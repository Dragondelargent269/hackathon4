<?php

namespace App\Models;

use App\Enums\StatutSignalement;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueSignalement extends Model
{
    protected $table = 'historique_signalements';

    protected $fillable = ['user_id', 'statut', 'commentaire'];

    protected function casts(): array
    {
        return [
            'statut' => StatutSignalement::class,
        ];
    }

    public function signalement(): BelongsTo
    {
        return $this->belongsTo(Signalement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nom affiché dans la frise de suivi (le citoyen reste anonyme).
     */
    protected function auteur(): Attribute
    {
        return Attribute::get(fn () => $this->statut === StatutSignalement::Nouveau
            ? 'Citoyen'
            : ($this->user?->name ?? 'Équipe CleanComores'));
    }
}