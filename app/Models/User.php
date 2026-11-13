<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $commune
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'commune', 'telephone'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const ROLE_CITOYEN = 'citoyen';
    public const ROLE_AGENT = 'agent';
    public const ROLE_RESPONSABLE = 'responsable';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    // ---------------------------------------------------------------
    // Rôles
    // ---------------------------------------------------------------

    public function estCitoyen(): bool
    {
        return $this->role === self::ROLE_CITOYEN;
    }

    public function estAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function estResponsable(): bool
    {
        return $this->role === self::ROLE_RESPONSABLE;
    }

    // ---------------------------------------------------------------
    // Filament : seuls les responsables entrent dans /admin
    // ---------------------------------------------------------------

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->estResponsable();
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    /**
     * Signalements créés par ce citoyen.
     */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class, 'user_id');
    }

    /**
     * Signalements affectés à cet agent.
     */
    public function interventions(): HasMany
    {
        return $this->hasMany(Signalement::class, 'agent_id');
    }

    /**
     * Abonnements de collecte à domicile de ce citoyen.
     */
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class)->latest('id');
    }

    /**
     * Paiements (reçus) de ce citoyen.
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->latest('id');
    }

    /**
     * Collectes à domicile effectuées par cet agent.
     */
    public function collectesEffectuees(): HasMany
    {
        return $this->hasMany(Collecte::class, 'agent_id');
    }
}