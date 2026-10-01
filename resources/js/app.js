import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import {
    Chart,
    BarController,
    BarElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(BarController, BarElement, DoughnutController, ArcElement, CategoryScale, LinearScale, Tooltip, Legend);
Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--font-sans') || 'system-ui';
Chart.defaults.color = '#64748b';

const currency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const number = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });

/**
 * Gráfica de barras. config = { labels, values, colors?, format: 'money'|'number', horizontal?, unit? }
 */
Alpine.data('barChart', (config) => ({
    chart: null,
    init() {
        const fmt = (v) => (config.format === 'money' ? currency.format(v) : number.format(v) + (config.unit ? ' ' + config.unit : ''));
        const horizontal = !!config.horizontal;
        const valueAxis = {
            beginAtZero: true,
            grid: { color: '#eef2f6', drawTicks: false },
            border: { display: false },
            ticks: {
                padding: 8,
                maxTicksLimit: 5,
                precision: config.format === 'number' && !config.unit ? 0 : undefined,
                callback: (v) => (config.format === 'money' ? compactMoney(v) : number.format(v)),
            },
        };
        const categoryAxis = { grid: { display: false }, border: { color: '#e2e8f0' }, ticks: { padding: 6 } };

        this.chart = new Chart(this.$refs.canvas, {
            type: 'bar',
            data: {
                labels: config.labels,
                datasets: [
                    {
                        data: config.values,
                        backgroundColor: config.colors || '#237a70',
                        hoverBackgroundColor: config.colors || '#1f625b',
                        borderRadius: 4,
                        borderSkipped: 'start',
                        maxBarThickness: horizontal ? 18 : 28,
                    },
                ],
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 300 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: { label: (ctx) => fmt(ctx.parsed[horizontal ? 'x' : 'y']) },
                    },
                },
                scales: horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
                onClick: (evt, elements) => {
                    if (config.links && elements.length) {
                        const url = config.links[elements[0].index];
                        if (url) Livewire.navigate(url);
                    }
                },
                onHover: (evt, elements) => {
                    if (config.links) evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
            },
        });
    },
    destroy() {
        this.chart?.destroy();
    },
}));

function compactMoney(v) {
    if (Math.abs(v) >= 1_000_000) return '$' + number.format(v / 1_000_000) + ' M';
    if (Math.abs(v) >= 1_000) return '$' + number.format(v / 1_000) + ' mil';
    return '$' + v;
}

/**
 * Multi-select con búsqueda (especies). Lee y escribe el arreglo de IDs en $wire[property].
 * options = [{id, name}], permite crear una opción nueva llamando a $wire[createMethod](nombre).
 */
Alpine.data('multiSelect', ({ property, options, createMethod = null }) => ({
    open: false,
    search: '',
    options,
    get selected() {
        const value = this.$wire[property];
        if (Array.isArray(value)) return value;
        return value ? Object.values(value) : [];
    },
    set selected(value) {
        this.$wire[property] = value;
    },
    get filtered() {
        const q = this.normalize(this.search);
        return this.options.filter((o) => this.normalize(o.name).includes(q));
    },
    get canCreate() {
        const q = this.normalize(this.search).trim();
        return createMethod && q.length > 1 && !this.options.some((o) => this.normalize(o.name) === q);
    },
    normalize(s) {
        return (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    },
    isSelected(id) {
        return this.selected.map(String).includes(String(id));
    },
    toggle(id) {
        const current = this.selected.map(String);
        this.selected = current.includes(String(id)) ? current.filter((v) => v !== String(id)) : [...current, String(id)];
    },
    nameOf(id) {
        return this.options.find((o) => String(o.id) === String(id))?.name ?? '';
    },
    async create() {
        const name = this.search.trim();
        const created = await this.$wire.call(createMethod, name);
        if (created) {
            this.options.push(created);
            this.options.sort((a, b) => a.name.localeCompare(b.name, 'es'));
            this.toggle(created.id);
            this.search = '';
        }
    },
}));

Livewire.start();
