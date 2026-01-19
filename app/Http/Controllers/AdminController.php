<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiToken;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Display API tokens management page
     */
    public function tokens()
    {
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();
        return view('admin.tokens', compact('tokens'));
    }

    /**
     * Create a new API token
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expires_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = $request->expires_days 
            ? now()->addDays($request->expires_days) 
            : now()->addYear();

        ApiToken::create([
            'name' => $request->name,
            'token' => $token,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);

        return redirect()->route('admin.tokens')
            ->with('success', 'Token berhasil dibuat!')
            ->with('newToken', $token);
    }

    /**
     * Revoke (deactivate) an API token
     */
    public function revokeToken($id)
    {
        $token = ApiToken::findOrFail($id);
        $token->update(['is_active' => false]);

        return redirect()->route('admin.tokens')
            ->with('success', 'Token berhasil direvoke.');
    }

    /**
     * Delete an API token permanently
     */
    public function deleteToken($id)
    {
        $token = ApiToken::findOrFail($id);
        $token->delete();

        return redirect()->route('admin.tokens')
            ->with('success', 'Token berhasil dihapus.');
    }

    /**
     * Display admin users management page
     */
    public function users()
    {
        $users = \App\Models\User::orderBy('name', 'asc')->get();
        return view('admin.users', compact('users'));
    }

    /**
     * Create a new admin user
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return redirect()->route('admin.users')
            ->with('success', 'User admin baru berhasil ditambahkan.');
    }

    /**
     * Update an admin user's name and email
     */
    public function updateUser(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.users')
            ->with('success', "Data user {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset a user's password
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::findOrFail($id);
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return redirect()->route('admin.users')
            ->with('success', "Password untuk {$user->name} berhasil di-reset.");
    }

    /**
     * Delete an admin user
     */
    public function deleteUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === \Illuminate\Support\Facades\Auth::id()) {
            return redirect()->route('admin.users')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', 'User admin berhasil dihapus.');
    }
}
