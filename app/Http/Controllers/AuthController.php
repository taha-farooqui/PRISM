<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\VerificationCodeMail;
use App\Services\BrevoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the sign in form.
     */
    public function showSignIn()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.signin');
    }

    /**
     * Handle sign in request.
     */
    public function signIn(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email.']);
        }

        if (!$user->password) {
            return back()->withErrors(['email' => 'This account uses Google sign-in. Please use the Google button.']);
        }

        if (!$user->is_verified) {
            // Generate new verification code and redirect to verify
            $this->generateVerificationCode($user);

            return redirect()->route('verify-email', ['email' => $user->email])
                ->with('error', 'Please verify your email first.');
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['password' => 'Invalid password.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show the sign up form.
     */
    public function showSignUp()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.signup');
    }

    /**
     * Handle sign up request.
     */
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_verified' => false,
        ]);

        // Generate and send verification code
        $this->generateVerificationCode($user);

        return redirect()->route('verify-email', ['email' => $user->email]);
    }

    /**
     * Show the email verification form.
     */
    public function showVerifyEmail(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('signup')->with('error', 'Email address is required.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('signup')->with('error', 'No account found with this email.');
        }

        if ($user->is_verified) {
            return redirect()->route('signin')->with('success', 'Email already verified. Please sign in.');
        }

        return view('auth.verify-email', ['email' => $email]);
    }

    /**
     * Handle email verification.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|array|size:6',
            'code.*' => 'required|string|size:1',
        ]);

        $code = implode('', $request->code);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['code' => 'No account found with this email.']);
        }

        if ($user->is_verified) {
            return redirect()->route('signin')->with('success', 'Email already verified. Please sign in.');
        }

        if ($user->verification_code !== $code) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        if ($user->verification_code_expires_at < now()) {
            return back()->withErrors(['code' => 'Verification code has expired. Please request a new one.']);
        }

        // Verify the user
        $user->update([
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Email verified successfully!');
    }

    /**
     * Resend verification code.
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'No account found with this email.');
        }

        if ($user->is_verified) {
            return redirect()->route('signin')->with('success', 'Email already verified. Please sign in.');
        }

        // Generate and send new verification code
        $this->generateVerificationCode($user);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin');
    }

    /**
     * Generate and send verification code.
     */
    private function generateVerificationCode(User $user)
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        // Send via Brevo API (more reliable than SMTP). Fall back to log if it fails.
        try {
            $brevo = new BrevoMailService();
            $sent = $brevo->sendVerificationCode($user->email, $user->name ?? '', $code);
            if (!$sent) {
                Log::info("Verification code for {$user->email}: {$code}");
            }
        } catch (\Throwable $e) {
            Log::warning("Verification email send failed for {$user->email}: " . $e->getMessage());
            Log::info("Verification code for {$user->email}: {$code}");
        }
    }
}
