<?php

namespace App\Support;

use App\Models\BgvCheck;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use App\Models\JobOnBoard;
use App\Models\ManpowerRequisition;
use App\Models\PreonboardingItem;
use App\Models\ProbationReview;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Computes "things needing attention" across all recruitment stages
 * for the currently-authenticated user.
 *
 * Cached for 60 seconds so that hitting the dashboard / nav doesn't
 * fire 8+ count queries per page load.
 */
class RecruitmentNotifications
{
    private const CACHE_TTL = 60; // seconds

    /**
     * Returns an associative array of counts:
     *   - requisitions, offers_awaiting_approval, offers_negotiation,
     *     interviews_to_feedback, bgv_pending, preonboarding_pending,
     *     probation_due, decisions_pending
     *   - total (sum of the above)
     *   - items (top 10 actionable items as feed entries)
     */
    public static function summary(): array
    {
        $user = Auth::user();
        if (!$user) {
            return self::emptySummary();
        }

        $key = 'rec_notif:' . $user->id;
        return Cache::remember($key, self::CACHE_TTL, function () use ($user) {
            return self::compute($user);
        });
    }

    public static function flush(): void
    {
        if ($u = Auth::user()) Cache::forget('rec_notif:' . $u->id);
    }

    // ── internals ─────────────────────────────────────────────────
    private static function compute($user): array
    {
        $cid       = $user->creatorId();
        $type      = $user->type;
        $isApprover= in_array($type, ['company', 'hr', 'super admin'], true);
        $isCompany = in_array($type, ['company', 'super admin'], true);

        // 1. Requisitions awaiting THIS user's approval step
        $pendingReqs = collect();
        if ($isApprover) {
            $pendingReqs = ManpowerRequisition::where('created_by', $cid)
                ->where('status', 'pending')
                ->get(['id', 'title', 'approval_chain', 'current_approval_step', 'priority', 'created_at']);
            $pendingReqs = $pendingReqs->filter(function ($r) use ($type, $isCompany) {
                $expected = $r->next_approver_role;
                if (!$expected) return false;
                if ($isCompany)            return true; // company/super-admin can approve any
                if ($expected === 'hr')    return $type === 'hr';
                if ($expected === 'finance') return $type === 'finance';
                return false;
            });
        }

        // 2. Offers awaiting approval
        $offersAwaiting = $isApprover
            ? JobOnBoard::where('created_by', $cid)
                ->where('status', 'awaiting_approval')
                ->with('applications')
                ->get(['id', 'application', 'total_ctc', 'currency', 'created_at'])
            : collect();

        // 3. Offers in negotiation (HR action)
        $offersNegotiation = $isApprover
            ? JobOnBoard::where('created_by', $cid)
                ->where('status', 'negotiation')
                ->with('applications')
                ->count()
            : 0;

        // 4. Interviews where this user is interviewer, scheduled in past, no status update yet
        $interviewsToFeedback = InterviewSchedule::where('created_by', $cid)
            ->where('employee', $user->id)
            ->where('status', 'scheduled')
            ->where('date', '<=', now()->toDateString())
            ->count();

        // 5. BGV checks in progress (across the company)
        $bgvPending = $isApprover
            ? BgvCheck::where('created_by', $cid)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count()
            : 0;

        // 6. Pre-onboarding pending items
        $preonPending = $isApprover
            ? PreonboardingItem::where('created_by', $cid)
                ->where('status', 'pending')
                ->count()
            : 0;

        // 7. Probation reviews due (review_date <= today, outcome=pending)
        $probationDue = $isApprover
            ? ProbationReview::where('created_by', $cid)
                ->where('outcome', 'pending')
                ->where('review_date', '<=', now()->toDateString())
                ->count()
            : 0;

        // 8. Final decisions pending — candidates past interview stage with no final_status
        $decisionsPending = $isApprover
            ? JobApplication::where('created_by', $cid)
                ->where(function ($q) {
                    $q->where('final_status', 'pending')->orWhereNull('final_status');
                })
                ->whereNotNull('rating')
                ->where('rating', '>=', 1)
                ->count()
            : 0;

        $counts = [
            'requisitions'            => $pendingReqs->count(),
            'offers_awaiting_approval'=> $offersAwaiting->count(),
            'offers_negotiation'      => $offersNegotiation,
            'interviews_to_feedback'  => $interviewsToFeedback,
            'bgv_pending'             => $bgvPending,
            'preonboarding_pending'   => $preonPending,
            'probation_due'           => $probationDue,
            'decisions_pending'       => $decisionsPending,
        ];
        $total = array_sum($counts);

        // Build a feed of top-priority items for the dashboard / bell
        $items = [];
        foreach ($pendingReqs as $r) {
            $items[] = [
                'icon'     => 'ti-file-plus',
                'color'    => 'warning',
                'title'    => __('Requisition awaiting approval') . ' — ' . $r->next_approver_role,
                'subtitle' => $r->title . ' · ' . __(ucfirst($r->priority) . ' priority'),
                'when'     => Carbon::parse($r->created_at)->diffForHumans(),
                'url'      => route('recruitment.requisitions.show', $r->id),
            ];
        }
        foreach ($offersAwaiting as $o) {
            $name = $o->applications->name ?? __('Candidate');
            $ctc  = $o->total_ctc
                ? ' · ' . ($o->currency ?: 'INR') . ' ' . number_format($o->total_ctc, 0)
                : '';
            $items[] = [
                'icon'     => 'ti-receipt-2',
                'color'    => 'info',
                'title'    => __('Offer awaiting approval'),
                'subtitle' => $name . $ctc,
                'when'     => Carbon::parse($o->created_at)->diffForHumans(),
                'url'      => route('recruitment.offers.show', $o->id),
            ];
        }
        if ($interviewsToFeedback > 0) {
            $items[] = [
                'icon'     => 'ti-message-circle-2',
                'color'    => 'success',
                'title'    => __(':n interview(s) need your feedback', ['n' => $interviewsToFeedback]),
                'subtitle' => __('Past-due interviews where you were the interviewer.'),
                'when'     => '',
                'url'      => route('interview-schedule.index'),
            ];
        }
        if ($probationDue > 0) {
            $items[] = [
                'icon'     => 'ti-user-check',
                'color'    => 'warning',
                'title'    => __(':n probation review(s) due', ['n' => $probationDue]),
                'subtitle' => __('Click to review and confirm / extend / terminate.'),
                'when'     => '',
                'url'      => route('recruitment.probation.index'),
            ];
        }
        if ($offersNegotiation > 0) {
            $items[] = [
                'icon'     => 'ti-messages',
                'color'    => 'warning',
                'title'    => __(':n offer(s) in negotiation', ['n' => $offersNegotiation]),
                'subtitle' => __('Reach out to close or capture decline.'),
                'when'     => '',
                'url'      => route('recruitment.offers.index', ['status' => 'negotiation']),
            ];
        }
        if ($bgvPending > 0) {
            $items[] = [
                'icon'     => 'ti-shield-check',
                'color'    => 'secondary',
                'title'    => __(':n BGV check(s) pending', ['n' => $bgvPending]),
                'subtitle' => __('Update status / upload documents.'),
                'when'     => '',
                'url'      => route('recruitment.bgv.index'),
            ];
        }
        if ($preonPending > 0) {
            $items[] = [
                'icon'     => 'ti-checklist',
                'color'    => 'secondary',
                'title'    => __(':n pre-onboarding item(s) pending', ['n' => $preonPending]),
                'subtitle' => __('Documents / IT assets / access pending.'),
                'when'     => '',
                'url'      => route('recruitment.preonboarding.index'),
            ];
        }
        if ($decisionsPending > 0) {
            $items[] = [
                'icon'     => 'ti-gavel',
                'color'    => 'primary',
                'title'    => __(':n candidate(s) awaiting final decision', ['n' => $decisionsPending]),
                'subtitle' => __('Mark Selected / Backup / Rejected.'),
                'when'     => '',
                'url'      => route('recruitment.decisions.index'),
            ];
        }

        return [
            'counts' => $counts,
            'total'  => $total,
            'items'  => array_slice($items, 0, 10),
        ];
    }

    private static function emptySummary(): array
    {
        return [
            'counts' => [
                'requisitions' => 0, 'offers_awaiting_approval' => 0, 'offers_negotiation' => 0,
                'interviews_to_feedback' => 0, 'bgv_pending' => 0, 'preonboarding_pending' => 0,
                'probation_due' => 0, 'decisions_pending' => 0,
            ],
            'total' => 0,
            'items' => [],
        ];
    }
}
