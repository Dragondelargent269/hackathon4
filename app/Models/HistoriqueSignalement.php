<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueSignalement extends Model
{
    protected $table = 'historique_signalements';

    protected $fillable = ['user_id', 'statut', 'commentaire'];

    public function signalement(): BelongsTo
    {
        return $this->belongsTo(Signalement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function auteur(): Attribute
    {
        return Attribute::get(fn () => $this->statut === 'nouveau'
            ? 'Citoyen'
            : ($this->user?->name ?? 'Équipe CleanComores'));
    }
}