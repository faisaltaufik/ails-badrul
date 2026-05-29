<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BadrulWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly BadrulWorkflowService $workflow)
    {
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', $this->loginViewData());
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['username' => 'Username atau password salah.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $this->workflow->ensureUserProjects($user);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function dashboard(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function loginViewData(): array
    {
        return [
            'pageTitle' => 'Login AILS',
            'loginBranding' => [
                'title' => 'PjBL BADRUL',
                'subtitle' => 'Berbantuan AILS',
                'supportingText' => 'Pemrograman Visual',
            ],
            'loginFormMeta' => [
                'kicker' => 'Masuk ke workspace AILS',
                'forgotPasswordLabel' => 'Lupa Password?',
                'forgotPasswordUrl' => '#',
                'noteTitle' => 'Gunakan akun yang terdaftar',
                'noteBody' => 'Masuk menggunakan username dan password yang diberikan agar progres proyek BADRUL Anda tetap tersimpan.',
            ],
        ];
    }
}