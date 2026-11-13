<?php

namespace App\Providers;

use App\Services\Paiement\PasserelleMobile;
use App\Services\Paiement\SimulationPasserelle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Paiement mobile : seule la simulation existe. Une vraie passerelle ne sera
        // ajoutée qu'après validation de l'équipe (contrat marchand, clés d'API).
        $this->app->bind(PasserelleMobile::class, fn () => match (config('cleancomores.paiement.passerelle')) {
            'simulation' => new SimulationPasserelle,
            default => throw new \RuntimeException('Passerelle de paiement inconnue : '.config('cleancomores.paiement.passerelle')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Schema::defaultStringLength(191);
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
