let currentPage = 1;
let lastPage = 1;

// Ajuste na função loadData para aceitar a página
async function loadData(showSpinner = false) {
    if (showSpinner) toggleLoader(true);
    try {
        // Passa a página na URL
        const res = await fetch(`/api/failed-events?page=${currentPage}`);
        const result = await res.json();

        // O Laravel Paginate coloca os dados em result.data
        const events = result.data || [];
        lastPage = result.last_page;
        currentPage = result.current_page;

        const currentHash = JSON.stringify(events);
        if (currentHash === lastDataHash && !showSpinner) return;
        lastDataHash = currentHash;

        updatePaginationUI(); // Atualiza os botões

        if (events.length === 0) {
            rowsContainer.innerHTML = '<tr><td colspan="5" class="p-12 text-center text-gray-500">Nenhum erro encontrado.</td></tr>';
            return;
        }

        // ... (resto do seu .map() continua igual)
        rowsContainer.innerHTML = events.map(e => `...`).join('');

    } catch (e) { console.error(e); } finally { toggleLoader(false); }
}

// Funções para navegar
function changePage(direction) {
    if (direction === 'next' && currentPage < lastPage) {
        currentPage++;
        loadData(true);
    } else if (direction === 'prev' && currentPage > 1) {
        currentPage--;
        loadData(true);
    }
}

function updatePaginationUI() {
    document.getElementById('page-info').innerText = `Página ${currentPage} de ${lastPage}`;
    document.getElementById('btn-prev').disabled = (currentPage === 1);
    document.getElementById('btn-next').disabled = (currentPage === lastPage);
}
