<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('blader_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString()->through(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'blader_name' => $user->blader_name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'avatar_path' => $user->avatar_path,
            'created_at' => $user->created_at->format('d/m/Y'),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Update the role of the specified user.
     */
    public function updateRole(Request $request, User $user)
    {
        // Prevent changing own role
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        $validated = $request->validate([
            'role' => 'required|in:miembro,miembro_gx,arbitro_gx,admin',
        ]);

        $user->update(['role' => $validated['role']]);

        AuditLogger::log('update_role', 'User', $user->id, ['new_role' => $validated['role']]);

        return back()->with('success', "El rol de {$user->name} ha sido actualizado a {$validated['role']}.");
    }

    /**
 * Reset the password of the specified user.
 */
public function resetPassword(Request $request, User $user)
{
    $tempPassword = \Illuminate\Support\Str::random(10);

    $user->update([
        'password' => Hash::make($tempPassword),
    ]);

    \Illuminate\Support\Facades\Log::info("Password reset for {$user->email}: Temporary password is {$tempPassword}");

    try {
        $body = '<p>Hola <strong>' . htmlspecialchars($user->name) . '</strong>,</p>'
            . '<p>Tu contraseña ha sido restablecida por un administrador en Guerrilla Xtrem.</p>'
            . '<div class="highlight-box">'
            . '<p style="margin:0 0 8px; font-weight:600;">Tu nueva contraseña temporal es:</p>'
            . '<p style="margin:0; font-size:18px; font-weight:700; font-family:monospace; letter-spacing:2px; color:#E10600;">' . htmlspecialchars($tempPassword) . '</p>'
            . '</div>'
            . '<p><strong>⚠️ Importante:</strong> Por seguridad, deberás cambiar esta contraseña la primera vez que inicies sesión.</p>';

        \Illuminate\Support\Facades\Mail::to($user->email)->send(
            new \App\Mail\GxStyledMail(
                subject: 'Contraseña Restablecida - GX',
                heading: 'Restablecimiento de contraseña',
                body: $body,
                ctaText: 'Iniciar Sesión',
                ctaUrl: config('app.url') . '/login',
            )
        );
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Failed to send reset email: " . $e->getMessage());
    }

    return back()->with('success', "La contraseña de {$user->name} ha sido restablecida. Se ha enviado un correo con la nueva contraseña temporal.");
}

    /**
     * Update user details including avatar.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'blader_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:miembro,miembro_gx,arbitro_gx,admin',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Remove the 'avatar' file object from the array before updating the model
        unset($validated['avatar']);

        $user->update($validated);

        AuditLogger::log('update_user', 'User', $user->id, $validated);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        try {
            if ($user->avatar_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
            }

            // Also clean up team member record if exists
            $user->teamMember()?->delete();

            $user->delete();

            AuditLogger::log('delete_user', 'User', $user->id, ['name' => $user->name, 'email' => $user->email]);

            return back()->with('success', 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo eliminar el usuario. Es posible que tenga registros asociados (finanzas, torneos, etc). Error: ' . $e->getMessage());
        }
    }
}
