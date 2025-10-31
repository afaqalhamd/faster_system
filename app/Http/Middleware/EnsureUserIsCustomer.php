<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Party\Party;

class EnsureUserIsCustomer
{
    /**
     * Handle an incoming request.
     *
     * Verify that the authenticated user is a Party (customer) and not a User (staff).
     * Also check that the customer account is active.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if the authenticated user is a Party instance
        if (!$user instanceof Party) {
            return response()->json([
                'status' => false,
                'message' => 'الوصول غير مصرح. يتطلب مصادقة العميل.'
            ], 403);
        }

        // Check if the customer account is active
        if (!$user->status) {
            return response()->json([
                'status' => false,
                'message' => 'حسابك غير نشط. يرجى التواصل مع الدعم الفني.'
            ], 403);
        }

        return $next($request);
    }
}
