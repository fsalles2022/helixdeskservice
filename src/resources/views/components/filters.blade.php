<div class="flex flex-wrap items-center justify-between gap-4 p-4 border-b border-gray-800">
    <div class="flex items-center gap-2">
        <input id="search"
               type="text"
               placeholder="Buscar routing key..."
               class="px-3 py-2 text-sm text-white bg-gray-900 border border-gray-700 rounded focus:outline-none focus:ring focus:ring-red-500/30">

        <select id="retries"
                class="px-3 py-2 text-sm text-white bg-gray-900 border border-gray-700 rounded">
            <option value="">Todos</option>
            <option value="3">3+ tentativas</option>
            <option value="5">5+ tentativas</option>
        </select>

        <button onclick="applyFilters()"
                class="px-3 py-2 text-sm font-semibold text-black bg-white rounded hover:bg-gray-200">
            Filtrar
        </button>
    </div>

    <div id="pagination" class="flex items-center gap-2 text-sm"></div>
</div>
