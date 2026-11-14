<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Renommer les colonnes selon les noms utilisés par les vues
        Schema::table('signalements', function (Blueprint $table) {
            $table->renameColumn('quartier', 'repere');
            $table->renameColumn('consigne', 'consignes');
        });

        // 2. Zone = île (ngazidja, ndzuani, mwali)
        Schema::table('signalements', function (Blueprint $table) {
            $table->string('zone', 20)->nullable()->index()->after('commune');
        });

        // 3. Informations utilisateur attendues par les vues (profil, agents)
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable()->after('email');
            $table->string('zone', 20)->nullable()->after('commune');
            $table->boolean('notif_email')->default(true);
            $table->boolean('notif_changement_statut')->default(true);
            $table->boolean('notif_resolution')->default(true);
        });

        // 4. Historique des changements de statut (écran « Suivi »)
        Schema::create('historique_signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signalement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('statut', 20);
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_signalements');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'zone', 'notif_email', 'notif_changement_statut', 'notif_resolution']);
        });

        Schema::table('signalements', function (Blueprint $table) {
            $table->dropColumn('zone');
        });

        Schema::table('signalements', function (Blueprint $table) {
            $table->renameColumn('repere', 'quartier');
            $table->renameColumn('consignes', 'consigne');
        });
    }
};