<?php

namespace Tests\Feature;

use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role, 'commune' => 'Moroni'])->save();

        return $u;
    }

    private function signalement(User $citoyen, string $statut, array $attributs = []): Signalement
    {
        $s = $citoyen->signalements()->create(array_merge([
            'categorie' => 'plastique', 'description' => 'Bouteilles sur la plage', 'commune' => 'Moroni',
            'zone' => 'ngazidja', 'repere' => 'Plage', 'latitude' => -11.70, 'longitude' => 43.25,
        ], $attributs));
        $s->forceFill(['statut' => $statut])->save();

        return $s;
    }

    /** Points envoyés à la carte (attribut data-points). */
    private function points(string $html): array
    {
        preg_match("/data-points='([^']*)'/", $html, $m);

        return json_decode(html_entity_decode($m[1] ?? '[]', ENT_QUOTES), true);
    }

    public function test_couleurs_des_marqueurs_selon_le_statut(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        foreach (['nouveau', 'en_cours', 'resolu', 'verifie', 'rejete'] as $i => $statut) {
            $this->signalement($citoyen, $statut, ['latitude' => -11.70 - $i / 100]);
        }

        $html = $this->actingAs($this->utilisateur('responsable'))->get(route('responsable.carte'))->assertOk()->getContent();
        $parStatut = collect($this->points($html))->where('type', 'signalement')->keyBy('statut');

        $this->assertSame('#dc2626', $parStatut['nouveau']['couleur']);
        $this->assertTrue($parStatut['nouveau']['clignote']);
        $this->assertSame('#ea580c', $parStatut['en_cours']['couleur']);
        $this->assertSame('#16a34a', $parStatut['resolu']['couleur']);
        $this->assertSame('#71717a', $parStatut['verifie']['couleur']);
        $this->assertSame('#71717a', $parStatut['rejete']['couleur']);
        $this->assertFalse($parStatut['resolu']['clignote']);
        $this->assertStringContainsString('/responsable/signalements/', $parStatut['nouveau']['url']);
    }

    public function test_filtres_et_couches_de_la_carte_responsable(): void
    {
        $citoyen = $this->utilisateur('citoyen');
        $this->signalement($citoyen, 'nouveau');
        $this->signalement($citoyen, 'resolu');
        $this->signalement($citoyen, 'nouveau', ['zone' => 'mwali', 'commune' => 'Fomboni']);
        PointCollecte::create(['nom' => 'Centre de tri', 'commune' => 'Moroni', 'zone' => 'ngazidja', 'latitude' => -11.7, 'longitude' => 43.26]);

        $html = $this->actingAs($this->utilisateur('responsable'))
            ->get(route('responsable.carte', ['f' => 1, 'etat' => 'non_traite', 'zone' => 'ngazidja', 'couches' => ['signalements']]))
            ->getContent();
        $points = collect($this->points($html));
        $this->assertCount(1, $points);
        $this->assertSame('nouveau', $points[0]['statut']);

        $html = $this->get(route('responsable.carte'))->getContent();
        $this->assertSame('point_collecte', collect($this->points($html))->firstWhere('titre', 'Centre de tri')['type']);
    }

    public function test_la_carte_citoyen_ne_donne_acces_qu_a_ses_propres_dossiers(): void
    {
        $moi = $this->utilisateur('citoyen');
        $autre = $this->utilisateur('citoyen');
        $mien = $this->signalement($moi, 'nouveau');
        $this->signalement($autre, 'en_cours');
        $this->signalement($autre, 'rejete');

        $points = collect($this->points($this->actingAs($moi)->get(route('citoyen.carte'))->assertOk()->getContent()));

        $this->assertCount(2, $points);                     // le rejeté est masqué
        $this->assertStringEndsWith('/citoyen/signalements/'.$mien->id, $points->firstWhere('statut', 'nouveau')['url']);
        $this->assertNull($points->firstWhere('statut', 'en_cours')['url']);
        $this->assertStringNotContainsString($autre->name, json_encode($points->all()));
    }
}
