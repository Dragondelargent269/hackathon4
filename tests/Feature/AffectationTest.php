<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Signalement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Affectation d'un agent à un signalement.
 * Cause de la liste vide : aucun compte n'avait le rôle « agent » (l'inscription crée des citoyens
 * et aucune interface ne permettait d'en nommer). Les agents se créent désormais dans /admin.
 */
class AffectationTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(string $role): User
    {
        $u = User::factory()->create(['commune' => 'Moroni']);
        $u->forceFill(['role' => $role, 'zone' => 'ngazidja'])->save();

        return $u;
    }

    private function signalementValide(): Signalement
    {
        $citoyen = $this->utilisateur('citoyen');
        $s = $citoyen->signalements()->create([
            'categorie' => 'plastique', 'description' => 'Bouteilles sur la plage', 'commune' => 'Moroni',
            'zone' => 'ngazidja', 'repere' => 'Plage', 'latitude' => -11.7, 'longitude' => 43.25,
        ]);
        $s->forceFill(['statut' => 'valide'])->save();

        return $s;
    }

    public function test_sans_agent_un_message_explicite_remplace_la_liste_vide(): void
    {
        $s = $this->signalementValide();

        $this->actingAs($this->utilisateur('responsable'))
            ->get(route('responsable.signalements.show', $s))
            ->assertOk()
            ->assertSee('Aucun agent de terrain enregistré')
            ->assertSee('/admin/users')
            ->assertDontSee('Choisir un agent…');
    }

    public function test_un_agent_nomme_dans_l_administration_peut_etre_affecte(): void
    {
        $s = $this->signalementValide();
        $responsable = $this->utilisateur('responsable');
        $this->actingAs($responsable);

        // 1. Le responsable nomme un agent dans /admin
        Filament::setCurrentPanel('admin');
        Livewire::test(ManageUsers::class)->callAction('create', data: [
            'name' => 'Nassuf Mohamed', 'role' => 'agent', 'email' => 'nassuf@cleancomores.km',
            'telephone' => '344 55 67', 'commune' => 'Moroni', 'password' => 'Mot-de-passe-2026!',
        ])->assertHasNoActionErrors();
        $agent = User::where('email', 'nassuf@cleancomores.km')->firstOrFail();

        // 2. Il apparaît dans la liste d'affectation
        $this->get(route('responsable.signalements.show', $s))
            ->assertOk()
            ->assertSee('Choisir un agent…')
            ->assertSee('value="'.$agent->id.'"', false)
            ->assertSee('Nassuf Mohamed — Ngazidja');

        // 3. Affectation enregistrée
        $this->post(route('responsable.signalements.affecter', $s), ['agent_id' => $agent->id, 'consignes' => 'Prendre des sacs'])
            ->assertSessionHasNoErrors();
        $s->refresh();
        $this->assertSame('affecte', $s->statut);
        $this->assertSame($agent->id, $s->agent_id);

        // 4. Le responsable voit l'agent affecté ; l'agent voit sa mission
        $this->get(route('responsable.signalements.show', $s))->assertOk()->assertSee('Nassuf Mohamed');
        $this->actingAs($agent)->get(route('agent.missions.index'))->assertOk()->assertSee($s->commune);
        $this->actingAs($agent)->get(route('agent.missions.show', $s))->assertOk();
    }

    public function test_seul_un_compte_agent_peut_etre_affecte(): void
    {
        $s = $this->signalementValide();
        $citoyen = $this->utilisateur('citoyen');

        $this->actingAs($this->utilisateur('responsable'))
            ->post(route('responsable.signalements.affecter', $s), ['agent_id' => $citoyen->id])
            ->assertSessionHasErrors('agent_id');
        $this->assertNull($s->fresh()->agent_id);
    }
}
