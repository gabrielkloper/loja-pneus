<?php

namespace App\Providers;

use App\Payments\FakeGateway;
use App\Payments\PaymentGateway;
use App\Support\Cart;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Quando alguma classe pede um PaymentGateway no construtor, o
        // container entrega a implementação escolhida em config/services.php.
        // singleton = uma instância só por requisição.
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $driver = config('services.payments.driver');

            // Trava de segurança: o gateway falso nunca roda em produção.
            if ($driver === 'fake' && $this->app->isProduction()) {
                throw new InvalidArgumentException('O driver de pagamento "fake" não pode ser usado em produção.');
            }

            return match ($driver) {
                'fake' => new FakeGateway((string) config('services.payments.fake.webhook_secret')),
                default => throw new InvalidArgumentException("Driver de pagamento desconhecido: {$driver}"),
            };
        });

        $this->app->singleton(Cart::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimits();
    }

    /**
     * Limites de requisições por minuto (ADR-0008). Bots abusivos esbarram
     * aqui antes de chegar às regras de negócio.
     */
    protected function configureRateLimits(): void
    {
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
