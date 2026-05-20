<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAppKeyIsValid
{
    public function handle(Request $request, \Closure $next): Response
    {
        $incomingKey = (string) $request->header('X-App-Key', '');
        if ($incomingKey === '') {
            return $this->error('X-App-Key header is required.', 401);
        }

        $creatorId = $this->resolveCreatorId($request, $incomingKey);
        if (!$creatorId) {
            return $this->error('Invalid mobile app API key.', 401);
        }

        $settings = DB::table('settings')
            ->where('created_by', $creatorId)
            ->whereIn('name', ['mobile_app_api_key', 'mobile_app_status'])
            ->pluck('value', 'name');

        $storedKey = (string) ($settings['mobile_app_api_key'] ?? '');
        $status = (string) ($settings['mobile_app_status'] ?? 'inactive');

        if ($storedKey === '' || !hash_equals($storedKey, $incomingKey)) {
            return $this->error('Invalid mobile app API key.', 401);
        }

        if ($status !== 'active') {
            return $this->error('Mobile app access is currently inactive for this key.', 403);
        }

        return $next($request);
    }

    protected function resolveCreatorId(Request $request, string $incomingKey): ?int
    {
        if ($request->user()) {
            return (int) $request->user()->creatorId();
        }

        $email = (string) $request->input('email', '');
        if ($email !== '') {
            $user = User::where('email', $email)->first();
            if ($user) {
                return (int) $user->creatorId();
            }
        }

        $ownerId = DB::table('settings')
            ->where('name', 'mobile_app_api_key')
            ->where('value', $incomingKey)
            ->value('created_by');

        if (!empty($ownerId)) {
            return (int) $ownerId;
        }

        return null;
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
