// Variáveis de instância globais para permitir o .destroy()
let instanceHour = null;
let instanceRouting = null;
let instanceSeverity = null;

const getConfig = () => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#fff' } } },
    scales: {
        x: { ticks: { color: '#9ca3af' }, grid: { display: false } },
        y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
    }
});

function renderCharts(hours, hourValues, routingLabels, routingValues, sevLabels, sevValues) {
    // 1. Horas
    const ctxHour = document.getElementById('chartHour');
    if (ctxHour) {
        if (instanceHour) instanceHour.destroy();
        instanceHour = new Chart(ctxHour, {
            type: 'line',
            data: {
                labels: hours,
                datasets: [{ label: 'Falhas/Hora', data: hourValues, borderColor: '#ef4444', tension: 0.4 }]
            },
            options: getConfig()
        });
    }

    // 2. Routing
    const ctxRouting = document.getElementById('chartRouting');
    if (ctxRouting) {
        if (instanceRouting) instanceRouting.destroy();
        instanceRouting = new Chart(ctxRouting, {
            type: 'bar',
            data: {
                labels: routingLabels,
                datasets: [{ label: 'Por Routing Key', data: routingValues, backgroundColor: '#3b82f6' }]
            },
            options: getConfig()
        });
    }

    // 3. Severidade
    const ctxSev = document.getElementById('chartSeverity');
    if (ctxSev) {
        if (instanceSeverity) instanceSeverity.destroy();
        instanceSeverity = new Chart(ctxSev, {
            type: 'doughnut',
            data: {
                labels: sevLabels,
                datasets: [{
                    data: sevValues,
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: '#fff' } } }
            }
        });
    }
}

async function loadCharts() {
    try {
        const res = await fetch('/api/failed-events/dlq/charts'); // Verifique se esta rota bate com seu Controller
        if (!res.ok) throw new Error('Erro na API');
        const data = await res.json();

        const hours = (data.byHour || []).map(i => `${i.hour}h`);
        const hourValues = (data.byHour || []).map(i => i.total);

        const routingLabels = (data.byRouting || []).map(i => i.routing_key || 'N/A');
        const routingValues = (data.byRouting || []).map(i => i.total);

        const sevLabels = (data.bySeverity || []).map(i => i.severity);
        const sevValues = (data.bySeverity || []).map(i => i.total);

        renderCharts(hours, hourValues, routingLabels, routingValues, sevLabels, sevValues);
    } catch (e) {
        console.error('Erro ao carregar charts:', e);
    }
}

// Iniciar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    loadCharts();
    setInterval(loadCharts, 15000); // Atualiza a cada 15s
});
