<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTableSession
{
    /**
     * Verify that the client's encrypted session cookie/session token matches the active order,
     * or that the request is from an authorized staff member or signed receipt link.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orderParam = $request->route('order') ?? $request->route('order_id');

        if ($orderParam === null) {
            return $next($request);
        }

        $order = $orderParam instanceof Order
            ? $orderParam
            : Order::findOrFail($orderParam);

        $user = $request->user();
        if ($user !== null && $user->is_active && $user->hasRole([Role::ADMIN, Role::MANAGER, Role::STAFF, Role::CASHIER])) {
            return $next($request);
        }

        if ($request->hasValidSignature()) {
            return $next($request);
        }

        $cookieName = (string) config('hotel.table_session_cookie', 'hotel_table_session_token');
        $clientToken = $request->cookie($cookieName)
            ?? $request->session()->get($cookieName)
            ?? $request->header('X-Table-Session-Token');

        if (! is_string($clientToken) || ! hash_equals((string) $order->session_token, $clientToken)) {
            abort(403, 'Invalid or expired table session token.');
        }

        return $next($request);
    }
}
