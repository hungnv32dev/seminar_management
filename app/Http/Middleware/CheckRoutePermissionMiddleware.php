<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoutePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact administrator.');
        }

        // Get current route name
        $routeName = $request->route()->getName();
        
        // Skip permission check for certain routes (login, logout, etc.)
        $skipRoutes = ['login', 'logout', 'password.request', 'password.email', 'password.reset', 'password.update'];
        if (in_array($routeName, $skipRoutes)) {
            return $next($request);
        }

        // Check if user has role and permission for this route
        if ($user->role && $user->role->hasPermission($routeName)) {
            return $next($request);
        }

        // Return 403 Forbidden with meaningful message
        abort(403, 'You do not have permission to access this page. Required permission: ' . $routeName);
    }
}
