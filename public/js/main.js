document.addEventListener('DOMContentLoaded', () => {
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
