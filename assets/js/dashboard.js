document.addEventListener("DOMContentLoaded", () => {
    // Efeito de intersecção simples (Fade in global)
    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(el => {
        setTimeout(() => {
            el.classList.add('is-visible');
        }, 100);
    });

    // Gráfico Chart.js - Apenas se existir na página (dashboard.html)
    const chartCanvas = document.getElementById('occurrencesChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'bar', // Tipo barra emparelhada
            data: {
                labels: ['2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029'],
                datasets: [
                    {
                        label: 'Ocorrências Médicas',
                        data: [18, 15, 13, 19, 17, 24, 34, 37],
                        backgroundColor: '#1E88E5', // Azul primário chart
                        borderRadius: 4,
                        barPercentage: 0.8,
                        categoryPercentage: 0.4
                    },
                    {
                        label: 'Respostas Efectivas',
                        data: [13, 9, 10, 18, 12, 19, 31, 40],
                        backgroundColor: '#26A69A', // Verde secundário chart
                        borderRadius: 4,
                        barPercentage: 0.8,
                        categoryPercentage: 0.4
                    },
                    {
                        // Linha tracejada subtil sobre as barras
                        type: 'line',
                        label: 'Média Esperada',
                        data: [20, 18, 14, 13, 19, 28, 35, 41],
                        borderColor: '#555',
                        borderWidth: 1.5,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Oculto tal como na imagem
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 40,
                        grid: {
                            color: '#e0e0e0',
                            drawBorder: false,
                        },
                        ticks: {
                            stepSize: 5,
                            font: { size: 10 }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            font: { size: 10 }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
});
