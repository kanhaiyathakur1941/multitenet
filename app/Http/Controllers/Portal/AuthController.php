<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Modules\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            if (auth()->user()?->isCustomer()) {
                return redirect()->route('portal.dashboard');
            }

            return redirect('/admin');
        }

        return view('portal.auth.login');
    }

    public function store(LoginRequest $request, AuthService $auth): RedirectResponse
    {
        $user = $auth->authenticateWeb(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (! $user->isCustomer()) {
            $auth->logoutWeb();

            throw ValidationException::withMessages([
                'email' => ['This login is for customer accounts only. Staff should use the admin panel.'],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request, AuthService $auth): RedirectResponse
    {
        $auth->logoutWeb();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
