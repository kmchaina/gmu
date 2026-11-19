<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\NreimsOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Everyone signs in through NREIMS. The local email-and-password form stays
 * only as the GMU administrator's fallback for when NREIMS is unavailable.
 *
 * NREIMS only proves who the person is. Everything else (roles, profile,
 * reviewer assignments) lives here, and GMU can add people before they have
 * ever signed in.
 *
 * Accounts are matched on the NREIMS user id. An email is used only once, to
 * link an account GMU added in advance, and only when NREIMS has verified that
 * email and the account is not already linked to another NREIMS user. An
 * unverified NREIMS email never gets in at all.
 */
class NreimsLoginController extends Controller
{
    private const STATE_KEY = 'nreims_oauth_state';

    public function __construct(private NreimsOAuth $nreims) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->nreims->enabled(), 404);

        $state = Str::random(40);
        $request->session()->put(self::STATE_KEY, $state);

        return redirect()->away($this->nreims->authorizeUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->nreims->enabled(), 404);

        $expected = $request->session()->pull(self::STATE_KEY);
        $state    = (string) $request->query('state', '');

        if (! is_string($expected) || $state === '' || ! hash_equals($expected, $state)) {
            return $this->fail('Your sign-in link expired. Please try again.');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->fail('Sign-in was cancelled in NREIMS.');
        }

        try {
            $remote = $this->nreims->user($this->nreims->accessToken((string) $request->query('code')));
        } catch (Throwable $e) {
            Log::warning('NREIMS sign-in failed', ['reason' => $e->getMessage()]);

            return $this->fail('We could not reach NREIMS to sign you in. Please try again in a few minutes.');
        }

        if (! $remote['email']) {
            return $this->fail('Your NREIMS account has no email address. Add one in NREIMS, then sign in again.');
        }

        if (! $remote['verified']) {
            return $this->fail('NREIMS has not confirmed your email address yet. Open the confirmation link NREIMS emailed you, then sign in again.');
        }

        $user     = User::withTrashed()->where('nreims_id', $remote['id'])->first();
        $byEmail  = $user ? null : User::withTrashed()->where('email', $remote['email'])->first();

        if ($byEmail && $byEmail->nreims_id !== null) {
            // The address belongs to an account another NREIMS user already holds.
            Log::notice('NREIMS sign-in email belongs to a different NREIMS user', ['nreims_id' => $remote['id']]);

            return $this->fail('A NIMR Grants account already uses ' . $remote['email'] . ' with a different NREIMS account. Contact the Grants Management Unit.');
        }

        $account = $user ?? $byEmail;

        if ($account?->trashed() || ($account && ! $account->is_active)) {
            return $this->fail('Your NIMR Grants account has been deactivated. Contact the Grants Management Unit.');
        }

        if ($user) {
            $this->refreshAccount($user, $remote);
        } elseif ($byEmail) {
            $user = $this->linkAccount($byEmail, $remote);
        } else {
            $user = $this->createAccount($remote);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $user->needsProfileCompletion()
            ? redirect()->route('profile.complete')
            : redirect()->intended(route('dashboard', absolute: false));
    }

    private function createAccount(array $remote): User
    {
        $user = new User([
            'name'     => $remote['name'] ?: $remote['email'],
            'email'    => $remote['email'],
            'phone'    => $remote['phone'],
            // Nobody knows this password: NREIMS accounts never sign in locally.
            'password' => Str::random(64),
            'role_id'  => Role::where('name', config('services.nreims.default_role'))->value('id'),
            'is_active' => true,
        ]);
        $user->forceFill(['nreims_id' => $remote['id'], 'email_verified_at' => now()])->save();

        return $user;
    }

    /**
     * First NREIMS sign-in by someone GMU added in advance: tie the account to
     * this NREIMS user for good. Role and details set by GMU are kept.
     *
     * Staff and reviewers GMU added skip the first-sign-in profile form (it asks
     * for NIMR appointment details an external reviewer does not have); only
     * applicants are asked, because the seed grant's eligibility rests on it.
     * From now on they sign in through NREIMS, so a password GMU set is moot.
     */
    private function linkAccount(User $user, array $remote): User
    {
        $isApplicant = $user->role?->name === config('services.nreims.default_role');

        $user->forceFill([
            'nreims_id'            => $remote['id'],
            'email_verified_at'    => $user->email_verified_at ?? now(),
            'must_change_password' => false,
            'profile_completed_at' => $user->profile_completed_at ?? ($isApplicant ? null : now()),
        ])->save();

        Log::info('Portal account linked to NREIMS on first sign-in', ['user_id' => $user->id, 'nreims_id' => $remote['id']]);

        $this->refreshAccount($user, $remote);

        return $user;
    }

    /** NREIMS owns name and email; the portal keeps whatever the user set for phone. */
    private function refreshAccount(User $user, array $remote): void
    {
        if ($remote['name'] !== '') {
            $user->name = $remote['name'];
        }

        $emailTaken = $remote['email'] && User::withTrashed()
            ->where('email', $remote['email'])->whereKeyNot($user->getKey())->exists();
        if ($remote['email'] && ! $emailTaken) {
            $user->email = $remote['email'];
        }

        if (blank($user->phone) && filled($remote['phone'])) {
            $user->phone = $remote['phone'];
        }

        if ($user->isDirty()) {
            $user->save();
        }
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('sso_error', $message);
    }
}
