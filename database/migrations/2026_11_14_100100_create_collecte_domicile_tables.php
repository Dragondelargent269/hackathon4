<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offres de collecte à domicile : gérées dans /admin, aucun tarif dans le code
        Schema::create('formules', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('prix');                  // en KMF
            $table->unsignedSmallInteger('duree_jours');
            $table->unsignedSmallInteger('collectes_incluses');
            $table->json('services')->nullable();             // liste de textes
            $table->boolean('active')->default(true)->index();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formule_id')->nullable()->constrained()->nullOnDelete();

            // Copie de la formule au moment de l'achat (un changement de tarif ne modifie pas l'historique)
            $table->string('formule_nom', 100);
            $table->unsignedInteger('prix');
            $table->unsignedSmallInteger('duree_jours');
            $table->unsignedSmallInteger('collectes_incluses');

            // Adresse de collecte
            $table->string('adresse', 255);
            $table->string('commune', 100);
            $table->string('zone', 20)->nullable()->index();
            $table->string('repere', 255)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('telephone', 30);

            $table->string('statut', 30)->default('en_attente_paiement')->index();
            $table->timestamp('debut_at')->nullable();
            $table->timestamp('fin_at')->nullable();
            $table->unsignedSmallInteger('collectes_restantes')->default(0);
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();         // numéro du reçu
            $table->string('jeton', 64)->unique();             // secret contenu dans le QR code
            $table->foreignId('abonnement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('montant');
            $table->string('devise', 3)->default('KMF');
            $table->string('operateur', 30);
            $table->string('telephone', 30);
            $table->boolean('simulation')->default(true);
            $table->string('statut', 20)->default('en_attente')->index();
            $table->string('transaction_externe', 100)->nullable()->unique();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirme_at')->nullable();
            $table->string('motif_refus', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('collectes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paiement_id')->constrained()->cascadeOnDelete();   // reçu utilisé
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statut', 20)->default('demandee')->index();
            $table->string('creneau', 20)->default('indifferent');
            $table->string('note_citoyen', 500)->nullable();
            $table->string('note_agent', 500)->nullable();
            $table->timestamp('effectuee_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collectes');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('abonnements');
        Schema::dropIfExists('formules');
    }
};
