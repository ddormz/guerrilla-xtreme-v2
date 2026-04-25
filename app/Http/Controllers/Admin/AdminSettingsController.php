<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'modules' => SiteSetting::getModuleSettings(),
        ]);
    }

    public function updateModules(Request $request)
    {
        $validated = $request->validate([
            'rifas' => 'required|boolean',
            'torneos' => 'required|boolean',
            'liga' => 'required|boolean',
            'ranking' => 'required|boolean',
        ]);

        foreach ($validated as $module => $enabled) {
            SiteSetting::setValue("module_{$module}_enabled", $enabled ? '1' : '0');
        }

        return back()->with('success', 'Módulos actualizados correctamente.');
    }
}
