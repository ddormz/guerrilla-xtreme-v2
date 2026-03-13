<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminMemberController extends Controller
{
    public function index(Request $request): Response
    {
        $members = TeamMember::orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'users' => \App\Models\User::where('role', 'miembro_gx')->orderBy('name')->get(['id', 'name', 'blader_name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id|unique:team_members,user_id',
            'name' => 'required|string|max:100',
            'blader_name' => 'nullable|string|max:100',
            'role_title' => 'required|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'tiktok' => 'nullable|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'lock_chip_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $this->storeCenteredSquare($request->file('photo'), 'miembros');
        }

        if ($request->hasFile('lock_chip_photo')) {
            $validated['lock_chip_photo'] = $request->file('lock_chip_photo')->store('miembros', 'public');
        }

        $member = TeamMember::create($validated);

        AuditLogger::log('create_team_member', 'TeamMember', $member->id, [
            'name' => $member->name,
        ]);

        return back()->with('success', 'Miembro agregado exitosamente al roster.');
    }

    public function update(Request $request, TeamMember $member)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id|unique:team_members,user_id,' . $member->id,
            'name' => 'required|string|max:100',
            'blader_name' => 'nullable|string|max:100',
            'role_title' => 'required|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'tiktok' => 'nullable|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'lock_chip_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $validated['photo_path'] = $this->storeCenteredSquare($request->file('photo'), 'miembros');
        }

        if ($request->hasFile('lock_chip_photo')) {
            if ($member->lock_chip_photo && Storage::disk('public')->exists($member->lock_chip_photo)) {
                Storage::disk('public')->delete($member->lock_chip_photo);
            }
            $validated['lock_chip_photo'] = $request->file('lock_chip_photo')->store('miembros', 'public');
        }

        $member->update($validated);

        AuditLogger::log('update_team_member', 'TeamMember', $member->id, [
            'name' => $member->name,
        ]);

        return back()->with('success', 'Roster actualizado correctamente.');
    }

    public function destroy(TeamMember $member)
    {
        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        if ($member->lock_chip_photo && Storage::disk('public')->exists($member->lock_chip_photo)) {
            Storage::disk('public')->delete($member->lock_chip_photo);
        }

        $memberId = $member->id;
        $member->delete();

        AuditLogger::log('delete_team_member', 'TeamMember', $memberId);

        return back()->with('success', 'Miembro eliminado del roster.');
    }

    public function toggleActive(TeamMember $member)
    {
        $member->update(['is_active' => !$member->is_active]);

        $status = $member->is_active ? 'activado' : 'desactivado';

        AuditLogger::log('toggle_team_member_visibility', 'TeamMember', $member->id, [
            'is_active' => $member->is_active,
        ]);

        return back()->with('success', "Visibilidad del miembro {$status}.");
    }

    private function storeCenteredSquare(UploadedFile $file, string $directory): string
    {
        $content = file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($content);

        if (!$source) {
            return $file->store($directory, 'public');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $srcX = (int) floor(($width - $side) / 2);
        $srcY = (int) floor(($height - $side) / 2);

        $target = imagecreatetruecolor(800, 800);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, 800, 800, $side, $side);

        $filename = $directory . '/' . Str::uuid()->toString() . '.webp';
        $absolutePath = Storage::disk('public')->path($filename);

        imagewebp($target, $absolutePath, 90);
        imagedestroy($source);
        imagedestroy($target);

        return $filename;
    }
}
