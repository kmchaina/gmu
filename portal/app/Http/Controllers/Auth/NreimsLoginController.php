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
 * Accounts are matched on the NREIMS user id, never on email alone: an email
 * typed into NREIMS must not be enough to take over a portal account.
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

        $user = User::withTrashed()->where('nreims_id', $remote['id'])->first();

        if ($user?->trashed() || ($user && ! $user->is_active)) {
            return $this->fail('Your NIMR Grants account has been deactivated. Contact the Grants Management Unit.');
        }

        if (! $user) {
            if (! $remote['email']) {
                return $this->fail('Your NREIMS account has no email address. Add one in NREIMS, then sign in again.');
            }

            if (User::withTrashed()->where('email', $remote['email'])->exists()) {
                Log::notice('NREIMS sign-in matched an unlinked portal email', ['nreims_id' => $remote['id']]);

                return $this->fail('A NIMR Grants account already uses ' . $remote['email'] . '. Ask the Grants Management Unit to link it to your NREIMS account.');
            }

            $user = $this->createAccount($remote);
        } else {
            $this->refreshAccount($user, $remote);
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
