<footer class="mt-16 border-t border-gray-800/50">
  <div class="px-6 py-10 mx-auto max-w-7xl">

    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">

      <!-- Marca -->
      <div>
        <h2 class="text-xl font-bold">
          <span class="text-red-500">Helix</span>Desk
        </h2>
        <p class="mt-2 text-sm text-gray-400">
          Plataforma de monitoramento e automação de filas,
          retries e eventos falhos em tempo real.
        </p>
        <p class="mt-3 text-xs text-gray-500">
          © <span id="year"></span> HelixDesk. Todos os direitos reservados.
        </p>
      </div>

      <!-- Links -->
      <div>
        <h3 class="mb-3 text-sm font-semibold tracking-widest text-gray-400 uppercase">
          Produto
        </h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li><a href="#" class="hover:text-white">Dashboard</a></li>
          <li><a href="#" class="hover:text-white">DLQ Monitor</a></li>
          <li><a href="#" class="hover:text-white">Workers</a></li>
          <li><a href="#" class="hover:text-white">Configurações</a></li>
        </ul>
      </div>

      <!-- Tech -->
      <div>
        <h3 class="mb-3 text-sm font-semibold tracking-widest text-gray-400 uppercase">
          Tecnologia
        </h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li>Laravel</li>
          <li>RabbitMQ</li>
          <li>TailwindCSS</li>
          <li>Docker</li>
        </ul>
      </div>

    </div>

    <!-- Barra inferior -->
    <div class="flex flex-col items-center justify-between pt-6 mt-10 text-xs text-gray-500 border-t border-gray-800 md:flex-row">
      <p>Monitoramento em tempo real • Alta disponibilidade • Observabilidade</p>
      <p class="mt-2 md:mt-0">Desenvolvido por Fabio Salles🚀<a href="https://www.linkedin.com/in/fabio-salles-47a85988/" target="_blank" class="ml-1 text-blue-400 hover:text-blue-300">LinkedIn</a></p>
    </div>
  </div>
</footer>

<script>
  document.getElementById('year').textContent = new Date().getFullYear();
</script>
