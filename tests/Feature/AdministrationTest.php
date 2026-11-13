<?php

namespace Tests\Feature;

use App\Filament\Resources\Formules\Pages\ManageFormules;
use App\Filament\Resources\PointCollectes\Pages\ManagePointCollectes;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Formule;
use App\Models\PointCollecte;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Panneau /admin (Filament) : formules, points de collecte, utilisateurs. */
class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role])->save();

        return $u;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_seul_un_responsable_accede_a_l_administration(): void
    {
        foreach (['formules', 'point-collectes', 'users'] as $page) {
            $this->actingAs($this->utilisateur('responsable'))->get("/admin/$page")->assertOk();
            $this->actingAs($this->utilisateur('agent'))->get("/admin/$page")->assertForbidden();
            $this->actingAs($this->utilisateur('citoyen'))->get("/admin/$page")->assertForbidden();
        }
    }

    public function test_le_responsable_cree_un_agent_avec_son_role(): void
    {
        $this->actingAs($this->utilisateur('responsable'));

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'name' => 'Agent Test', 'role' => 'agent', 'email' => 'agent.test@cleancomores.km',
                'telephone' => '+2693440000', 'commune' => 'Moroni', 'password' => 'Mot-de-passe-2026!',
            ])
            ->assertHasNoActionErrors();

        $agent = User::where('email', 'agent.test@cleancomores.km')->firstOrFail();
        $this->assertSame('agent', $agent->role);
        $this->assertTrue(\Hash::check('Mot-de-passe-2026!', $agent->password));
    }

    public function test_le_responsable_modifie_un_tarif_sans_toucher_au_code(): void
    {
        $this->actingAs($this->utilisateur('responsable'));
        $formule = Formule::create(['nom' => 'Mensuelle', 'slug' => 'mensuelle', 'prix' => 7500, 'duree_jours' => 30, 'collectes_incluses' => 4]);

        Livewire::test(ManageFormules::class)
            ->callTableAction('edit', $formule, data: ['prix' => 8000, 'services' => ['4 collectes', 'Choix du créneau']])
            ->assertHasNoTableActionErrors();

        $this->assertSame(8000, $formule->fresh()->prix);
        $this->assertSame(['4 collectes', 'Choix du créneau'], $formule->fresh()->services);
    }

    public function test_le_responsable_ajoute_un_point_de_collecte(): void
    {
        $this->actingAs($this->utilisateur('responsable'));

        Livewire::test(ManagePointCollectes::class)
            ->callAction('create', data: [
                'nom' => 'Point de Domoni', 'commune' => 'Domoni', 'zone' => 'ndzuani',
                'latitude' => -12.256, 'longitude' => 44.53, 'actif' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('ndzuani', PointCollecte::where('nom', 'Point de Domoni')->value('zone'));
    }
}
