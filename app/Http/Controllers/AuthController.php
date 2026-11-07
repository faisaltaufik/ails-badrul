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

    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User|null $user */
            $user = $request->user();

            return $this->redirectToDashboard($request, $user);
        }

        return view('auth.login', $this->loginViewData());
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            /** @var User|null $user */
            $user = $request->user();

            return $this->redirectToDashboard($request, $user);
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

        return $this->redirectToDashboard($request, $user);
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

    private function redirectToDashboard(Request $request, ?User $user): RedirectResponse
    {
        $dashboardUrl = route('dashboard', $user ? $this->workflow->loginDashboardQuery($user) : []);
        $intendedUrl = $request->session()->pull('url.intended');

        if (is_string($intendedUrl) && $intendedUrl !== '' && ! $this->shouldReplacePlainDashboardIntendedUrl($intendedUrl)) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->to($dashboardUrl);
    }

    private function shouldReplacePlainDashboardIntendedUrl(string $intendedUrl): bool
    {
        $dashboardPath = trim((string) parse_url(route('dashboard'), PHP_URL_PATH), '/');
        $intendedPath = trim((string) parse_url($intendedUrl, PHP_URL_PATH), '/');
        $intendedQuery = (string) parse_url($intendedUrl, PHP_URL_QUERY);

        return $intendedPath === $dashboardPath && $intendedQuery === '';
    }
}