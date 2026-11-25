<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->load(['roles', 'office', 'department', 'employee.department']);

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_password' => ['nullable', 'current_password'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
        ]);

        // Re-check against the authenticated account instead of trusting a client-side flag.
        if (strcasecmp(trim($data['email']), (string) $user->email) !== 0) {
            $request->validate(['current_password' => ['required', 'current_password']]);
        }

        $before = $user->only(['name', 'email', 'phone']);
        $user->forceFill([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
        ])->save();

        $after = $user->only(['name', 'email', 'phone']);
        if ($before !== $after) {
            Audit::record('profile_updated', 'profile', User::class, $user->id, $before, $after);
        }

        return redirect()->route('profile.edit')->with('success', 'Your profile information has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'different:current_password', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ], [
            'password.regex' => 'Use a password with uppercase and lowercase letters and at least one number.',
            'password.different' => 'Your new password must be different from your current password.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        Audit::record('profile_password_changed', 'profile', User::class, $user->id);

        return redirect()->route('profile.edit')->with('success', 'Your password has been changed successfully.');
    }
}
