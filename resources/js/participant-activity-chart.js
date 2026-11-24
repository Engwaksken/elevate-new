// Participants page: daily activity line chart (active participants + new registrations).
// Plain SVG, one shared y-axis, crosshair + tooltip on hover and keyboard focus.
// Data comes from data-series (JSON) on [data-participant-activity-chart]; a table view
// with the same figures sits beside it, so the tooltip only adds convenience.

const SVG_NS = 'http://www.w3.org/2000/svg';
const SERIES = [
    { key: 'active', label: 'Active participants', className: 'pa-s1' },
    { key: 'registrations', label: 'New registrations', className: 'pa-s2' },
];
const HEIGHT = 240;
const PAD = { top: 12, right: 16, bottom: 28, left: 40 };

function el(name, attrs = {}, parent = null) {
    const node = document.createElementNS(SVG_NS, name);
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, String(v)));
    if (parent) parent.appendChild(node);
    return node;
}

// Round y-axis: the smallest 1/2/5 x 10^n step that covers the data in at most five ticks.
function niceScale(value) {
    const v = Math.max(1, value);
    for (let magnitude = 1; ; magnitude *= 10) {
        for (const m of [1, 2, 5]) {
            const step = m * magnitude;
            const ticks = Math.ceil(v / step);
            if (ticks <= 5) return { max: Math.max(2, ticks) * step, ticks: Math.max(2, ticks) };
        }
    }
}

const formatNumber = (n) => new Intl.NumberFormat().format(n);
const dayLabel = (iso, opts) => new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, opts);

function render(container) {
    let points;
    try {
        points = JSON.parse(container.dataset.series || '[]');
    } catch (e) {
        return;
    }
    if (!points.length) return;

    container.replaceChildren();
    const width = Math.max(320, container.clientWidth);
    const plotW = width - PAD.left - PAD.right;
    const plotH = HEIGHT - PAD.top - PAD.bottom;

    const { max: maxValue, ticks } = niceScale(Math.max(...points.flatMap((p) => SERIES.map((s) => p[s.key]))));
    const x = (i) => PAD.left + (points.length === 1 ? plotW / 2 : (i / (points.length - 1)) * plotW);
    const y = (v) => PAD.top + plotH - (v / maxValue) * plotH;

    const svg = el('svg', { width, height: HEIGHT, viewBox: `0 0 ${width} ${HEIGHT}`, 'aria-hidden': 'true' }, container);

    // Gridlines + y ticks
    for (let t = 0; t <= ticks; t += 1) {
        const value = (maxValue / ticks) * t;
        const ty = y(value);
        el('line', { x1: PAD.left, x2: width - PAD.right, y1: ty, y2: ty, class: t === 0 ? 'pa-axis' : 'pa-gridline' }, svg);
        const label = el('text', { x: PAD.left - 8, y: ty + 4, 'text-anchor': 'end', class: 'pa-tick' }, svg);
        label.textContent = formatNumber(Math.round(value));
    }

    // X ticks: about six evenly spaced dates
    const every = Math.max(1, Math.ceil(points.length / 6));
    points.forEach((p, i) => {
        if (i % every !== 0 && i !== points.length - 1) return;
        if (i !== points.length - 1 && points.length - 1 - i < every / 2) return;
        const label = el('text', { x: x(i), y: HEIGHT - 8, 'text-anchor': 'middle', class: 'pa-tick' }, svg);
        label.textContent = dayLabel(p.date, { day: 'numeric', month: 'short' });
    });

    // Series: faint area for the headline series, 2px lines, end dot
    const pathFor = (key) => points.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p[key]).toFixed(1)}`).join('');
    el('path', {
        d: `${pathFor('active')}L${x(points.length - 1)},${y(0)}L${x(0)},${y(0)}Z`,
        class: 'pa-area',
    }, svg);
    SERIES.forEach((s) => {
        el('path', { d: pathFor(s.key), class: `pa-path ${s.className}` }, svg);
        const last = points.length - 1;
        el('circle', { cx: x(last), cy: y(points[last][s.key]), r: 4, class: `pa-dot ${s.className}` }, svg);
    });

    // Hover layer
    const cross = el('line', { y1: PAD.top, y2: PAD.top + plotH, class: 'pa-cross', visibility: 'hidden' }, svg);
    const hoverDots = SERIES.map((s) => el('circle', { r: 4, class: `pa-dot ${s.className}`, visibility: 'hidden' }, svg));

    const tip = document.createElement('div');
    tip.className = 'pa-tip';
    tip.hidden = true;
    container.appendChild(tip);

    let current = points.length - 1;

    const show = (i) => {
        current = i;
        const p = points[i];
        const px = x(i);
        cross.setAttribute('x1', px);
        cross.setAttribute('x2', px);
        cross.setAttribute('visibility', 'visible');
        SERIES.forEach((s, n) => {
            hoverDots[n].setAttribute('cx', px);
            hoverDots[n].setAttribute('cy', y(p[s.key]));
            hoverDots[n].setAttribute('visibility', 'visible');
        });

        const title = document.createElement('div');
        title.className = 'pa-tip-date';
        title.textContent = dayLabel(p.date, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        const rows = SERIES.map((s) => {
            const row = document.createElement('div');
            row.className = 'pa-tip-row';
            const key = document.createElement('span');
            key.className = `pa-tip-key ${s.className}`;
            const value = document.createElement('strong');
            value.textContent = formatNumber(p[s.key]);
            const name = document.createElement('span');
            name.textContent = s.label;
            row.append(key, value, name);
            return row;
        });
        tip.replaceChildren(title, ...rows);
        tip.hidden = false;

        const tipW = tip.offsetWidth;
        const left = px + 12 + tipW > width ? px - 12 - tipW : px + 12;
        tip.style.left = `${Math.max(0, left)}px`;
        tip.style.top = `${PAD.top}px`;
    };

    const hide = () => {
        cross.setAttribute('visibility', 'hidden');
        hoverDots.forEach((d) => d.setAttribute('visibility', 'hidden'));
        tip.hidden = true;
    };

    const nearest = (clientX) => {
        const rect = svg.getBoundingClientRect();
        const ratio = (clientX - rect.left - PAD.left) / plotW;
        return Math.min(points.length - 1, Math.max(0, Math.round(ratio * (points.length - 1))));
    };

    svg.addEventListener('pointermove', (e) => show(nearest(e.clientX)));
    svg.addEventListener('pointerleave', hide);

    container.tabIndex = 0;
    container.addEventListener('focus', () => show(current));
    container.addEventListener('blur', hide);
    container.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            e.preventDefault();
            show(Math.min(points.length - 1, Math.max(0, current + (e.key === 'ArrowRight' ? 1 : -1))));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const charts = [...document.querySelectorAll('[data-participant-activity-chart]')];
    if (!charts.length) return;
    charts.forEach(render);

    let timer = null;
    window.addEventListener('resize', () => {
        clearTimeout(timer);
        timer = setTimeout(() => charts.forEach(render), 150);
    });
});
