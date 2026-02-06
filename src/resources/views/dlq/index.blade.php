<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelixDesk | DLQ Monitor</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Inter;
            background: #0b0e14;
            color: #f3f4f6
        }

        .glass {
            background: rgba(22, 27, 34, .8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .05)
        }

        .code {
            background: #010409;
            border: 1px solid #30363d;
            font-family: monospace;
            line-height: 1.5;
        }

        .payload-preview {
            max-height: 80px;
            overflow: hidden;
            cursor: pointer;
            transition: max-height 0.3s ease;
        }

        .payload-preview.expanded {
            max-height: none;
        }

        .badge-low {
            color: #22c55e;
            border-color: #22c55e40
        }

        .badge-mid {
            color: #eab308;
            border-color: #eab30840
        }

        .badge-high {
            color: #ef4444;
            border-color: #ef444440
        }

        .retry-high {
            background: rgba(239, 68, 68, .12)
        }

        .row-glow {
            box-shadow: inset 4px 0 0 rgba(239, 68, 68, .6)
        }

        #loader {
            background: rgba(0, 0, 0, .7);
            backdrop-filter: blur(4px);
            display: none
        }

        #loader.active {
            display: flex
        }

        .new-row {
            animation: flash 2s ease-out;
            background: rgba(34, 197, 94, .15) !important;
        }

        @keyframes flash {
            from {
                box-shadow: inset 4px 0 0 rgba(34, 197, 94, .9);
            }

            to {
                box-shadow: inset 4px 0 0 rgba(34, 197, 94, 0);
            }
        }
    </style>
</head>

<body>
    <?php include resource_path('views/components/navbar.php'); ?>

    <div id="loader" class="fixed inset-0 z-50 items-center justify-center">
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 border-4 border-red-500 rounded-full border-t-transparent animate-spin"></div>
            <p class="mt-3 text-sm text-gray-400">Processando...</p>
        </div>
    </div>

    <div class="px-4 py-8 mx-auto max-w-7xl">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">
                    <span class="text-red-500">Helix</span>Desk <span class="text-gray-500">/</span>
                    <span class="px-2 py-1 text-sm bg-gray-800 rounded">DLQ</span>
                </h1>
                <p class="text-xs tracking-widest text-gray-500 uppercase">RabbitMQ Dead Letter Queue</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>Monitoramento em tempo Real
                </div>
                <button onclick="refresh()"
                    class="px-4 py-2 text-sm font-semibold text-black bg-white rounded hover:bg-gray-200">Sincronizar</button>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-4 mb-8 text-center md:grid-cols-4 md:text-left">
            <div class="p-4 border glass rounded-xl border-green-500/30">
                <p class="text-xs text-gray-500 uppercase">Workers</p>
                <p class="font-bold text-green-400">ATIVOS</p>
            </div>
            <div class="p-4 border glass rounded-xl border-blue-500/20">
                <p class="text-xs text-gray-500 uppercase">Hoje</p>
                <p id="stats-today" class="text-2xl font-bold text-blue-400">0</p>
            </div>
            <div class="p-4 border glass rounded-xl border-yellow-500/20">
                <p class="text-xs text-gray-500 uppercase">Retries</p>
                <p id="queue-retry" class="text-2xl font-bold text-yellow-400">0</p>
            </div>
            <div class="p-4 border glass rounded-xl border-gray-500/20">
                <p class="text-xs text-gray-400 uppercase">DLQ</p>
                <p id="queue-dlq" class="text-2xl font-bold text-gray-300">0</p>
            </div>
        </div>

        <div class="overflow-hidden glass rounded-2xl">
            <table class="w-full text-left">
                <thead class="text-xs tracking-wider text-gray-400 uppercase bg-gray-800/40">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6">Routing</th>
                        <th class="px-6 py-4">Payload</th>
                        <th class="px-6 py-4 text-center">Retries</th>
                        <th class="px-6 py-4">Falhou em</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-500">Carregando...</td>
                    </tr>
                </tbody>
            </table>
            <div class="flex items-center justify-between px-4 py-3 mt-3 text-sm text-gray-400">
                <span id="page-info">Página 1</span>
                <div class="flex gap-2">
                    <button id="prev" class="px-3 py-1 bg-gray-800 rounded hover:bg-gray-700">Anterior</button>
                    <button id="next" class="px-3 py-1 bg-gray-800 rounded hover:bg-gray-700">Próxima</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const loader = document.getElementById('loader');
        const rows = document.getElementById('rows');

        let currentPage = 1;
        let lastPage = 1;
        let lastIds = new Set();


        const toggleLoader = s => s ? loader.classList.add('active') : loader.classList.remove('active');

        async function loadStats() {
            try {
                const r = await fetch('/api/failed-events/stats');
                const s = await r.json();
                document.getElementById('stats-today').textContent = s.today ?? 0;
                document.getElementById('queue-retry').textContent = s.status?.total_retries ?? 0;
                document.getElementById('queue-dlq').textContent = s.total ?? 0;
            } catch (e) {
                console.error(e);
            }
        }

        async function loadData(show = false) {
            if (show) toggleLoader(true);
            try {
                const r = await fetch(`/api/failed-events?page=${currentPage}`);
                const j = await r.json();

                const data = j.data || [];
                lastPage = j.last_page || 1;

                document.getElementById('page-info').textContent =
                    `Página ${currentPage} de ${lastPage}`;

                if (!data.length) {
                    rows.innerHTML =
                        '<tr><td colspan="6" class="p-10 text-center text-gray-500">Nenhum evento.</td></tr>';
                    return;
                }

                const newIds = new Set(data.map(e => e.id));

                rows.innerHTML = data.map(e => {
                    let lvl = e.attempts >= 5 ? 'high' : (e.attempts >= 3 ? 'mid' : 'low');
                    const isNew = !lastIds.has(e.id);

                    let displayPayload = e.payload;
                    try {
                        const obj = typeof e.payload === 'string' ? JSON.parse(e.payload) : e.payload;
                        displayPayload = JSON.stringify(obj, null, 2);
                    } catch {}

                    return `
      <tr class="border-b border-gray-800 hover:bg-white/5 transition-colors
        ${lvl === 'high' ? 'retry-high row-glow' : ''}
        ${isNew ? 'new-row' : ''}">
        <td class="px-6 py-3 text-xs text-gray-400">#${e.id}</td>
        <td class="px-6 py-3 text-sm">${e.routing_key || 'N/A'}</td>
        <td class="max-w-xs px-6 py-3">
          <pre onclick="this.classList.toggle('expanded')"
            class="code payload-preview p-4 rounded text-[10px] text-green-500/80 whitespace-pre-wrap">${displayPayload}</pre>
        </td>
        <td class="px-6 py-3 text-center">
          <span class="px-2 py-1 text-xs border rounded-full badge-${lvl}">${e.attempts}x</span>
        </td>
        <td class="px-6 py-3 text-xs text-gray-400">${new Date(e.created_at).toLocaleString('pt-BR')}</td>
        <td class="px-6 py-3 text-right">
          <button onclick="retryEvent(${e.id})" class="px-2 text-green-400 hover:text-green-300">⟳</button>
          <button onclick="deleteEvent(${e.id})" class="px-2 text-red-400 hover:text-red-300">✕</button>
        </td>
      </tr>`;
                }).join('');

                lastIds = newIds;

                document.getElementById('prev').disabled = currentPage <= 1;
                document.getElementById('next').disabled = currentPage >= lastPage;

            } catch (e) {
                console.error(e);
            } finally {
                toggleLoader(false);
            }
        }


        async function retryEvent(id) {
            toggleLoader(true);
            await fetch(`/api/failed-events/${id}/retry`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            refresh();
        }

        async function deleteEvent(id) {
            if (!confirm('Excluir definitivamente?')) return;
            toggleLoader(true);
            await fetch(`/api/failed-events/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            refresh();
        }

        function refresh() {
            loadStats();
            loadData();
        }

        // Inicialização
        refresh();
        setInterval(refresh, 5000); // Intervalo de 5s para não sobrecarregar

        document.getElementById('prev').onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                loadData(true);
            }
        };

        document.getElementById('next').onclick = () => {
            if (currentPage < lastPage) {
                currentPage++;
                loadData(true);
            }
        };
    </script>

    <?php include resource_path('views/components/footer.php'); ?>

</body>

</html>
