/* ==========================================================================
   CleanComores — interactions front-end (sans dépendance, amélioration progressive)
   ========================================================================== */

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

/* --------------------------------------------------------------------------
   1. Dialogues de confirmation (<dialog>)
   - [data-ouvrir="id"]      ouvre le dialogue
   - <form data-confirmer="id"> : la soumission est interceptée, le dialogue
     s'ouvre, et le formulaire n'est envoyé qu'après [data-valider]
   - [data-remplir="champ"]  dans le dialogue : recopie la valeur du champ
   -------------------------------------------------------------------------- */
function remplirDialogue(dialog, form) {
    if (!form) return;
    $$('[data-remplir]', dialog).forEach((el) => {
        const champ = form.elements[el.dataset.remplir];
        if (!champ) return;
        const valeur = champ.tagName === 'SELECT' ? champ.selectedOptions[0]?.textContent.trim() : champ.value.trim();
        el.textContent = valeur || '—';
    });
}

function ouvrirDialogue(id, declencheur, form = null) {
    const dialog = document.getElementById(id);
    if (!dialog) return false;
    remplirDialogue(dialog, form);
    dialog._form = form;
    dialog._declencheur = declencheur;
    dialog.showModal();
    ($('textarea, input:not([type=hidden]), select', dialog) || $('[data-valider], [type=submit]', dialog))?.focus();
    return true;
}

document.addEventListener('click', (e) => {
    const ouvrir = e.target.closest('[data-ouvrir]');
    if (ouvrir) {
        e.preventDefault();
        ouvrirDialogue(ouvrir.dataset.ouvrir, ouvrir, ouvrir.closest('form'));
        return;
    }
    const fermer = e.target.closest('[data-fermer]');
    if (fermer) {
        fermer.closest('dialog')?.close();
        return;
    }
    const valider = e.target.closest('[data-valider]');
    if (valider) {
        const dialog = valider.closest('dialog');
        const form = dialog?._form;
        if (form) {
            form.dataset.confirme = '1';
            valider.disabled = true;
            dialog.close();
            form.requestSubmit();
        }
    }
});

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.confirmer && form.dataset.confirme !== '1') {
        e.preventDefault();
        if (!form.reportValidity()) return;
        ouvrirDialogue(form.dataset.confirmer, e.submitter, form);
        return;
    }
    // Retour visuel pendant l'envoi + évite les doubles soumissions
    const bouton = e.submitter;
    if (bouton && !e.defaultPrevented) {
        setTimeout(() => {
            bouton.disabled = true;
            bouton.setAttribute('aria-busy', 'true');
            bouton.dataset.texte = bouton.textContent;
            bouton.lastChild.textContent = ' Envoi…';
        }, 0);
    }
});

// Clic sur le fond du dialogue = fermer ; on rend le focus au déclencheur
document.addEventListener('click', (e) => {
    if (e.target instanceof HTMLDialogElement) e.target.close();
});
document.addEventListener('close', (e) => {
    if (e.target instanceof HTMLDialogElement) e.target._declencheur?.focus?.();
}, true);

/* --------------------------------------------------------------------------
   2. Formulaire en étapes  <form data-etapes>
      chaque étape : <fieldset data-etape="1">, boutons [data-suivant] / [data-precedent]
      indicateur : [data-indicateur-etape="1"]
   -------------------------------------------------------------------------- */
function initEtapes(form) {
    const etapes = $$('[data-etape]', form);
    const indicateurs = $$('[data-indicateur-etape]', form);
    let courante = 0;

    // Revenir sur l'étape contenant une erreur serveur éventuelle
    const erreur = etapes.findIndex((et) => $('[aria-invalid="true"]', et));
    if (erreur > 0) courante = erreur;

    const afficher = (i, focus = true) => {
        courante = i;
        etapes.forEach((et, n) => (et.hidden = n !== i));
        indicateurs.forEach((ind, n) => {
            ind.dataset.etat = n < i ? 'fait' : n === i ? 'courant' : 'avenir';
            if (n === i) ind.setAttribute('aria-current', 'step');
            else ind.removeAttribute('aria-current');
        });
        if (etapes[i].dataset.etape === 'recap') remplirRecap(form);
        if (focus) {
            const titre = $('legend, h2', etapes[i]);
            titre?.setAttribute('tabindex', '-1');
            titre?.focus();
            form.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
        document.dispatchEvent(new CustomEvent('cc:etape', { detail: { etape: etapes[i] } }));
    };

    const valide = (et) => $$('input, select, textarea', et).every((c) => c.reportValidity());

    form.addEventListener('click', (e) => {
        if (e.target.closest('[data-suivant]')) {
            e.preventDefault();
            if (valide(etapes[courante])) afficher(Math.min(courante + 1, etapes.length - 1));
        }
        if (e.target.closest('[data-precedent]')) {
            e.preventDefault();
            afficher(Math.max(courante - 1, 0));
        }
        const aller = e.target.closest('[data-aller-etape]');
        if (aller) {
            e.preventDefault();
            afficher(Number(aller.dataset.allerEtape));
        }
    });

    afficher(courante, false);
}

function remplirRecap(form) {
    $$('[data-recap]', form).forEach((el) => {
        const noms = el.dataset.recap.split(',');
        const valeurs = noms.map((nom) => {
            const champ = form.elements[nom];
            if (!champ) return '';
            if (champ instanceof RadioNodeList) {
                const coche = [...champ].find((r) => r.checked);
                return coche ? (coche.dataset.label || coche.value) : '';
            }
            if (champ.type === 'file') return champ.files[0]?.name || '';
            if (champ.tagName === 'SELECT') return champ.value ? champ.selectedOptions[0].textContent.trim() : '';
            return champ.value.trim();
        }).filter(Boolean);
        el.textContent = valeurs.length ? valeurs.join(', ') : (el.dataset.vide || '—');
    });
}

/* --------------------------------------------------------------------------
   3. Aperçu photo   <input type="file" data-apercu="idImg">
   -------------------------------------------------------------------------- */
document.addEventListener('change', (e) => {
    const input = e.target.closest('input[type=file][data-apercu]');
    if (!input) return;
    const zone = document.getElementById(input.dataset.apercu);
    if (!zone) return;
    const fichier = input.files[0];
    if (!fichier) { zone.hidden = true; return; }
    if (fichier.size > 5 * 1024 * 1024) {
        input.value = '';
        input.setCustomValidity('La photo dépasse 5 Mo. Choisissez une image plus légère.');
        input.reportValidity();
        input.setCustomValidity('');
        zone.hidden = true;
        return;
    }
    $('img', zone).src = URL.createObjectURL(fichier);
    $('[data-nom-fichier]', zone).textContent = fichier.name;
    zone.hidden = false;
});
document.addEventListener('click', (e) => {
    const retirer = e.target.closest('[data-retirer-photo]');
    if (!retirer) return;
    const input = document.getElementById(retirer.dataset.retirerPhoto);
    input.value = '';
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.focus();
});

/* --------------------------------------------------------------------------
   4. Carte Leaflet (chargée uniquement si la page contient [data-carte])
   -------------------------------------------------------------------------- */
const LEAFLET = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet';
let leafletPromesse;
function chargerLeaflet() {
    if (window.L) return Promise.resolve(window.L);
    leafletPromesse ??= new Promise((ok, ko) => {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = `${LEAFLET}.css`;
        document.head.append(css);
        const js = document.createElement('script');
        js.src = `${LEAFLET}.js`;
        js.onload = () => ok(window.L);
        js.onerror = ko;
        document.head.append(js);
    });
    return leafletPromesse;
}

// Les couleurs viennent de config/cleancomores.php (marqueurs / types_points) via <x-cc.carte>
const echapper = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function initCarte(el) {
    const points = JSON.parse(el.dataset.points || '[]');
    const centre = JSON.parse(el.dataset.centre || '{"lat":-11.95,"lng":43.75,"zoom":8}');
    const form = el.closest('form');

    chargerLeaflet().then((L) => {
        $('[data-carte-repli]', el)?.remove();
        const carte = L.map(el, { scrollWheelZoom: false }).setView([centre.lat, centre.lng], centre.zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(carte);

        const marqueurs = points.filter((p) => p.lat && p.lng).map((p) => {
            const couleur = p.couleur || '#0a7661';
            // Signalement : rond (rouge clignotant si non traité) ; autres types : carré
            const m = p.type === 'signalement'
                ? L.circleMarker([p.lat, p.lng], {
                    radius: 9, weight: 2, color: '#ffffff', fillColor: couleur, fillOpacity: 1,
                    className: p.clignote ? 'cc-clignote' : '',
                })
                : L.marker([p.lat, p.lng], {
                    icon: L.divIcon({ className: 'cc-pin-carre', html: `<span style="background:${couleur}"></span>`, iconSize: [20, 20], iconAnchor: [10, 10] }),
                    keyboard: true, title: p.titre,
                });
            m.addTo(carte);
            const lignes = [`<strong>${echapper(p.titre)}</strong>`];
            if (p.typeLabel) lignes.push(`<span class="cc-popup-type">${echapper(p.typeLabel)}</span>`);
            if (p.statutLabel) lignes.push(`Statut : ${echapper(p.statutLabel)}`);
            if (p.details) lignes.push(echapper(p.details));
            if (p.url) lignes.push(`<a href="${echapper(p.url)}">${echapper(p.lien || 'Ouvrir le dossier')}</a>`);
            m.bindPopup(lignes.join('<br>'));
            return m;
        });
        if (marqueurs.length > 1) carte.fitBounds(L.featureGroup(marqueurs).getBounds().pad(0.2));
        else if (marqueurs.length === 1) carte.setView(marqueurs[0].getLatLng(), 15);

        if ('selection' in el.dataset && form) {
            let repere = null;
            const placer = (lat, lng, centrer = false) => {
                form.elements.latitude.value = lat.toFixed(6);
                form.elements.longitude.value = lng.toFixed(6);
                repere ??= L.marker([lat, lng], { draggable: true }).addTo(carte)
                    .on('dragend', (ev) => placer(ev.target.getLatLng().lat, ev.target.getLatLng().lng));
                repere.setLatLng([lat, lng]);
                if (centrer) carte.setView([lat, lng], 16);
                form.dispatchEvent(new CustomEvent('cc:position', { detail: { lat, lng } }));
            };
            carte.on('click', (ev) => placer(ev.latlng.lat, ev.latlng.lng));
            el._placer = placer;
            const lat = parseFloat(form.elements.latitude?.value);
            const lng = parseFloat(form.elements.longitude?.value);
            if (lat && lng) placer(lat, lng, true);
        }
        el._carte = carte;
        // Les cartes dans une étape masquée doivent être recalculées à l'affichage
        document.addEventListener('cc:etape', () => setTimeout(() => carte.invalidateSize(), 50));
    }).catch(() => {
        const repli = $('[data-carte-repli] p', el);
        if (repli) repli.textContent = 'La carte n\'a pas pu être chargée (connexion ?). Les informations restent disponibles ci-dessous.';
    });
}

/* --------------------------------------------------------------------------
   5. Géolocalisation avec accord explicite  [data-geolocaliser]
   -------------------------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const bouton = e.target.closest('[data-geolocaliser]');
    if (!bouton) return;
    e.preventDefault();
    const form = bouton.closest('form');
    const statut = document.getElementById(bouton.dataset.geolocaliser);
    const dire = (texte, type = 'info') => {
        statut.textContent = texte;
        statut.dataset.type = type;
    };
    if (!('geolocation' in navigator)) {
        dire('Votre appareil ne permet pas la localisation. Placez le repère sur la carte.', 'erreur');
        return;
    }
    dire('Recherche de votre position… Autorisez l\'accès si votre navigateur le demande.');
    bouton.disabled = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            bouton.disabled = false;
            const carte = $('[data-carte]', form);
            if (carte?._placer) carte._placer(pos.coords.latitude, pos.coords.longitude, true);
            else {
                form.elements.latitude.value = pos.coords.latitude.toFixed(6);
                form.elements.longitude.value = pos.coords.longitude.toFixed(6);
            }
            dire(`Position trouvée (précision ± ${Math.round(pos.coords.accuracy)} m). Vous pouvez déplacer le repère.`, 'succes');
        },
        (err) => {
            bouton.disabled = false;
            dire(err.code === 1
                ? 'Accès à la position refusé. Ce n\'est pas grave : touchez la carte pour placer le repère, ou décrivez le lieu.'
                : 'Position introuvable pour le moment. Touchez la carte pour placer le repère.', 'erreur');
        },
        { enableHighAccuracy: true, timeout: 15000 },
    );
});

/* --------------------------------------------------------------------------
   6. Filtres : envoi automatique au changement d'une liste déroulante
   -------------------------------------------------------------------------- */
document.addEventListener('change', (e) => {
    const champ = e.target.closest('form[data-filtres-auto] select');
    if (champ) champ.form.requestSubmit();
});

/* --------------------------------------------------------------------------
   7. Téléphone comorien  [data-telephone]
   Mêmes règles que App\Support\Telephone (le serveur revalide toujours).
   -------------------------------------------------------------------------- */
const TEL_VALIDE = /^\+269[347][0-9]{6}$/;
function normaliserTelephone(saisie) {
    const s = saisie.trim();
    if (!s || !/^\+?[0-9 .-]+$/.test(s)) return s;
    let chiffres = s.replace(/\D/g, '');
    const international = s.startsWith('+') || chiffres.startsWith('00');
    chiffres = chiffres.replace(/^00/, '');
    if (chiffres.length === 7 && !international) return `+269${chiffres}`;
    if (chiffres.length === 10 && chiffres.startsWith('269')) return `+${chiffres}`;
    return (international ? '+' : '') + chiffres;
}
// Pendant la frappe (et au collage) : on retire lettres et symboles ; « + » seulement en premier
document.addEventListener('input', (e) => {
    const champ = e.target.closest('input[data-telephone]');
    if (!champ) return;
    const propre = champ.value.replace(/[^0-9+ .-]/g, '').replace(/(?!^)\+/g, '');
    if (propre !== champ.value) {
        const pos = Math.max(0, champ.selectionStart - (champ.value.length - propre.length));
        champ.value = propre;
        champ.setSelectionRange(pos, pos);
    }
});
// À la sortie du champ : message clair si le format n'est pas comorien
document.addEventListener('focusout', (e) => {
    const champ = e.target.closest('input[data-telephone]');
    if (!champ) return;
    const message = document.getElementById(`${champ.id}-format`);
    const vide = champ.value.trim() === '';
    const ok = vide || TEL_VALIDE.test(normaliserTelephone(champ.value));
    champ.setCustomValidity(ok ? '' : 'Numéro comorien attendu : 7 chiffres commençant par 3, 4 ou 7.');
    if (ok) champ.removeAttribute('aria-invalid');
    else champ.setAttribute('aria-invalid', 'true');
    if (message) {
        message.hidden = ok;
        message.textContent = ok ? '' : 'Numéro invalide : 7 chiffres commençant par 3, 4 ou 7 (ex. : 321 00 11 ou +269 321 00 11).';
        const decrit = new Set((champ.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
        ok ? decrit.delete(message.id) : decrit.add(message.id);
        champ.setAttribute('aria-describedby', [...decrit].join(' '));
    }
});

/* -------------------------------------------------------------------------- */
function init() {
    $$('form[data-etapes]').forEach(initEtapes);
    $$('[data-carte]').forEach(initCarte);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
