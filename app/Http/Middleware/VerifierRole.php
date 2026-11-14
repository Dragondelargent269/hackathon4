<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierRole
{
    /**
     * Laisse passer uniquement les utilisateurs dont le rôle est dans la liste.
     * Utilisation : ->middleware('role:responsable') ou 'role:responsable,agent'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Cet espace est réservé à un autre rôle.');
        }

        return $next($request);
    }
}