<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Models\ChatGroupMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatGroupController extends Controller
{
    public function index(Request $request, ?int $groupId = null)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $groups = ChatGroup::where('created_by', $creatorId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['members.user', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest('updated_at')
            ->get();

        $selectedGroup = null;
        if (!empty($groupId)) {
            $selectedGroup = $groups->firstWhere('id', (int) $groupId);
        }
        if (empty($selectedGroup) && $groups->count() > 0) {
            $selectedGroup = $groups->first();
        }

        $messages = collect();
        if (!empty($selectedGroup)) {
            $messages = ChatGroupMessage::where('chat_group_id', $selectedGroup->id)
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            ChatGroupMember::where('chat_group_id', $selectedGroup->id)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);
        }

        $teamMembers = User::where('created_by', $creatorId)
            ->where('id', '!=', $user->id)
            ->whereNotIn('type', ['super admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'type']);

        return view('chat_groups.index', compact('groups', 'selectedGroup', 'messages', 'teamMembers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'members' => 'required|array|min:1',
            'members.*' => 'integer|exists:users,id',
        ]);

        $user = Auth::user();
        $creatorId = $user->creatorId();

        $allowedUserIds = User::where('created_by', $creatorId)->pluck('id')->toArray();
        $memberIds = array_values(array_unique(array_map('intval', (array) $request->members)));

        foreach ($memberIds as $memberId) {
            if (!in_array($memberId, $allowedUserIds)) {
                return redirect()->back()->with('error', __('Invalid member selected.'));
            }
        }

        if (!in_array((int) $user->id, $memberIds)) {
            $memberIds[] = (int) $user->id;
        }

        $group = ChatGroup::create([
            'name' => $request->name,
            'created_by' => $creatorId,
            'owner_id' => $user->id,
        ]);

        foreach ($memberIds as $memberId) {
            ChatGroupMember::create([
                'chat_group_id' => $group->id,
                'user_id' => $memberId,
                'added_by' => $user->id,
            ]);
        }

        return redirect()->route('chat-groups.index', ['groupId' => $group->id])->with('success', __('Group created successfully.'));
    }

    public function addMembers(Request $request, int $groupId)
    {
        $request->validate([
            'members' => 'required|array|min:1',
            'members.*' => 'integer|exists:users,id',
        ]);

        $user = Auth::user();
        $creatorId = $user->creatorId();
        $group = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }

        $allowedUserIds = User::where('created_by', $creatorId)->pluck('id')->toArray();

        foreach ((array) $request->members as $memberId) {
            $memberId = (int) $memberId;
            if (!in_array($memberId, $allowedUserIds)) {
                continue;
            }

            ChatGroupMember::firstOrCreate(
                [
                    'chat_group_id' => $group->id,
                    'user_id' => $memberId,
                ],
                [
                    'added_by' => $user->id,
                ]
            );
        }

        return redirect()->route('chat-groups.index', ['groupId' => $group->id])->with('success', __('Members updated successfully.'));
    }

    public function getMessages(Request $request, int $groupId)
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();
        $group = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }

        $since = $request->query('since');
        $query = ChatGroupMessage::where('chat_group_id', $group->id)->with('user');
        if ($since) {
            $query->where('id', '>', (int) $since);
        }
        $messages = $query->orderBy('created_at', 'asc')->get();

        ChatGroupMember::where('chat_group_id', $group->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $currentUserId = $user->id;
        $data = $messages->map(function ($msg) use ($currentUserId) {
            return [
                'id'           => $msg->id,
                'user_id'      => $msg->user_id,
                'is_mine'      => $msg->user_id === $currentUserId,
                'user_name'    => $msg->user ? $msg->user->name : 'User',
                'message'      => $msg->message,
                'message_type' => $msg->message_type ?? 'text',
                'voice_path'   => $msg->voice_path ?? null,
                'time'         => $msg->created_at->format('d M, h:i A'),
            ];
        });

        return response()->json(['messages' => $data]);
    }

    public function sendMessage(Request $request, int $groupId)
    {

        $hasFile = $request->hasFile('file');
        $hasText = $request->filled('message');
        if (!$hasFile && !$hasText) {
            return response()->json(['error' => 'Message or file required'], 422);
        }
        $request->validate([
            'message' => 'nullable|string|max:5000',
            'file'    => 'nullable|file|max:20480', // 20MB
        ]);

        $user = Auth::user();
        $creatorId = $user->creatorId();
        $group = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }


        $filePath = null;
        $msgType = 'text';
        if ($hasFile) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
            $isVideo = in_array($ext, ['mp4','webm','ogg','avi','mov','mkv']);
            $msgType = $isImage ? 'image' : ($isVideo ? 'video' : 'file');
            $fileName = 'chat_' . $user->id . '_' . time() . '_' . Str::random(6) . '.' . $ext;
            $filePath = 'uploads/chat-files/' . $fileName;
            $file->move(public_path('uploads/chat-files'), $fileName);
        }
        $msg = ChatGroupMessage::create([
            'chat_group_id' => $group->id,
            'user_id'       => $user->id,
            'message'       => $request->message,
            'message_type'  => $msgType,
            'file_path'     => $filePath,
        ]);

        $group->updated_at = now();
        $group->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'id'      => $msg->id,
                'message' => $msg->message,
                'time'    => $msg->created_at->format('d M, h:i A'),
            ]);
        }

        return redirect()->route('chat-groups.index', ['groupId' => $group->id]);
    }

    public function sendVoice(Request $request, int $groupId)
    {
        $request->validate([
            'voice' => 'required|file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/webm,audio/ogg,audio/mp4,audio/x-m4a|max:10240',
        ]);

        $user = Auth::user();
        $creatorId = $user->creatorId();
        $group = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }

        $file = $request->file('voice');
        $voiceDir = public_path('uploads/chat-group-voice');
        if (!is_dir($voiceDir)) {
            mkdir($voiceDir, 0777, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'webm';
        $fileName = 'voice_' . time() . '_' . $user->id . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $file->move($voiceDir, $fileName);

        $voicePath = 'uploads/chat-group-voice/' . $fileName;

        $msg = ChatGroupMessage::create([
            'chat_group_id' => $group->id,
            'user_id'       => $user->id,
            'message'       => 'Voice message',
            'message_type'  => 'voice',
            'voice_path'    => $voicePath,
        ]);

        $group->updated_at = now();
        $group->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'id'         => $msg->id,
                'voice_path' => asset($voicePath),
                'time'       => $msg->created_at->format('d M, h:i A'),
            ]);
        }

        return redirect()->route('chat-groups.index', ['groupId' => $group->id]);
    }

    public function headerNotifications()
    {
        try {
            return $this->headerNotificationsResponse();
        } catch (\Throwable $e) {
            Log::warning('chat-groups headerNotifications failed', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'unread' => 0,
                'html' => '',
            ]);
        }
    }

    private function headerNotificationsResponse()
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $groups = ChatGroup::where('created_by', $creatorId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['messages' => function ($query) {
                $query->latest();
            }, 'members' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest('updated_at')
            ->take(10)
            ->get();

        $html = '';
        $unreadTotal = 0;

        foreach ($groups as $group) {
            $lastReadAt = optional($group->members->first())->last_read_at;
            $unread = ChatGroupMessage::where('chat_group_id', $group->id)
                ->where('user_id', '!=', $user->id)
                ->when(!empty($lastReadAt), function ($query) use ($lastReadAt) {
                    $query->where('created_at', '>', $lastReadAt);
                })
                ->count();

            $unreadTotal += $unread;
            $lastMessage = $group->messages->first();
            $preview = $lastMessage
                ? ($lastMessage->message_type === 'voice' ? '🎤 Voice message' : $lastMessage->message)
                : 'No messages yet';

            $html .= '<a href="' . route('chat-groups.index', ['groupId' => $group->id]) . '" class="dropdown-item">'
                . '<div class="d-flex justify-content-between">'
                . '<span><strong>' . e($group->name) . '</strong></span>'
                . ($unread > 0 ? '<span class="badge bg-danger">' . $unread . '</span>' : '')
                . '</div>'
                . '<small class="text-muted">' . e(\Illuminate\Support\Str::limit($preview, 40)) . '</small>'
                . '</a>';
        }

        return response()->json([
            'unread' => $unreadTotal,
            'html' => $html,
        ]);
    }

    public function chatboxFavorites()
    {
        $user = Auth::user();
        $creatorId = $user->creatorId();

        $groups = ChatGroup::where('created_by', $creatorId)
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1)->with('user');
            }, 'members' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest('updated_at')
            ->take(8)
            ->get();

        $groupsHtml = '';
        $combinedGroupsHtml = '';

        foreach ($groups as $group) {
            $lastMessage = $group->messages->first();
            $previewText = $lastMessage
                ? ($lastMessage->message_type === 'voice' ? '🎤 Voice message' : $lastMessage->message)
                : 'No messages yet';

            $senderName = $lastMessage && !empty($lastMessage->user) ? $lastMessage->user->name : null;
            $preview = !empty($senderName) ? ($senderName . ': ' . $previewText) : $previewText;

            $lastReadAt = optional($group->members->first())->last_read_at;
            $unread = ChatGroupMessage::where('chat_group_id', $group->id)
                ->where('user_id', '!=', $user->id)
                ->when(!empty($lastReadAt), function ($query) use ($lastReadAt) {
                    $query->where('created_at', '>', $lastReadAt);
                })
                ->count();

            $avatarUrl = $this->resolveAvatarUrl($lastMessage ? $lastMessage->user : null);
            $updatedAt = !empty($group->updated_at) ? $group->updated_at->diffForHumans() : '';

            $groupsHtml .= '<a href="' . route('chat-groups.index', ['groupId' => $group->id]) . '" class="messenger-list-item group-chat-item">'
                . '<div class="avatar av-m" style="background-image:url(' . e($avatarUrl) . ');"></div>'
                . '<div class="m-list-details">'
                . '<p>' . e($group->name) . ' <span class="group-chip">Group</span></p>'
                . '<span>' . e(Str::limit($preview, 45)) . '</span>'
                . '</div>'
                . '<div class="m-list-action text-end">'
                . (!empty($updatedAt) ? '<small>' . e($updatedAt) . '</small>' : '')
                . ($unread > 0 ? '<b>' . $unread . '</b>' : '')
                . '</div>'
                . '</a>';

            $combinedGroupsHtml .= '<a href="' . route('chat-groups.index', ['groupId' => $group->id]) . '" class="messenger-list-item group-chat-item combined-group-item">'
                . '<div class="avatar av-m" style="background-image:url(' . e($avatarUrl) . ');"></div>'
                . '<div class="m-list-details">'
                . '<p>' . e('Group • ' . $group->name) . '</p>'
                . '<span>' . e(Str::limit($preview, 45)) . '</span>'
                . '</div>'
                . '<div class="m-list-action text-end">'
                . ($unread > 0 ? '<b>' . $unread . '</b>' : '')
                . '</div>'
                . '</a>';
        }

        return response()->json([
            'html' => $groupsHtml,
            'groups_html' => $groupsHtml,
            'combined_html' => $combinedGroupsHtml,
        ]);
    }

    public function directContacts()
    {
        $userId = (int) Auth::id();

        try {
            return $this->directContactsResponse($userId);
        } catch (\Throwable $e) {
            Log::warning('chat directContacts failed', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json(['contacts' => []]);
        }
    }

    private function directContactsResponse(int $userId)
    {
        // Raw SQL — safe because $userId is cast to int
        $rows = DB::select("
            SELECT u.id, u.name, u.avatar, u.active_status,
                   MAX(m.created_at) AS last_at
            FROM ch_messages m
            JOIN users u ON u.id = IF(m.from_id = {$userId}, m.to_id, m.from_id)
            WHERE m.from_id = {$userId} OR m.to_id = {$userId}
            GROUP BY u.id, u.name, u.avatar, u.active_status
            ORDER BY last_at DESC
            LIMIT 30
        ");
        $rows = collect($rows);

        if ($rows->isEmpty()) {
            return response()->json(['contacts' => []]);
        }

        // Collect all contact IDs for bulk queries
        $contactIds = $rows->pluck('id')->toArray();

        // Bulk: last message per pair (one query)
        $lastMsgs = DB::select("
            SELECT m.*
            FROM ch_messages m
            INNER JOIN (
                SELECT
                    IF(from_id = {$userId}, to_id, from_id) AS contact_id,
                    MAX(id) AS max_id
                FROM ch_messages
                WHERE from_id = {$userId} OR to_id = {$userId}
                GROUP BY IF(from_id = {$userId}, to_id, from_id)
            ) latest ON m.id = latest.max_id
        ");
        $lastMsgMap = collect($lastMsgs)->keyBy(function ($m) use ($userId) {
            return $m->from_id == $userId ? $m->to_id : $m->from_id;
        });

        // Bulk: unseen counts (one query)
        $unreadRows = DB::table('ch_messages')
            ->selectRaw('from_id, COUNT(*) as cnt')
            ->whereIn('from_id', $contactIds)
            ->where('to_id', $userId)
            ->where('seen', 0)
            ->groupBy('from_id')
            ->pluck('cnt', 'from_id');

        $contacts = [];
        foreach ($rows as $row) {
            // Avatar URL — handles: 'avatar.png', 'uploads/avatar/x.png', full URL
            $avatar = null;
            if (!empty($row->avatar) && $row->avatar !== 'avatar.png') {
                if (filter_var($row->avatar, FILTER_VALIDATE_URL)) {
                    $avatar = $row->avatar;                     // already full URL
                } elseif (str_starts_with($row->avatar, 'storage/') || str_starts_with($row->avatar, '/storage/')) {
                    $avatar = asset($row->avatar);              // storage/avatars/x.png
                } else {
                    $avatar = asset('storage/' . ltrim($row->avatar, '/'));
                }
            }

            $lastMsg  = $lastMsgMap->get($row->id);
            $lastMsgText = '';
            $lastMsgTime = '';
            $isMine      = false;
            if ($lastMsg) {
                $lastMsgText = !empty($lastMsg->attachment)
                    ? '📎 Attachment'
                    : Str::limit((string)($lastMsg->body ?? ''), 45);
                $lastMsgTime = \Carbon\Carbon::parse($lastMsg->created_at)->diffForHumans(null, true, true);
                $isMine      = $lastMsg->from_id == $userId;
            }

            $contacts[] = [
                'uid'     => $row->id,
                'name'    => $row->name,
                'avatar'  => $avatar,
                'lastMsg' => $lastMsgText,
                'time'    => $lastMsgTime,
                'isMine'  => $isMine,
                'unread'  => (int)($unreadRows->get($row->id) ?? 0),
                'online'  => (bool)$row->active_status,
            ];
        }

        return response()->json(['contacts' => $contacts]);
    }

    private function resolveAvatarUrl(?User $user): string
    {
        if (!empty($user) && !empty($user->avatar)) {
            return asset('/storage/avatars/' . $user->avatar);
        }

        return asset('/storage/avatars/avatar.png');
    }

    protected function isMember(int $groupId, int $userId): bool
    {
        return ChatGroupMember::where('chat_group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * GET /chat-inline-messages/{userId}
     * Returns the last N direct messages between auth user and $userId as JSON.
     */
    public function inlineMessages(int $userId)
    {
        $me = \Auth::id();
        $messages = \DB::table('ch_messages')
            ->where(function ($q) use ($me, $userId) {
                $q->where('from_id', $me)->where('to_id', $userId);
            })
            ->orWhere(function ($q) use ($me, $userId) {
                $q->where('from_id', $userId)->where('to_id', $me);
            })
            ->orderBy('created_at', 'desc')
            ->limit(60)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($m) use ($me) {
                return [
                    'id'      => $m->id,
                    'from_id' => (int)$m->from_id,
                    'body'    => $m->body ?? '',
                    'isMine'  => ((int)$m->from_id === $me),
                    'time'    => \Carbon\Carbon::parse($m->created_at)->diffForHumans(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * POST /chat-inline-send/{userId}
     * Send a direct message from auth user to $userId.
     */
    public function inlineSend(Request $request, int $userId)
    {
        $body = trim($request->input('message', ''));
        if (empty($body)) {
            return response()->json(['error' => 'Empty message'], 422);
        }
        $me = \Auth::id();
        \DB::table('ch_messages')->insert([
            'from_id'    => $me,
            'to_id'      => $userId,
            'body'       => $body,
            'seen'       => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }

    /**
     * GET /chat-group-inline-messages/{groupId}
     * Returns last 60 messages of a group chat as JSON for the FAB inline panel.
     */
    public function inlineGroupMessages(int $groupId)
    {
        $user      = Auth::user();
        $creatorId = $user->creatorId();
        $group     = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }

        $messages = ChatGroupMessage::where('chat_group_id', $group->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(60)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) use ($user) {
                return [
                    'id'     => $msg->id,
                    'isMine' => $msg->user_id === $user->id,
                    'body'   => $msg->message ?? '',
                    'sender' => $msg->user ? $msg->user->name : 'User',
                    'time'   => $msg->created_at->diffForHumans(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * POST /chat-group-inline-send/{groupId}
     * Send a message to a group chat from the FAB inline panel.
     */
    public function inlineGroupSend(Request $request, int $groupId)
    {
        $body = trim($request->input('message', ''));
        if (empty($body)) {
            return response()->json(['error' => 'Empty message'], 422);
        }

        $user      = Auth::user();
        $creatorId = $user->creatorId();
        $group     = ChatGroup::where('created_by', $creatorId)->findOrFail($groupId);

        if (!$this->isMember($group->id, $user->id)) {
            abort(403);
        }

        ChatGroupMessage::create([
            'chat_group_id' => $group->id,
            'user_id'       => $user->id,
            'message'       => $body,
            'message_type'  => 'text',
        ]);

        return response()->json(['ok' => true]);
    }
}
