<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementEmployee;
use App\Models\ChMessage;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\Employee;
use App\Models\Meeting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileExtendedController extends Controller
{
    // ════════════════════════════════════════════════════════════
    // 1. EDIT PROFILE — GET + PUT
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/profile/edit
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'name'            => $user->name,
                'email'           => $user->email,
                'avatar'          => $user->avatar
                    ? asset('storage/uploads/avatar/' . $user->avatar)
                    : null,
                'phone'           => $employee->phone ?? null,
                'dob'             => $employee->dob ?? null,
                'gender'          => $employee->gender ?? null,
                'address'         => $employee->present_address ?? null,
                'city'            => $employee->present_city ?? null,
                'state'           => $employee->present_state ?? null,
                'country'         => $employee->present_country ?? null,
                'permanent_address' => $employee->permanent_address ?? null,
            ],
        ]);
    }

    /**
     * PUT /api/mobile/profile/edit
     * Body: name, phone, dob, gender, address, city, state, country, avatar (base64 optional)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => 'sometimes|string|max:100',
            'phone'   => 'sometimes|string|max:20',
            'dob'     => 'sometimes|date',
            'gender'  => 'sometimes|in:Male,Female,Other',
            'address' => 'sometimes|string|max:500',
            'city'    => 'sometimes|string|max:100',
            'state'   => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'avatar'  => 'sometimes|string',
        ]);

        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($request->filled('name')) {
            $user->name = $request->name;
            if ($employee) $employee->name = $request->name;
        }

        // Avatar upload
        if ($request->filled('avatar')) {
            $base64 = $request->avatar;
            if (str_contains($base64, ',')) {
                $base64 = explode(',', $base64, 2)[1];
            }
            $decoded = base64_decode($base64, true);
            if ($decoded && strlen($decoded) > 500) {
                $filename = 'avatar_' . $user->id . '_' . time() . '.jpg';
                Storage::disk('public')->put('uploads/avatar/' . $filename, $decoded);
                $user->avatar = $filename;
            }
        }

        $user->save();

        if ($employee) {
            if ($request->filled('phone'))   $employee->phone           = $request->phone;
            if ($request->filled('dob'))     $employee->dob             = $request->dob;
            if ($request->filled('gender'))  $employee->gender          = $request->gender;
            if ($request->filled('address')) $employee->present_address = $request->address;
            if ($request->filled('city'))    $employee->present_city    = $request->city;
            if ($request->filled('state'))   $employee->present_state   = $request->state;
            if ($request->filled('country')) $employee->present_country = $request->country;
            $employee->save();
        }

        return response()->json(['success' => true, 'message' => 'Profile updated successfully.']);
    }

    // ════════════════════════════════════════════════════════════
    // 2. MY ACCOUNT — Account info + change password + delete token
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/account
     */
    public function myAccount(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'type'            => $user->type,
                'avatar'          => $user->avatar
                    ? asset('storage/uploads/avatar/' . $user->avatar)
                    : null,
                'employee_id'     => $employee->employee_id ?? null,
                'department'      => $employee->department->name ?? null,
                'designation'     => $employee->designation->name ?? null,
                'branch'          => $employee->branch->name ?? null,
                'company_doj'     => $employee->company_doj ?? null,
                'active_tokens'   => $user->tokens()->count(),
                'account_created' => $user->created_at?->toDateString(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/account/change-password
     * Body: current_password, new_password, new_password_confirmation
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    /**
     * DELETE /api/mobile/account/sessions — Logout from all devices
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Logged out from all devices.']);
    }

    // ════════════════════════════════════════════════════════════
    // 3. NOTIFICATIONS — Announcements + Meeting alerts
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        $cid      = $user->creatorId();

        $notifications = [];

        // Announcements for this employee
        if ($employee) {
            $announcements = Announcement::where('created_by', $cid)
                ->where(function ($q) use ($employee) {
                    $q->where('department_id', 'like', '%"0"%')
                      ->orWhere('department_id', 'like', '%"' . $employee->department_id . '"%');
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            foreach ($announcements as $ann) {
                $notifications[] = [
                    'id'      => 'ann_' . $ann->id,
                    'type'    => 'announcement',
                    'title'   => $ann->title,
                    'message' => $ann->description ?? '',
                    'date'    => $ann->start_date ?? $ann->created_at?->toDateString(),
                    'read'    => false,
                ];
            }

            // Upcoming meetings (next 7 days)
            $meetings = Meeting::join('meeting_employees', 'meetings.id', '=', 'meeting_employees.meeting_id')
                ->where('meeting_employees.employee_id', $employee->id)
                ->where('meetings.date', '>=', now()->toDateString())
                ->where('meetings.date', '<=', now()->addDays(7)->toDateString())
                ->select('meetings.*')
                ->orderBy('meetings.date')
                ->limit(10)
                ->get();

            foreach ($meetings as $meeting) {
                $notifications[] = [
                    'id'        => 'meet_' . $meeting->id,
                    'type'      => 'meeting',
                    'title'     => 'Meeting: ' . $meeting->title,
                    'message'   => 'Scheduled on ' . $meeting->date . ' at ' . $meeting->time,
                    'date'      => $meeting->date,
                    'meet_link' => $meeting->meet_link,
                    'read'      => false,
                ];
            }
        }

        // Sort by date descending
        usort($notifications, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return response()->json([
            'success' => true,
            'count'   => count($notifications),
            'data'    => $notifications,
        ]);
    }

    /**
     * GET /api/mobile/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();
        $count    = 0;

        if ($employee) {
            $count += Announcement::where('created_by', $user->creatorId())
                ->where(function ($q) use ($employee) {
                    $q->where('department_id', 'like', '%"0"%')
                      ->orWhere('department_id', 'like', '%"' . $employee->department_id . '"%');
                })
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        }

        return response()->json(['success' => true, 'unread_count' => $count]);
    }

    // ════════════════════════════════════════════════════════════
    // 4. LIVE CHAT — Messages between employees
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/chat/contacts — All employees to chat with
     */
    public function chatContacts(Request $request): JsonResponse
    {
        $user      = $request->user();
        $employees = Employee::where('created_by', $user->creatorId())
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get();

        $contacts = $employees->map(function ($emp) use ($user) {
            $lastMsg = ChMessage::where(function ($q) use ($user, $emp) {
                $q->where('from_id', $user->id)->where('to_id', $emp->user_id);
            })->orWhere(function ($q) use ($user, $emp) {
                $q->where('from_id', $emp->user_id)->where('to_id', $user->id);
            })->orderByDesc('id')->first();

            $unread = ChMessage::where('from_id', $emp->user_id)
                ->where('to_id', $user->id)
                ->where('seen', 0)
                ->count();

            return [
                'user_id'      => $emp->user_id,
                'employee_id'  => $emp->id,
                'name'         => $emp->name,
                'designation'  => $emp->designation->name ?? null,
                'avatar'       => $emp->user->avatar
                    ? asset('storage/uploads/avatar/' . $emp->user->avatar)
                    : null,
                'last_message' => $lastMsg?->body ?? null,
                'last_time'    => $lastMsg?->created_at?->toDateTimeString(),
                'unread'       => $unread,
            ];
        });

        return response()->json(['success' => true, 'data' => $contacts]);
    }

    /**
     * GET /api/mobile/chat/messages/{userId}?page=1
     */
    public function chatMessages(Request $request, $userId): JsonResponse
    {
        $me   = $request->user()->id;
        $page = max(1, (int)$request->get('page', 1));
        $perPage = 30;

        $messages = ChMessage::where(function ($q) use ($me, $userId) {
            $q->where('from_id', $me)->where('to_id', $userId);
        })->orWhere(function ($q) use ($me, $userId) {
            $q->where('from_id', $userId)->where('to_id', $me);
        })
        ->orderByDesc('id')
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get()
        ->reverse()
        ->values();

        // Mark as seen
        ChMessage::where('from_id', $userId)->where('to_id', $me)->where('seen', 0)->update(['seen' => 1]);

        return response()->json([
            'success' => true,
            'page'    => $page,
            'data'    => $messages->map(fn($m) => [
                'id'         => $m->id,
                'from_me'    => $m->from_id == $me,
                'message'    => $m->body,
                'attachment' => $m->attachment ? asset('storage/' . $m->attachment) : null,
                'seen'       => (bool)$m->seen,
                'time'       => $m->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * POST /api/mobile/chat/send
     * Body: to_user_id, message, attachment (base64 optional)
     */
    public function chatSend(Request $request): JsonResponse
    {
        $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'message'     => 'required_without:attachment|string|max:2000',
            'attachment'  => 'sometimes|string',
        ]);

        $attachmentPath = null;
        if ($request->filled('attachment')) {
            $base64 = $request->attachment;
            if (str_contains($base64, ',')) $base64 = explode(',', $base64, 2)[1];
            $decoded = base64_decode($base64, true);
            if ($decoded && strlen($decoded) > 100) {
                $fname = 'chat_' . Str::random(10) . '_' . time() . '.jpg';
                Storage::disk('public')->put('chat_attachments/' . $fname, $decoded);
                $attachmentPath = 'chat_attachments/' . $fname;
            }
        }

        $msg = ChMessage::create([
            'id'         => Str::uuid(),
            'type'       => 'user',
            'from_id'    => $request->user()->id,
            'to_id'      => $request->to_user_id,
            'body'       => $request->message ?? '',
            'attachment' => $attachmentPath,
            'seen'       => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data'    => [
                'id'      => $msg->id,
                'message' => $msg->body,
                'time'    => $msg->created_at?->toDateTimeString(),
            ],
        ], 201);
    }

    // ════════════════════════════════════════════════════════════
    // 5. SUPPORT — Tickets (Help Desk)
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/support/tickets
     */
    public function myTickets(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $tickets->map(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'status'      => $t->status,
                'priority'    => $t->priority,
                'category'    => $t->category,
                'description' => $t->description,
                'created_at'  => $t->created_at?->toDateTimeString(),
                'updated_at'  => $t->updated_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * POST /api/mobile/support/tickets
     * Body: title, description, priority, category
     */
    public function createTicket(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'required|string',
            'priority'    => 'sometimes|in:Low,Medium,High,Critical',
            'category'    => 'sometimes|string|max:100',
        ]);

        $ticket = Ticket::create([
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority ?? 'Medium',
            'category'    => $request->category ?? 'General',
            'status'      => 'Open',
            'user_id'     => $request->user()->id,
            'created_by'  => $request->user()->creatorId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created.',
            'data'    => ['id' => $ticket->id, 'status' => 'Open'],
        ], 201);
    }

    /**
     * GET /api/mobile/support/tickets/{id}
     */
    public function ticketDetail(Request $request, $id): JsonResponse
    {
        $ticket = Ticket::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        $replies = TicketReply::where('ticket_id', $id)->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $ticket->id,
                'title'       => $ticket->title,
                'description' => $ticket->description,
                'status'      => $ticket->status,
                'priority'    => $ticket->priority,
                'created_at'  => $ticket->created_at?->toDateTimeString(),
                'replies'     => $replies->map(fn($r) => [
                    'id'         => $r->id,
                    'message'    => $r->description,
                    'from_admin' => $r->user_id != $request->user()->id,
                    'time'       => $r->created_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    /**
     * POST /api/mobile/support/tickets/{id}/reply
     * Body: message
     */
    public function replyTicket(Request $request, $id): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $ticket = Ticket::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        $reply = TicketReply::create([
            'ticket_id'   => $id,
            'user_id'     => $request->user()->id,
            'description' => $request->message,
        ]);

        $ticket->status = 'In Progress';
        $ticket->save();

        return response()->json(['success' => true, 'message' => 'Reply sent.', 'data' => ['id' => $reply->id]], 201);
    }

    // ════════════════════════════════════════════════════════════
    // 6. CONTACT — Company contact info
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/contact
     */
    public function contact(Request $request): JsonResponse
    {
        $settings = \App\Models\Utility::settings($request->user()->creatorId());

        return response()->json([
            'success' => true,
            'data'    => [
                'company_name'    => $settings['company_name'] ?? config('app.name'),
                'company_email'   => $settings['company_email'] ?? null,
                'company_phone'   => $settings['company_phone'] ?? null,
                'company_address' => $settings['company_address'] ?? null,
                'company_city'    => $settings['company_city'] ?? null,
                'company_country' => $settings['company_country'] ?? null,
                'company_website' => $settings['company_website'] ?? null,
                'hr_email'        => $settings['hr_email'] ?? null,
                'support_email'   => $settings['support_email'] ?? null,
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 7. HELP — FAQs & app guide
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/help
     */
    public function help(Request $request): JsonResponse
    {
        $faqs = [
            ['q' => 'How do I clock in?',           'a' => 'Go to Attendance tab and tap "Clock In". Allow location access when prompted.'],
            ['q' => 'How do I apply for leave?',    'a' => 'Go to Leave tab → Apply Leave. Fill the form and submit. HR will review your request.'],
            ['q' => 'How do I view my payslip?',    'a' => 'Go to My Account → Payslips to view and download your salary slips.'],
            ['q' => 'How do I raise a support ticket?', 'a' => 'Go to Support tab → New Ticket. Describe your issue and submit.'],
            ['q' => 'How do I update my profile?',  'a' => 'Go to Profile → Edit Profile. You can update your photo, phone, and address.'],
            ['q' => 'How do I join a meeting?',     'a' => 'Go to Meetings tab. Tap "Join" on any meeting that has a link.'],
            ['q' => 'What if I forgot to clock out?', 'a' => 'Submit a Swipe Request from the Attendance tab explaining the situation.'],
            ['q' => 'How do I change my password?', 'a' => 'Go to My Account → Change Password. Enter your current and new password.'],
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'faqs'       => $faqs,
                'user_guide' => 'For full user guide, visit your company HR portal.',
                'version'    => config('app.version', '1.0.0'),
            ],
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 8. CONNECT — Team directory
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/connect?search=name&department_id=1
     */
    public function connect(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Employee::where('created_by', $user->creatorId())
            ->where('user_id', '!=', $user->id)
            ->with(['department', 'designation', 'branch', 'user']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('employee_id', 'like', "%$s%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->orderBy('name')->limit(50)->get();

        return response()->json([
            'success' => true,
            'count'   => $employees->count(),
            'data'    => $employees->map(fn($emp) => [
                'employee_id' => $emp->id,
                'name'        => $emp->name,
                'emp_code'    => $emp->employee_id,
                'email'       => $emp->user->email ?? null,
                'phone'       => $emp->phone,
                'department'  => $emp->department->name ?? null,
                'designation' => $emp->designation->name ?? null,
                'branch'      => $emp->branch->name ?? null,
                'avatar'      => $emp->user?->avatar
                    ? asset('storage/uploads/avatar/' . $emp->user->avatar)
                    : null,
            ]),
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // 9. MEETINGS — List + detail
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/meetings?upcoming=1
     */
    public function meetings(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $query = Meeting::join('meeting_employees', 'meetings.id', '=', 'meeting_employees.meeting_id')
            ->where('meeting_employees.employee_id', $employee->id)
            ->select('meetings.*')
            ->orderByDesc('meetings.date');

        if ($request->get('upcoming')) {
            $query->where('meetings.date', '>=', now()->toDateString());
        }

        $meetings = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data'    => $meetings->map(fn($m) => [
                'id'        => $m->id,
                'title'     => $m->title,
                'date'      => $m->date,
                'time'      => $m->time,
                'note'      => $m->note,
                'meet_link' => $m->meet_link,
                'can_join'  => !empty($m->meet_link),
            ]),
        ]);
    }
}
