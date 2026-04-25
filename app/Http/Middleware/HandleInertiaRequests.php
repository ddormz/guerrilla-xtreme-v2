<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'blader_name' => $request->user()->blader_name,
                    'role' => $request->user()->role->value ?? $request->user()->role,
                    'avatar_url' => $request->user()->avatar_url,
                ] : null,
            ],
            'site_settings' => fn () => app(\App\Services\SettingsService::class)->getSettings(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'troll_message' => fn () => $request->session()->get('troll_message'),
                '_shadow_banned' => fn () => $request->session()->get('_shadow_banned'),
            ],
        ];
    }
}
