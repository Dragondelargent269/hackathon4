<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Collecte;
use App\Models\Formule;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Collecte à domicile : souscription → paiement (simulation) → reçu → vérification → collecte.
 */
class CollecteDomicileTest extends TestCase
{
    use RefreshDatabase;

    private Formule $formule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formule = Formule::create([
            'nom' => 'Mensuelle', 'slug' => 'mensuelle', 'prix' => 7500,
            'duree_jours' => 30, 'collectes_incluses' => 4, 'services' => ['4 collectes'], 'active' => true,
        ]);
    }

    private function utilisateur(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role, 'commune' => 'Moroni'])->save();

        return $u;
    }

    private function donneesSouscription(array $surcharge = []): array
    {
        return array_merge([
            'adresse' => 'Quartier Magoudjou, maison 12', 'commune' => 'Moroni', 'repere' => 'Portail bleu',
            'latitude' => -11.698, 'longitude' => 43.256, 'telephone' => '+269 321 00 11', 'conditions' => '1',
        ], $surcharge);
    }

    /** Souscription par le citoyen ; renvoie le paiement en attente. */
    private function souscrire(User $citoyen): Paiement
    {
        $this->actingAs($citoyen)
            ->post(route('citoyen.abonnements.store', $this->formule), $this->donneesSouscription())
            ->assertRedirect();

        return Paiement::latest('id')->firstOrFail();
    }

    /** Paiement confirmé par un responsable ; renvoie le paiement. */
    private function abonnementPaye(User $citoyen): Paiement
    {
        $paiement = $this->souscrire($citoyen);
        $paiement->confirmer($this->utilisateur('responsable'));

        return $paiement->fresh();
    }

    public function test_la_souscription_cree_un_abonnement_en_attente_et_ne_paie_rien(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        $paiement = $this->souscrire($citoyen);
        $abonnement = $paiement->abonnement;

        $this->assertSame('en_attente', $paiement->statut);
        $this->assertTrue($paiement->simulation);
        $this->assertSame(7500, $paiement->montant);
        $this->assertMatchesRegularExpression('/^PAY-[2-9A-HJ-NP-Z]{8}$/', $paiement->reference);
        $this->assertSame('en_attente_paiement', $abonnement->statut);
        $this->assertSame('ngazidja', $abonnement->zone);
        $this->assertSame('+2693210011', $abonnement->telephone);
        $this->assertSame(0, $abonnement->collectes_restantes);

        // Avant confirmation, la page n'est pas un reçu et n'a pas de QR code
        $this->get(route('citoyen.paiements.show', $paiement->reference))
            ->assertOk()->assertSee('Demande de paiement')->assertSee('SIMULATION')->assertDontSee('QR code de vérification');
    }

    public function test_la_position_gps_et_l_adresse_sont_obligatoires(): void
    {
        $this->actingAs($this->utilisateur('citoyen'))
            ->post(route('citoyen.abonnements.store', $this->formule), $this->donneesSouscription(['latitude' => null, 'longitude' => null, 'adresse' => '']))
            ->assertSessionHasErrors(['latitude', 'longitude', 'adresse']);
        $this->assertSame(0, Abonnement::count());
    }

    public function test_une_formule_desactivee_ne_peut_pas_etre_souscrite(): void
    {
        $this->formule->update(['active' => false]);
        $this->actingAs($this->utilisateur('citoyen'))
            ->get(route('citoyen.abonnements.create', $this->formule))->assertNotFound();
    }

    public function test_seul_un_responsable_peut_confirmer_un_paiement(): void
    {
        $paiement = $this->souscrire($this->utilisateur('citoyen'));

        $this->actingAs($this->utilisateur('agent'))->post(route('responsable.paiements.confirmer', $paiement))->assertForbidden();
        $this->actingAs($paiement->citoyen)->post(route('responsable.paiements.confirmer', $paiement))->assertForbidden();
        $this->assertSame('en_attente', $paiement->fresh()->statut);

        $responsable = $this->utilisateur('responsable');
        $this->actingAs($responsable)->post(route('responsable.paiements.confirmer', $paiement))->assertSessionHas('success');

        $paiement->refresh();
        $abonnement = $paiement->abonnement;
        $this->assertSame('confirme', $paiement->statut);
        $this->assertSame($responsable->id, $paiement->traite_par);
        $this->assertNotNull($paiement->confirme_at);
        $this->assertSame('actif', $abonnement->statut);
        $this->assertSame(4, $abonnement->collectes_restantes);
        $this->assertSame(30, (int) round($abonnement->debut_at->diffInDays($abonnement->fin_at)));

        // Une seconde confirmation est refusée
        $this->actingAs($responsable)->post(route('responsable.paiements.confirmer', $paiement))->assertSessionHas('error');
    }

    public function test_un_paiement_refuse_peut_etre_relance(): void
    {
        $paiement = $this->souscrire($this->utilisateur('citoyen'));
        $abonnement = $paiement->abonnement;

        // Pas de second paiement tant que le premier est en attente
        $this->actingAs($paiement->citoyen)->post(route('citoyen.abonnements.payer', $abonnement))->assertSessionHas('error');

        $this->actingAs($this->utilisateur('responsable'))
            ->post(route('responsable.paiements.refuser', $paiement), ['motif' => 'Paiement non reçu.'])->assertSessionHas('success');
        $this->assertSame('echoue', $paiement->fresh()->statut);
        $this->assertSame('en_attente_paiement', $abonnement->fresh()->statut);

        $this->actingAs($paiement->citoyen)->post(route('citoyen.abonnements.payer', $abonnement))->assertRedirect();
        $this->assertSame(2, $abonnement->paiements()->count());
    }

    public function test_un_citoyen_ne_voit_pas_l_abonnement_ni_le_recu_d_un_autre(): void
    {
        $paiement = $this->abonnementPaye($this->utilisateur('citoyen'));
        $autre = $this->utilisateur('citoyen');

        $this->actingAs($autre)->get(route('citoyen.paiements.show', $paiement->reference))->assertNotFound();
        $this->actingAs($autre)->get(route('citoyen.abonnements.show', $paiement->abonnement))->assertNotFound();
        $this->actingAs($autre)->post(route('citoyen.collectes.store', $paiement->abonnement), ['creneau' => 'matin'])->assertNotFound();
    }

    public function test_le_recu_confirme_contient_les_informations_et_le_qr_code(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        $paiement = $this->abonnementPaye($citoyen);

        $this->actingAs($citoyen)->get(route('citoyen.paiements.show', $paiement->reference))
            ->assertOk()
            ->assertSee('Reçu de paiement')
            ->assertSee($paiement->reference)
            ->assertSee($citoyen->name)
            ->assertSee('Mensuelle')
            ->assertSee('7 500')
            ->assertSee('Paiement confirmé')
            ->assertSee('QR code de vérification')->assertSee('<rect', false);
    }

    public function test_l_agent_verifie_le_recu_en_base(): void
    {
        $paiement = $this->abonnementPaye($this->utilisateur('citoyen'));
        $agent = $this->utilisateur('agent');

        $this->actingAs($agent)->get($paiement->urlVerification())->assertOk()->assertSee('Reçu valable')->assertSee('QR code authentique');
        $this->actingAs($agent)->get(route('agent.recus.show', ['reference' => $paiement->reference, 'jeton' => 'falsifie']))
            ->assertOk()->assertSee('QR code invalide')->assertDontSee('Reçu valable');
        $this->actingAs($agent)->get(route('agent.recus.show', 'PAY-ZZZZZZZZ'))->assertOk()->assertSee('Reçu introuvable');

        $enAttente = $this->souscrire($this->utilisateur('citoyen'));
        $this->actingAs($agent)->get(route('agent.recus.show', $enAttente->reference))
            ->assertOk()->assertSee('Reçu non valable')->assertDontSee('Reçu valable<');
    }

    public function test_une_collecte_ne_peut_pas_etre_effectuee_deux_fois(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        $paiement = $this->abonnementPaye($citoyen);
        $abonnement = $paiement->abonnement;

        $this->actingAs($citoyen)->post(route('citoyen.collectes.store', $abonnement), ['creneau' => 'matin', 'note_citoyen' => '3 sacs'])->assertSessionHas('success');
        // Une seule demande en attente à la fois
        $this->actingAs($citoyen)->post(route('citoyen.collectes.store', $abonnement), ['creneau' => 'matin'])->assertSessionHas('error');
        $collecte = Collecte::firstOrFail();
        $this->assertSame($paiement->id, $collecte->paiement_id);

        $agent = $this->utilisateur('agent');
        $this->actingAs($agent)->get(route('agent.collectes.index'))->assertOk()->assertSee($abonnement->adresse);
        $this->actingAs($agent)->post(route('agent.collectes.confirmer', $collecte), ['note_agent' => '3 sacs récupérés'])->assertSessionHas('success');

        $collecte->refresh();
        $this->assertSame('effectuee', $collecte->statut);
        $this->assertSame($agent->id, $collecte->agent_id);
        $this->assertSame(3, $abonnement->fresh()->collectes_restantes);

        // Deuxième confirmation (même agent ou un autre) : refusée, rien n'est décompté
        $this->actingAs($agent)->post(route('agent.collectes.confirmer', $collecte))->assertSessionHas('error');
        $this->assertSame(3, $abonnement->fresh()->collectes_restantes);
        $autreAgent = $this->utilisateur('agent');
        $this->actingAs($autreAgent)->post(route('agent.collectes.confirmer', $collecte))->assertNotFound();
        $this->assertSame(3, $abonnement->fresh()->collectes_restantes);

        // Historique de l'agent
        $this->actingAs($agent)->get(route('agent.collectes.historique'))->assertOk()->assertSee($paiement->reference);
    }

    public function test_les_collectes_s_arretent_quand_l_abonnement_est_epuise_ou_expire(): void
    {
        $this->formule->update(['collectes_incluses' => 1]);
        $paiement = $this->abonnementPaye($this->utilisateur('citoyen'));
        $abonnement = $paiement->abonnement;
        $agent = $this->utilisateur('agent');

        // Collecte sur place depuis la vérification du reçu
        $this->actingAs($agent)->post(route('agent.recus.collecter', $paiement->reference))->assertSessionHas('success');
        $this->assertSame(0, $abonnement->fresh()->collectes_restantes);
        $this->assertSame('epuise', $abonnement->fresh()->statutEffectif());

        // Reçu réutilisé : refusé
        $this->actingAs($agent)->post(route('agent.recus.collecter', $paiement->reference))->assertSessionHas('error');
        $this->assertSame(1, Collecte::where('statut', 'effectuee')->count());

        // Expiration
        $paiement2 = $this->abonnementPaye($this->utilisateur('citoyen'));
        $collecte = Collecte::demander($paiement2->abonnement, 'matin', null);
        $paiement2->abonnement->forceFill(['fin_at' => now()->subDay()])->save();
        $this->actingAs($agent)->post(route('agent.collectes.confirmer', $collecte))->assertSessionHas('error');
        $this->assertSame('demandee', $collecte->fresh()->statut);
    }

    public function test_un_paiement_non_confirme_ne_permet_aucune_collecte(): void
    {
        $paiement = $this->souscrire($this->utilisateur('citoyen'));
        $this->actingAs($this->utilisateur('agent'))->post(route('agent.recus.collecter', $paiement->reference))->assertSessionHas('error');
        $this->actingAs($paiement->citoyen)->post(route('citoyen.collectes.store', $paiement->abonnement), ['creneau' => 'matin'])->assertSessionHas('error');
        $this->assertSame(0, Collecte::count());
    }

    public function test_changer_le_tarif_ne_modifie_pas_les_abonnements_existants(): void
    {
        $paiement = $this->abonnementPaye($this->utilisateur('citoyen'));
        $this->formule->update(['prix' => 9000, 'nom' => 'Mensuelle+']);

        $this->assertSame(7500, $paiement->abonnement->fresh()->prix);
        $this->assertSame('Mensuelle', $paiement->abonnement->fresh()->formule_nom);
        $this->assertSame(7500, $paiement->fresh()->montant);
    }

    public function test_les_pages_de_chaque_role(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        $paiement = $this->abonnementPaye($citoyen);

        $this->actingAs($citoyen)->get(route('citoyen.abonnements.index'))->assertOk()->assertSee('Mensuelle')->assertSee('7 500');
        $this->actingAs($citoyen)->get(route('citoyen.abonnements.create', $this->formule))->assertOk()->assertSee('aucun argent réel');
        $this->actingAs($citoyen)->get(route('citoyen.paiements.index'))->assertOk()->assertSee($paiement->reference);
        $this->actingAs($this->utilisateur('responsable'))->get(route('responsable.paiements.index'))->assertOk()->assertSee($paiement->reference);
        $this->actingAs($this->utilisateur('agent'))->get(route('agent.recus.index'))->assertOk();
        $this->actingAs($citoyen)->get(route('agent.recus.index'))->assertForbidden();
    }
}
