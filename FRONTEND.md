# CleanComores : contrat front-end ↔ back-end

Le front-end est livré sous forme de **vues Blade alimentées par des données fictives**.
Pour brancher le back-end, il suffit de **remplacer chaque closure de `routes/maquette.php`
par un contrôleur** en gardant le même nom de route et en passant les mêmes variables à la vue.

Pour prévisualiser : lancer `npm run build` (ou `npm run dev`), puis ouvrir `/`, `/citoyen`, `/responsable` ou `/agent`.
Un bandeau noir « Maquette » permet de passer d'un rôle à l'autre.

## 1. Fichiers

| Fichier | Rôle |
|---|---|
| `config/cleancomores.php` | **Référentiel partagé** : clés de statuts, priorités, catégories, zones/communes. Utiliser **les mêmes clés** en base et dans les enums. |
| `routes/maquette.php` | Routes avec leurs noms et URL définitifs, données fictives et POST simulés. **À remplacer puis supprimer.** |
| `app/Support/Maquette.php` | Données fictives. Elles montrent la forme exacte des objets attendus. **À supprimer à la fin.** |
| `resources/views/layouts/{public,espace}.blade.php` | Layouts. `espace` prend `role="citoyen|responsable|agent"`. |
| `resources/views/components/cc/*` | Composants (`<x-cc.statut>`, `<x-cc.historique>`, `<x-cc.carte>`…) |
| `resources/views/{accueil,auth,citoyen,responsable,agent}/…` | Pages |
| `resources/js/app.js` | Confirmations, formulaire en 3 étapes, géolocalisation, carte Leaflet, aperçu photo |

## 2. Valeurs (clés en base)

- **statut** : `nouveau`, `a_verifier`, `valide`, `affecte`, `en_cours`, `resolu`, `verifie`, `rejete`
- **priorite** : `faible`, `moyenne`, `elevee`, `critique` (ou `null` tant que le dossier n'est pas validé)
- **categorie** : `depot_sauvage`, `poubelle_debordante`, `plastique`, `autre`
- **zone** : `ngazidja`, `ndzuani`, `mwali`

Les composants acceptent aussi bien une chaîne qu'un **enum PHP adossé** (`->value`).

## 3. Forme d'un signalement (`$signalement`, `$mission`, éléments des listes)

Un modèle Eloquent convient, à condition d'exposer ces attributs et relations :

```
id, reference ("CC-2026-0142"), categorie, description, commune, zone, repere,
latitude, longitude, photo_url (URL publique ou null),
statut, priorite, consignes, motif_rejet,
created_at, updated_at                        (Carbon)
citoyen      → { name, telephone, email }     (relation user)
agent        → { id, name } | null
compte_rendu → { texte, photo_url, created_at } | null
historique   → collection { statut, created_at, auteur (string), commentaire }  (ordre chronologique)
```

> Chaque transition de statut doit créer une ligne d'**historique** horodatée, car le citoyen la voit dans son suivi.

## 4. Routes et variables de vue

### Public
| Méthode | URL | Nom | Vue / remarque |
|---|---|---|---|
| GET | `/` | `home` | `accueil` |
| GET | `/connexion` | `connexion` | `auth.connexion`, POST vers la route Fortify `login` avec le champ **`identifiant`** (e-mail **ou** téléphone) + `password`, `remember` |
| GET | `/inscription` | `inscription` | `auth.inscription`, POST vers `register` : `name`, `telephone`, `email` (au moins l'un des deux), `commune`, `password`, `password_confirmation`, `conditions`. **Rôle forcé à `citoyen`.** |

Astuce : `Fortify::loginView(fn () => view('auth.connexion'))` et `Fortify::registerView(fn () => view('auth.inscription'))`.

### Citoyen (`citoyen.*`)
| Méthode | URL | Nom | Variables / champs |
|---|---|---|---|
| GET | `/citoyen` | `citoyen.signalements.index` | `$signalements` (collection ou paginator) |
| GET | `/citoyen/signalements/nouveau` | `citoyen.signalements.create` | — |
| POST | `/citoyen/signalements` | `citoyen.signalements.store` | multipart : `categorie`*, `description`* (10 à 1000 caractères), `photo` (image, 5 Mo max), `commune`*, `repere`, `latitude`, `longitude`. Rediriger vers `show` avec `success`. |
| GET | `/citoyen/signalements/{id}` | `citoyen.signalements.show` | `$signalement` (**vérifier qu'il appartient au citoyen**) |
| GET | `/citoyen/profil` | `citoyen.profil.edit` | utilise `auth()->user()` : `name, telephone, email, commune, notif_email, notif_changement_statut, notif_resolution` |
| PUT | `/citoyen/profil` | `citoyen.profil.update` | `name, telephone, email, commune` |
| PUT | `/citoyen/profil/notifications` | `citoyen.profil.notifications` | booléens `notif_email`, `notif_changement_statut`, `notif_resolution` (0/1 toujours envoyés) |
| PUT | `/citoyen/profil/mot-de-passe` | `citoyen.profil.mot-de-passe` | `current_password, password, password_confirmation` |

### Responsable (`responsable.*`)
| Méthode | URL | Nom | Variables / champs |
|---|---|---|---|
| GET | `/responsable` | `responsable.tableau` | `$indicateurs` = `['a_traiter','en_intervention','a_valider','taux_resolution']`, `$urgents`, `$a_valider`, `$nouveaux`, `$carte` |
| GET | `/responsable/signalements` | `responsable.signalements.index` | `$signalements` (paginator recommandé), `$total`. Filtres GET : `q, statut, priorite, categorie, zone` |
| GET | `/responsable/signalements/{id}` | `responsable.signalements.show` | `$signalement`, `$agents` = `{ id, name, zone, missions_en_cours, disponible }` |
| POST | `…/{id}/examiner` | `responsable.signalements.examiner` | nouveau → a_verifier |
| POST | `…/{id}/valider` | `responsable.signalements.valider` | `priorite`* ; a_verifier → valide |
| POST | `…/{id}/priorite` | `responsable.signalements.priorite` | `priorite`* |
| POST | `…/{id}/affecter` | `responsable.signalements.affecter` | `agent_id`*, `consignes` ; valide/affecte → affecte |
| POST | `…/{id}/rejeter` | `responsable.signalements.rejeter` | `motif`* (montré au citoyen) ; → rejete |
| POST | `…/{id}/cloturer` | `responsable.signalements.cloturer` | resolu → verifie |
| POST | `…/{id}/reprise` | `responsable.signalements.reprise` | `motif`* ; resolu → en_cours |
| GET | `/responsable/statistiques` | `responsable.statistiques` | `$stats` : voir l'en-tête de `responsable/statistiques.blade.php` |

### Agent (`agent.*`)
| Méthode | URL | Nom | Variables / champs |
|---|---|---|---|
| GET | `/agent` | `agent.missions.index` | `$missions` (statut affecte/en_cours **de l'agent connecté**) |
| GET | `/agent/missions/{id}` | `agent.missions.show` | `$mission` |
| POST | `…/{id}/demarrer` | `agent.missions.demarrer` | affecte → en_cours |
| POST | `…/{id}/terminer` | `agent.missions.terminer` | multipart `compte_rendu`*, `photo_preuve` (image) ; en_cours → **resolu** (l'agent ne clôture jamais) |
| GET | `/agent/historique` | `agent.historique` | `$missions` (resolu/verifie) |

## 5. Messages et erreurs

- Succès : `return back()->with('success', '…');`, erreur : `->with('error', '…')`
- Les erreurs de validation (`$errors`) s'affichent automatiquement en haut de la page et sous chaque champ.
- Les messages de validation doivent être en français (`APP_LOCALE=fr` + fichiers `lang/fr`).

## 6. À faire côté back (sécurité, à ne pas oublier)

- Middleware de rôle sur chaque groupe (`citoyen`, `responsable`, `agent`). Seul `citoyen` est attribuable à l'inscription.
- Le contrôle des transitions de statut se fait **côté serveur**. Le front n'affiche que les actions possibles, mais ne protège rien.
- Les coordonnées du citoyen ne sont envoyées qu'aux vues `responsable`. La vue agent ne les affiche pas.
