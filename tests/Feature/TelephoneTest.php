<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Formule;
use App\Models\User;
use App\Support\Telephone;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Numéros comoriens : normalisation et validation côté serveur partout où un téléphone est saisi. */
class TelephoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalisation_et_format_comorien(): void
    {
        foreach (['321 00 11', '3210011', '+269 321 00 11', '+2693210011', '00269 321 00 11', '269-321-00-11', '321.00.11'] as $saisie) {
            $this->assertSame('+2693210011', Telephone::normaliser($saisie), $saisie);
            $this->assertTrue(Telephone::estValide($saisie), $saisie);
        }
        $this->assertTrue(Telephone::estValide('441 22 33'));     // mobile (4)
        $this->assertTrue(Telephone::estValide('773 00 00'));     // fixe (7)
        foreach (['abc1234', '32100l1', '12', '521 00 11', '+33 6 12 34 56 78', '321 00 11 22', '+3210011', '32+10011', ''] as $saisie) {
            $this->assertFalse(Telephone::estValide($saisie), $saisie);
        }
        $this->assertSame('+269 321 00 11', Telephone::afficher('+2693210011'));
    }

    private function inscription(string $telephone): \Illuminate\Testing\TestResponse
    {
        return $this->post('/register', [
            'name' => 'Test', 'telephone' => $telephone, 'commune' => 'Moroni',
            'password' => 'MotDePasse123!', 'password_confirmation' => 'MotDePasse123!', 'conditions' => '1',
        ]);
    }

    public function test_inscription_refuse_les_numeros_invalides(): void
    {
        foreach (['abc', '32100l1', '12345', '521 00 11', '+33612345678'] as $mauvais) {
            $this->inscription($mauvais)->assertSessionHasErrors('telephone');
            $this->assertGuest();
        }
        $this->assertSame(0, User::count());
    }

    public function test_inscription_puis_connexion_quel_que_soit_le_format(): void
    {
        $this->inscription('321 00 11')->assertSessionHasNoErrors();
        $this->assertSame('+2693210011', User::firstOrFail()->telephone);
        auth()->logout();

        // Même numéro écrit autrement : refusé comme doublon
        $this->inscription('+269 321-00-11')->assertSessionHasErrors('telephone');
        auth()->logout();

        // Connexion avec une autre écriture du même numéro
        $this->post('/login', ['identifiant' => '+269 321 00 11', 'password' => 'MotDePasse123!'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_profil_et_souscription_valident_le_numero(): void
    {
        $u = User::factory()->create(['commune' => 'Moroni']);
        $u->forceFill(['role' => 'citoyen'])->save();
        $this->actingAs($u);

        $this->put(route('citoyen.profil.update'), ['name' => 'Moi', 'telephone' => 'zéro six', 'commune' => 'Moroni'])
            ->assertSessionHasErrors('telephone');
        $this->put(route('citoyen.profil.update'), ['name' => 'Moi', 'telephone' => '441 22 33', 'commune' => 'Moroni'])
            ->assertSessionHasNoErrors();
        $this->assertSame('+2694412233', $u->fresh()->telephone);

        $formule = Formule::create(['nom' => 'Ponctuelle', 'slug' => 'ponctuelle', 'prix' => 2500, 'duree_jours' => 30, 'collectes_incluses' => 1]);
        $donnees = ['adresse' => 'Maison 12', 'commune' => 'Moroni', 'latitude' => -11.7, 'longitude' => 43.25, 'conditions' => '1'];
        $this->post(route('citoyen.abonnements.store', $formule), $donnees + ['telephone' => '1234567'])->assertSessionHasErrors('telephone');
        $this->post(route('citoyen.abonnements.store', $formule), $donnees + ['telephone' => '00269 773 00 00'])->assertSessionHasNoErrors();
        $this->assertSame('+2697730000', $u->abonnements()->first()->telephone);
    }

    public function test_administration_valide_et_normalise_le_numero(): void
    {
        Filament::setCurrentPanel('admin');
        $resp = User::factory()->create();
        $resp->forceFill(['role' => 'responsable'])->save();
        $this->actingAs($resp);

        $donnees = ['name' => 'Agent', 'role' => 'agent', 'email' => 'a@cleancomores.km', 'commune' => 'Moroni', 'password' => 'Mot-de-passe-2026!'];
        Livewire::test(ManageUsers::class)->callAction('create', data: $donnees + ['telephone' => '06 12 AB'])
            ->assertHasActionErrors(['telephone']);
        Livewire::test(ManageUsers::class)->callAction('create', data: $donnees + ['telephone' => '344 55 66'])
            ->assertHasNoActionErrors();

        $agent = User::where('email', 'a@cleancomores.km')->firstOrFail();
        $this->assertSame('+2693445566', $agent->telephone);
        $this->assertSame('ngazidja', $agent->zone);
    }
}
