<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MobileApiPanelController extends Controller
{
    public function index(): View
    {
        // Public view — hide sensitive token/key management when not logged in
        // or when logged-in user is not company/super admin.
        $user = Auth::user();
        $isPrivileged = $user && in_array($user->type, ['company', 'super admin'], true);

        $apiKey = '';
        $status = 'inactive';
        if ($isPrivileged) {
            $dbSettings = DB::table('settings')
                ->where('created_by', $user->creatorId())
                ->whereIn('name', ['mobile_app_api_key', 'mobile_app_status'])
                ->pluck('value', 'name');
            $apiKey = (string) ($dbSettings['mobile_app_api_key'] ?? '');
            $status = ($dbSettings['mobile_app_status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
        }

        $viewName = $isPrivileged ? 'api-docs' : 'api-docs-public';

        return view($viewName, [
            'mobileApiKey' => $apiKey,
            'mobileApiStatus' => $status,
            'isPublicView' => !$isPrivileged,
        ]);
    }

    public function updateStatus(): RedirectResponse
    {
        $this->authorizeAccess();

        $status = request()->input('mobile_app_status');
        if (!in_array($status, ['active', 'inactive'], true)) {
            return redirect()->route('api-docs')->with('error', __('Invalid mobile app status.'));
        }

        $this->upsertSetting('mobile_app_status', $status);

        return redirect()->route('api-docs')->with('success', __('Mobile app status updated successfully.'));
    }

    public function generateKey(): RedirectResponse
    {
        $this->authorizeAccess();

        $apiKey = 'hrms_' . Str::lower(Str::random(40));

        $this->upsertSetting('mobile_app_api_key', $apiKey);

        $existingStatus = DB::table('settings')
            ->where('created_by', Auth::user()->creatorId())
            ->where('name', 'mobile_app_status')
            ->value('value');

        if (empty($existingStatus)) {
            $this->upsertSetting('mobile_app_status', 'active');
        }

        return redirect()->route('api-docs')->with('success', __('New mobile app API key generated successfully.'));
    }

    protected function upsertSetting(string $name, string $value): void
    {
        DB::table('settings')->updateOrInsert(
            [
                'created_by' => Auth::user()->creatorId(),
                'name' => $name,
            ],
            [
                'value' => $value,
            ]
        );
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->type, ['company', 'super admin'], true)) {
            abort(403);
        }
    }
}
