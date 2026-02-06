document.addEventListener('DOMContentLoaded', () => {

  const ctx1 = document.getElementById('chartHour');
  const ctx2 = document.getElementById('chartRouting');
  const ctx3 = document.getElementById('chartSeverity');

  if (!ctx1 || !ctx2 || !ctx3) return;

  new Chart(ctx1, {
    type: 'bar',
    data: {
      labels: ['10h', '11h', '12h'],
      datasets: [{
        label: 'Eventos',
        data: [2, 5, 3]
      }]
    }
  });

  new Chart(ctx2, {
    type: 'pie',
    data: {
      labels: ['ticket.created', 'ticket.updated'],
      datasets: [{
        data: [4, 6]
      }]
    }
  });

  new Chart(ctx3, {
    type: 'doughnut',
    data: {
      labels: ['Low', 'Mid', 'High'],
      datasets: [{
        data: [5, 3, 2]
      }]
    }
  });

});
