<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobOnBoard;
use App\Support\RecruitmentNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferManagementController extends Controller
{
    /**
     * Salary above this CTC requires admin approval before the offer can
     * be released. Pulled from settings if configured, else defaults to
     * 1.2 cr / annum so default behaviour is "no approval needed".
     */
    private function approvalThreshold(): float
    {
        $cid = Auth::user()->creatorId();
        $val = DB::table('settings')
            ->where('name', 'offer_approval_threshold')
            ->where('created_by', $cid)
            ->value('value');
        return $val ? (float) $val : 1_20_00_000.00;
    }

    private function creatorId(): int
    {
        return Auth::user()->creatorId();
    }

    private function authorizedOffer(int $id): JobOnBoard
    {
        return JobOnBoard::where('created_by', $this->creatorId())->findOrFail($id);
    }

    private function isApprover(): bool
    {
        return in_array(Auth::user()->type, ['company', 'hr', 'super admin'], true);
    }

    /**
     * Create (if needed) an offer for the selected candidate and send
     * the proposed CTC to management for approval.
     *
     * POST /recruitment/compare/offer-request
     */
    public function requestApprovalFromCompare(Request $request)
    {
        $cid = $this->creatorId();
        $data = $request->validate([
            'candidate_id' => 'required|integer',
            'total_ctc'    => 'required|numeric|min:0',
            'currency'     => 'nullable|string|max:8',
        ]);

        $candidate = JobApplication::where('created_by', $cid)->findOrFail((int) $data['candidate_id']);
        if (($candidate->final_status ?? 'pending') !== 'selected') {
            return back()->with('error', __('Please mark the candidate as Selected before releasing an offer.'));
        }

        $offer = JobOnBoard::where('created_by', $cid)
            ->where('application', $candidate->id)
            ->orderByDesc('id')
            ->first();

        if (!$offer) {
            $offer = new JobOnBoard();
            $offer->application = $candidate->id;
            $offer->created_by  = $cid;
            $offer->status      = 'pending';
        }

        if (in_array($offer->status, ['offer_released', 'accepted', 'declined', 'confirm'], true)) {
            return redirect()
                ->route('recruitment.offers.show', $offer->id)
                ->with('info', __('Offer is already in progress / released.'));
        }

        $ctc = round((float) $data['total_ctc'], 2);
        $offer->currency = $data['currency'] ?: ($offer->currency ?: 'INR');
        $offer->compensation_breakup = [
            ['label' => 'Total CTC', 'amount' => $ctc, 'cadence' => 'annual'],
        ];
        $offer->total_ctc = $offer->computeTotalCtc();

        // Always send to approval from this action (explicit management sign-off).
        $offer->status              = 'awaiting_approval';
        $offer->requires_approval   = true;
        $offer->approved_by_user_id = null;
        $offer->approved_at         = null;
        $offer->save();

        RecruitmentNotifications::flush();

        return redirect()
            ->route('recruitment.offers.show', $offer->id)
            ->with('success', __('Offer sent to management for CTC approval.'));
    }

    // ── INDEX (lifecycle board) ───────────────────────────────────
    public function index(Request $request)
    {
        $cid    = $this->creatorId();
        $status = $request->input('status');

        $query = JobOnBoard::with(['applications.jobs', 'approver'])
            ->where('created_by', $cid);

        if ($status) {
            $query->where('status', $status);
        }

        $offers = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $statusCounts = JobOnBoard::where('created_by', $cid)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('recruitment.offers.index', compact('offers', 'status', 'statusCounts'));
    }

    // ── SHOW (single offer with breakup + lifecycle actions) ──────
    public function show($id)
    {
        $offer = JobOnBoard::with(['applications.jobs', 'approver'])
            ->where('created_by', $this->creatorId())
            ->findOrFail($id);

        // Seed default breakup on first view if none exists yet
        if (empty($offer->compensation_breakup)) {
            $offer->compensation_breakup = JobOnBoard::defaultBreakup();
            $offer->save();
        }

        return view('recruitment.offers.show', [
            'offer'      => $offer,
            'isApprover' => $this->isApprover(),
            'threshold'  => $this->approvalThreshold(),
        ]);
    }

    // ── COMPENSATION BREAKUP ──────────────────────────────────────
    /**
     * POST /recruitment/offers/{id}/compensation
     * Body: rows[] = [{label, amount, cadence}], currency, offer_expiry_date
     */
    public function saveCompensation(Request $request, $id)
    {
        $offer = $this->authorizedOffer($id);

        $data = $request->validate([
            'rows'              => 'required|array|min:1|max:30',
            'rows.*.label'      => 'required|string|max:120',
            'rows.*.amount'     => 'required|numeric|min:0',
            'rows.*.cadence'    => 'required|in:monthly,annual,one_time',
            'currency'          => 'nullable|string|max:8',
            'offer_expiry_date' => 'nullable|date',
        ]);

        // Drop empty rows (label empty AND amount=0).
        $rows = collect($data['rows'])
            ->filter(fn($r) => trim($r['label']) !== '' || (float) $r['amount'] > 0)
            ->values()
            ->all();

        $offer->compensation_breakup = $rows;
        $offer->currency             = $data['currency'] ?? 'INR';
        $offer->offer_expiry_date    = $data['offer_expiry_date'] ?? null;
        $offer->total_ctc            = $offer->computeTotalCtc();
        $offer->save();

        return back()->with('success', __('Compensation breakup saved. Total CTC: :ctc', [
            'ctc' => number_format($offer->total_ctc, 2),
        ]));
    }

    // ── LIFECYCLE TRANSITIONS ─────────────────────────────────────
    /**
     * Move to "awaiting_approval" if CTC >= threshold; else jump straight to
     * "offer_released" (no approval needed).
     */
    public function release(Request $request, $id)
    {
        $offer = $this->authorizedOffer($id);
        if ($offer->total_ctc === null) {
            return back()->with('error', __('Save the compensation breakup first.'));
        }

        $needsApproval = (float) $offer->total_ctc >= $this->approvalThreshold();
        if ($needsApproval && !$offer->approved_at) {
            $offer->status            = 'awaiting_approval';
            $offer->requires_approval = true;
            $offer->save();
            return back()->with('info', __('Offer sent for approval (CTC above threshold).'));
        }

        $offer->status            = 'offer_released';
        $offer->offer_released_at = now();
        $offer->save();
        return back()->with('success', __('Offer released to candidate.'));
    }

    public function approve($id)
    {
        if (!$this->isApprover()) abort(403);
        $offer = $this->authorizedOffer($id);
        if ($offer->status !== 'awaiting_approval') {
            return back()->with('error', __('This offer is not awaiting approval.'));
        }
        $offer->approved_by_user_id = Auth::id();
        $offer->approved_at         = now();
        $offer->status              = 'offer_released';
        $offer->offer_released_at   = now();
        $offer->save();
        return back()->with('success', __('Offer approved and released.'));
    }

    public function negotiation(Request $request, $id)
    {
        $offer = $this->authorizedOffer($id);
        $data = $request->validate([
            'negotiation_notes' => 'required|string|max:5000',
        ]);
        $offer->status            = 'negotiation';
        $offer->negotiation_notes = trim(($offer->negotiation_notes ? $offer->negotiation_notes . "\n\n" : '')
            . '— ' . Auth::user()->name . ' · ' . now()->format('d M Y H:i') . "\n"
            . $data['negotiation_notes']);
        $offer->save();
        return back()->with('success', __('Negotiation note recorded.'));
    }

    public function accept($id)
    {
        $offer = $this->authorizedOffer($id);
        if (!in_array($offer->status, ['offer_released', 'negotiation'], true)) {
            return back()->with('error', __('Only released / negotiation offers can be accepted.'));
        }
        $offer->status               = 'accepted';
        $offer->accepted_declined_at = now();
        $offer->save();
        return back()->with('success', __('Candidate accepted the offer.'));
    }

    public function decline(Request $request, $id)
    {
        $offer = $this->authorizedOffer($id);
        $data = $request->validate(['decline_reason' => 'required|string|max:2000']);

        $offer->status               = 'declined';
        $offer->decline_reason       = $data['decline_reason'];
        $offer->accepted_declined_at = now();
        $offer->save();
        return back()->with('success', __('Offer marked as declined.'));
    }

    public function uploadOfferLetter(Request $request, $id)
    {
        $offer = $this->authorizedOffer($id);
        $request->validate(['offer_letter' => 'required|file|mimes:pdf,doc,docx|max:10240']);
        $file = $request->file('offer_letter');
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('recruitment/offers/' . $offer->id, $name, 'public');
        // Replace previous file
        if ($offer->offer_letter_path && Storage::disk('public')->exists($offer->offer_letter_path)) {
            Storage::disk('public')->delete($offer->offer_letter_path);
        }
        $offer->offer_letter_path = $path;
        $offer->save();
        return back()->with('success', __('Offer letter uploaded.'));
    }
}
