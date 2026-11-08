<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege as rotas do painel do vendedor: quem ainda não tem loja é
 * levado para o cadastro de loja. Registrado como alias "seller" em
 * bootstrap/app.php.
 */
class EnsureUserHasSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->seller === null) {
            return redirect()->route('seller.onboarding.create');
        }

        return $next($request);
    }
}
