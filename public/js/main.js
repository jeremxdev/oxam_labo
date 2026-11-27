// Fichier : public/js/main.js — comportements côté navigateur du portail.
//
// Rôle : ajouter les contrôles côté client aux pages du portail, sans dépendre
// du serveur : validation de la date de création de visite et filtre de
// recherche instantané sur la liste des visites.
//
// Interactions : cible les éléments des vues views/visites/*.php
// (#visitForm, #date_vis, #searchInput, #visitsTable) incluses via le layout
// views/layout/footer.php. Ce script ne fait qu'empêcher l'envoi du formulaire
// ou masquer des lignes : la validation d'autorité reste côté serveur.
document.addEventListener('DOMContentLoaded', () => {
    // Formulaire de création de visite : on bloque l'envoi si la date est vide.
    // (Le message est affiché par un simple alert ; la page reste en place.)
    const form = document.getElementById('visitForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const dateInput = document.getElementById('date_vis');
            if (!dateInput.value) {
                alert('Veuillez sélectionner une date valide.');
                e.preventDefault();
            }
        });
    }
});

// Filtre instantané de la liste des visites : saisi dans #searchInput,
// on masque (display: none) toutes les lignes du tableau #visitsTable dont le
// texte ne contient pas la chaîne tapée (recherche insensible à la casse).
// Appelée par l'attribut onkeyup/oninput posé sur le champ de recherche.
function filterVisits() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('visitsTable');
    if (!table) return;
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let tdText = tr[i].textContent || tr[i].innerText;
        if (tdText.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
