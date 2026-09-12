<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Orders\FakePaymentGateway;
use App\Modules\Orders\PaymentGatewayInterface;
use App\Modules\Orders\RazorpayPaymentGateway;
use App\Shared\CurrentTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentTenant::class);

        $this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
            $driver = config('eventflow.payments.driver', 'fake');

            return match ($driver) {
                'razorpay' => $this->app->make(RazorpayPaymentGateway::class),
                default => $this->app->make(FakePaymentGateway::class),
            };
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(config('eventflow.rate_limits.login.max_attempts'))
                ->by($email.'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(config('eventflow.rate_limits.api.max_attempts'))
                ->by('api:'.$key);
        });
    }
}
