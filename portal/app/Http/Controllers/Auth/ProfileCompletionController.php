<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The one-time form a member of staff sees after their first NREIMS sign-in.
 * Name and email come from NREIMS and are shown read-only.
 */
class ProfileCompletionController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isNreimsLinked()) {
            return redirect()->route('dashboard');
        }

        return view('auth.complete-profile', [
            'user'         => $user,
            'designations' => config('gmu.designations'),
            'centres'      => config('gmu.centres'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isNreimsLinked(), 404);

        $data = $request->validate([
            'designation'            => ['required', 'string', Rule::in(config('gmu.designations'))],
            'research_center'        => ['required', 'string', Rule::in(config('gmu.centres'))],
            'department'             => ['required', 'string', 'max:255'],
            'phone'                  => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{9,}$/'],
            'employment_type'        => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'first_appointment_date' => ['required', 'date', 'before_or_equal:today', 'after:1960-01-01'],
        ], [
            'department.required' => 'Enter your section.',
            'research_center.required' => 'Choose your centre.',
            'phone.regex' => 'Enter a phone number with digits only, for example +255 712 345 678.',
            'first_appointment_date.before_or_equal' => 'The date of first appointment cannot be in the future.',
        ]);

        $user->fill($data);
        $user->profile_completed_at ??= now();
        $user->save();

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('success', 'Thank you. Your details are saved.');
    }
}
