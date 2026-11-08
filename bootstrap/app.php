<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserHasSeller;
use App\Payments\InvalidWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apelido para usar nas rotas: ->middleware('seller')
        $middleware->alias([
            'seller' => EnsureUserHasSeller::class,
        ]);

        // O gateway não tem como enviar o token CSRF do nosso formulário.
        // No lugar dele, o webhook é protegido pela assinatura HMAC.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Regra de negócio quebrada vira mensagem amigável, nunca erro 500.
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        });

        $exceptions->render(fn (InvalidWebhookSignature $e) => response()->json(['message' => $e->getMessage()], 401));

        // Regras de negócio são esperadas; não precisam poluir o log de erros.
        $exceptions->dontReport([
            BusinessRuleException::class,
        ]);
    })->create();
