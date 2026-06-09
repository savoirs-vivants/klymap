document.addEventListener('DOMContentLoaded', function () {
    const chartDataEl = document.getElementById('chart-data');
    const canvasEl = document.getElementById('capteurChart');
    if (!chartDataEl || !canvasEl) return;

    const chartUrl = chartDataEl.dataset.chartUrl;

    const countEl = document.getElementById('chart-count');
    const limitInput = document.getElementById('chart-limit');
    const customRange = document.getElementById('custom-range');
    const fromInput = document.getElementById('chart-from');
    const toInput = document.getElementById('chart-to');

    let chart = null;
    let activePeriod = '1m';

    const DATASETS_META = [
        { key: 'temp', label: 'Température (°C)', color: '#f97316', yAxis: 'y' },
        { key: 'hum', label: 'Humidité (%)', color: '#3b82f6', yAxis: 'y' },
        { key: 'vitesse_vent', label: 'Vitesse du vent (km/h)', color: '#06b6d4', yAxis: 'y' },
        { key: 'press_baro', label: 'Pression (hPa)', color: '#8b5cf6', yAxis: 'yRight' },
        { key: 'pluie', label: 'Pluie (mm)', color: '#10b981', yAxis: 'y' },
    ];

    function buildParams() {
        const params = new URLSearchParams();

        params.set('limit', parseInt(limitInput?.value, 10) || 50);

        if (activePeriod === 'custom') {
            if (fromInput?.value) params.set('from', fromInput.value);
            if (toInput?.value) params.set('to', toInput.value);
        } else if (activePeriod !== 'none') {
            const now = new Date();
            const from = new Date(now);

            if (activePeriod === '1m') from.setMonth(now.getMonth() - 1);
            if (activePeriod === '6m') from.setMonth(now.getMonth() - 6);
            if (activePeriod === '1a') from.setFullYear(now.getFullYear() - 1);

            params.set('from', from.toISOString().slice(0, 10));
            params.set('to', now.toISOString().slice(0, 10));
        }

        return params;
    }

    async function loadChart() {
        const params = buildParams();
        const res = await fetch(`${chartUrl}?${params}`);
        const data = await res.json();

        if (countEl) countEl.textContent = `${data.count} mesures`;

        const datasets = DATASETS_META.map((m) => ({
            label: m.label,
            data: data[m.key],
            borderColor: m.color,
            backgroundColor: m.color + '14',
            borderWidth: 2,
            pointRadius: data.count > 200 ? 0 : 2,
            tension: 0.4,
            fill: false,
            yAxisID: m.yAxis,
        }));

        if (chart) {
            chart.data.labels = data.labels;
            datasets.forEach((ds, i) => {
                chart.data.datasets[i].data = ds.data;
                chart.data.datasets[i].pointRadius = ds.pointRadius;
            });
            chart.update('none');
        } else {
            const ctx = canvasEl.getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: { labels: data.labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                pointStyleWidth: 8,
                                padding: 16,
                                font: { family: "'Space Grotesk', sans-serif", size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: "'Space Grotesk', sans-serif", size: 12 },
                            bodyFont: { family: "'Space Mono', monospace", size: 11 },
                            padding: 12,
                            cornerRadius: 10,
                            borderColor: 'rgba(255,255,255,0.08)',
                            borderWidth: 1,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: "'Space Mono', monospace", size: 10 },
                                color: '#94a3b8',
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            grid: { color: 'rgba(0,0,0,0.04)', borderDash: [4, 4] },
                            ticks: { font: { family: "'Space Mono', monospace", size: 10 }, color: '#94a3b8' },
                            title: { display: true, text: 'Valeurs', font: { size: 10, family: "'Space Mono', monospace" }, color: '#94a3b8' }
                        },
                        yRight: {
                            type: 'linear',
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { font: { family: "'Space Mono', monospace", size: 10 }, color: '#8b5cf6' },
                            title: { display: true, text: 'hPa', font: { size: 10, family: "'Space Mono', monospace" }, color: '#8b5cf6' }
                        }
                    }
                }
            });
        }
    }

    function setActivePeriod(period) {
        activePeriod = period;
        document.querySelectorAll('.chart-period-btn').forEach((b) => {
            const active = b.dataset.period === period;
            b.classList.toggle('bg-white', active);
            b.classList.toggle('text-slate-800', active);
            b.classList.toggle('shadow-sm', active);
            b.classList.toggle('text-slate-500', !active);
        });
        if (customRange) customRange.classList.toggle('hidden', period !== 'custom');
    }

    document.querySelectorAll('.chart-period-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            setActivePeriod(btn.dataset.period);
            if (activePeriod !== 'custom') loadChart();
        });
    });

    document.getElementById('chart-custom-apply')?.addEventListener('click', loadChart);

    document.getElementById('chart-limit-apply')?.addEventListener('click', loadChart);
    limitInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') loadChart();
    });

    document.getElementById('btn-export-png')?.addEventListener('click', function () {
        console.debug('[capteur-chart] export PNG clicked');

        if (!canvasEl) return;
        const exp = document.createElement('canvas');
        exp.width = canvasEl.width;
        exp.height = canvasEl.height;
        const ctx = exp.getContext('2d');
        if (!ctx) return;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, exp.width, exp.height);
        ctx.drawImage(canvasEl, 0, 0);

        const link = document.createElement('a');
        link.download = `Capteur_${chartDataEl.dataset.capteurId}_Graphique_${new Date().toISOString().slice(0, 10)}.png`;
        link.href = exp.toDataURL('image/png', 1.0);
        link.click();
    });

    document.getElementById('btn-export-excel')?.addEventListener('click', function () {
        try {
            console.debug('[capteur-chart] export Excel clicked');

            const exportUrl = chartDataEl.dataset.exportUrl;
            if (!exportUrl) return;

            const params = buildParams();
            window.location.href = `${exportUrl}?${params.toString()}&format=xlsx`;
        } catch (e) {
            console.error('[capteur-chart] export Excel failed', e);
        }
    });

    setActivePeriod('1m');
    loadChart();
});

