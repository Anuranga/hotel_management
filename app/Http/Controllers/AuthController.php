<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Display the portal home and signed QR table launcher.
     */
    public function home(): View
    {
        $tables = RestaurantTable::query()
            ->with('activeOrder')
            ->orderBy('table_number')
            ->get();

        $staffUsers = User::query()
            ->active()
            ->with('role')
            ->orderBy('role_id')
            ->get();

        return view('welcome', [
            'tables' => $tables,
            'staffUsers' => $staffUsers,
            'hotelName' => (string) config('hotel.name', 'Grand Ceylon Hotel & Resort'),
        ]);
    }

    /**
     * Show the staff login form.
     */
    public function showLoginForm(): View
    {
        $demoUsers = User::query()
            ->active()
            ->with('role')
            ->orderBy('role_id')
            ->get();

        return view('auth.login', [
            'demoUsers' => $demoUsers,
            'hotelName' => (string) config('hotel.name', 'Grand Ceylon Hotel & Resort'),
        ]);
    }

    /**
     * Authenticate a staff/kitchen/cashier/manager/admin user.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();
            $user->loadMissing('role');

            return redirect()->intended($this->redirectPathForRole($user->role?->name));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match an active hotel account.',
        ])->onlyInput('email');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Resolve default landing route based on role name.
     */
    private function redirectPathForRole(?string $roleName): string
    {
        return match ($roleName) {
            Role::ADMIN, Role::MANAGER => route('admin.reports'),
            Role::CASHIER => route('cashier.billing'),
            Role::KITCHEN => route('kitchen.display'),
            Role::STAFF => route('staff.dashboard'),
            default => route('home'),
        };
    }
}
