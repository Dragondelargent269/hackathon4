<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Points de dépôt / centres de collecte affichés sur la carte (gérés dans /admin)
        Schema::create('points_collecte', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('commune', 100);
            $table->string('zone', 20)->nullable()->index();
            $table->string('adresse', 255)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('dechets_acceptes', 255)->nullable();
            $table->string('horaires', 150)->nullable();
            $table->boolean('actif')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_collecte');
    }
};
