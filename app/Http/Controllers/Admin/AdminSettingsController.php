<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function __construct(private SettingsService $settingsService) {}

    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->settingsService->getSettings()
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'modules.rifas' => 'required|boolean',
            'modules.torneos' => 'required|boolean',
        ]);

        $this->settingsService->updateSettings(['modules' => $validated['modules']]);

        return back()->with('flash', ['success' => 'Configuración actualizada']);
    }
}
