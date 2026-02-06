let currentPage = 1;
let lastPage = 1;
let lastDataHash = ""; // Necessário para a comparação de cache

async function loadData(showSpinner = false) {
    if (showSpinner) toggleLoader(true);

    try {
        const res = await fetch(`/api/failed-events?page=${currentPage}`);
        if (!res.ok) throw new Error('Erro ao buscar dados');

        const result = await res.json();

        // CORREÇÃO: Removida a declaração duplicada de 'events'
        const events = result.data || [];
        lastPage = result.last_page || 1;
        currentPage = result.current_page || 1;

        // Lógica de Hash para evitar refresh visual desnecessário
        const currentHash = JSON.stringify(events);
        if (currentHash === lastDataHash && !showSpinner) {
            updatePaginationUI();
            return;
        }
        lastDataHash = currentHash;

        updatePaginationUI();

        if (events.length === 0) {
            rowsContainer.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-gray-500 italic">Nenhum erro encontrado na fila.</td></tr>';
            return;
        }

        rowsContainer.innerHTML = events.map(e => {
            let lvl = e.attempts >= 5 ? 'high' : (e.attempts >= 3 ? 'mid' : 'low');

            // Formatação do Payload
           // No seu loadData (JavaScript), certifique-se de que e.payload seja tratado:
let displayPayload = e.payload;
if (typeof e.payload === 'object') {
    displayPayload = JSON.stringify(e.payload, null, 2);
} else {
    try {
        displayPayload = JSON.stringify(JSON.parse(e.payload), null, 2);
    } catch (err) {
        displayPayload = e.payload;
    }
}


            return `
            <tr class="hover:bg-white/5 transition-colors ${lvl === 'high' ? 'retry-high row-glow' : ''}">
                <td class="px-6 py-4 text-xs font-mono text-gray-500">#${e.id}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-300">${e.routing_key || 'N/A'}</td>
                <td class="px-6 py-4">
                    <pre onclick="this.classList.toggle('expanded')"
                         class="code payload-preview p-2 rounded text-[10px] text-green-500/80 whitespace-pre-wrap">${displayPayload}</pre>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 text-[10px] font-bold border rounded-full badge-${lvl}">${e.attempts}x</span>
                </td>
                <td class="px-6 py-4 text-[10px] text-gray-400">
                    ${e.created_at ? new Date(e.created_at).toLocaleString('pt-BR') : '---'}
                </td>
                <td class="px-6 py-4 text-right space-x-1">
                    <button onclick="retryEvent(${e.id})" class="p-1.5 text-green-400 hover:bg-green-400/10 rounded">⟳</button>
                    <button onclick="deleteEvent(${e.id})" class="p-1.5 text-red-400 hover:bg-red-400/10 rounded">✕</button>
                </td>
            </tr>`;
        }).join('');

    } catch (e) {
        console.error("Erro no loadData:", e);
        rowsContainer.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-red-500">Erro de conexão com o banco.</td></tr>`;
    } finally {
        toggleLoader(false);
    }
}

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
    // Garante que os elementos existam antes de atualizar
    const pageInfo = document.getElementById('page-info');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');

    if(pageInfo) pageInfo.innerText = `Página ${currentPage} de ${lastPage}`;
    if(btnPrev) btnPrev.disabled = (currentPage === 1);
    if(btnNext) btnNext.disabled = (currentPage === lastPage);
}
