<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PermissionAssignment;
use Illuminate\Support\Facades\Auth;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * Expected middleware usage: \"permission:module,action\"
     * where module is e.g. 'orders' and action is 'manage' (or any defined action).
     */
    public function handle(Request $request, Closure $next, $module, $action)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }

        // Build permission string "module:action"
        $permission = "{$module}:{$action}";

        // First, check user specific assignments
        $userPermission = PermissionAssignment::where('user_id', $user->id)
            ->where('modulo', $module)
            ->where('accion', $action)
            ->first();
        if ($userPermission) {
            return $userPermission->permitido ? $next($request) : response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
        }

        // Then, check role assignments (user may have multiple roles via role relationship)
        $roles = $user->roles ?? [];
        foreach ($roles as $role) {
            $rolePermission = PermissionAssignment::where('role_id', $role->id)
                ->where('modulo', $module)
                ->where('accion', $action)
                ->first();
            if ($rolePermission) {
                return $rolePermission->permitido ? $next($request) : response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
            }
        }

        // If no assignment found, allow access (as per spec)
        return $next($request);
    }
}
?>
