<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();

            // Qui ?
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();

            // Quoi ? (écran « Décrire »)
            $table->string('categorie', 30);
            $table->text('description');

            // Où ? (écran « Localiser »)
            $table->string('commune', 100);
            $table->string('quartier', 150);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo')->nullable();

            // Traitement (responsable et agent)
            $table->string('statut', 20)->default('nouveau')->index();
            $table->string('priorite', 20)->nullable()->index();
            $table->text('consigne')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->text('compte_rendu')->nullable();
            $table->string('photo_resolution')->nullable();
            $table->timestamp('affecte_at')->nullable();
            $table->timestamp('resolu_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};