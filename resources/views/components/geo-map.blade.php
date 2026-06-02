@props([
    'mapData'       => ['nodes' => [], 'edges' => []],
    'currentCityId' => null,
    'compact'       => false,
    'dark'          => false,
])

<div
    x-data="geoMap(@js($mapData), @js($currentCityId), @js($compact), @js($dark))"
    x-init="init()"
    class="relative w-full"
>
    {{-- View toggle buttons --}}
    @if (!$compact)
        <div class="flex flex-wrap gap-2 mb-4">
            <button type="button"
                @click="setView('world')"
                :class="view === 'world' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium font-mono tracking-wide transition-colors">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                World
            </button>
            <template x-for="(cfg, key) in continents" :key="key">
                <button type="button"
                    @click="setView(key)"
                    :class="view === key ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium font-mono tracking-wide transition-colors">
                    <span class="inline-block h-2 w-2 rounded-full" :style="`background:${cfg.color}`"></span>
                    <span x-text="cfg.label"></span>
                </button>
            </template>
        </div>
    @else
        <div class="flex flex-wrap gap-1 mb-2">
            <template x-for="(cfg, key) in continents" :key="key">
                <button type="button"
                    @click="setView(key)"
                    :class="view === key ? 'border-emerald-500/60 text-emerald-400 bg-emerald-950/30' : 'border-zinc-700 text-zinc-600 hover:text-zinc-400'"
                    class="rounded border px-2 py-0.5 text-[8px] font-mono tracking-widest uppercase transition-colors"
                    x-text="cfg.label">
                </button>
            </template>
        </div>
    @endif

    {{-- Tooltip --}}
    <div
        x-show="tooltip.visible"
        x-cloak
        :style="`left:${tooltip.x}px;top:${tooltip.y}px`"
        class="{{ $dark ? 'border-zinc-700 bg-zinc-900' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }} pointer-events-none absolute z-10 rounded-xl border px-3 py-2 text-xs shadow-xl"
    >
        <div class="{{ $dark ? 'text-emerald-300' : 'text-zinc-900 dark:text-white' }} font-semibold font-mono" x-text="tooltip.label"></div>
        <div class="{{ $dark ? 'text-zinc-400' : 'text-zinc-500 dark:text-zinc-400' }}" x-text="tooltip.country"></div>
        <div class="{{ $dark ? 'text-zinc-500' : 'text-zinc-500 dark:text-zinc-400' }} mt-0.5 italic" x-text="tooltip.biome"></div>
        <template x-if="tooltip.lat !== null">
            <div class="{{ $dark ? 'text-zinc-600' : 'text-zinc-400 dark:text-zinc-600' }} mt-0.5 font-mono text-[10px]"
                x-text="`${tooltip.lat?.toFixed(2)}°, ${tooltip.lng?.toFixed(2)}°`"></div>
        </template>
    </div>

    {{-- SVG Map Canvas --}}
    <div class="relative w-full" x-ref="container">
        <svg
            x-ref="svgEl"
            viewBox="0 0 1000 500"
            class="w-full block"
            :style="`background:${isDark ? '#0d1117' : '#dbeafe'}`"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <marker id="geo-arr-def" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L6,3 z" :fill="isDark ? '#52525b' : '#a1a1aa'" />
                </marker>
                <marker id="geo-arr-cur" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L6,3 z" fill="#34d399" />
                </marker>
                <marker id="geo-arr-portal" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L6,3 z" fill="#f59e0b" />
                </marker>
                <marker id="geo-arr-portal-cur" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L6,3 z" fill="#34d399" />
                </marker>
                <filter id="geo-glow">
                    <feGaussianBlur stdDeviation="2" result="blur" />
                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
            </defs>

            <g x-ref="continentsLayer"></g>
            <g x-ref="edgesLayer"></g>
            <g x-ref="portalsLayer"></g>
            <g x-ref="nodesLayer"></g>
        </svg>
    </div>

    {{-- Compact continent label --}}
    @if ($compact)
        <div class="mt-1 text-center font-mono text-[8px] uppercase tracking-widest text-zinc-600" x-text="continents[view]?.label ?? ''"></div>
    @endif
</div>

<script>
(function () {
    if (window.__geoMapDefined) { return; }
    window.__geoMapDefined = true;

    // ---------------------------------------------------------------------------
    // Simplified continent outline coordinates  [lat, lng] clockwise
    // ---------------------------------------------------------------------------
    const OUTLINES = {
        africa: [
            [37.3,-5.5],[33.5,-8],[27,-13],[21,-17],[15,-17.5],[11.5,-15],
            [8,-11.5],[5.5,-5],[5,2],[4.3,7.5],[4,9.5],[0.5,9.5],
            [-4.5,12],[-5.5,12.3],[-10.5,13.5],[-17,12],[-23,14.5],
            [-29,17],[-34.4,18.5],[-34,26.5],[-29.5,31],[-20.5,35.3],
            [-15,40.5],[-4.5,39.5],[-1.5,41.5],[2.5,45.3],[11.8,51.3],
            [15,41.5],[22,37.5],[27,34.5],[31,32.3],[31.5,25.3],
            [32.5,23],[33.5,11.5],[37,10.5],[37.5,8],[36.5,2],[35.8,-0.5],
        ],
        europe: [
            [71.5,26],[70,20],[68,18.5],[66,15],[63,8],[59,5],[58,5.5],
            [55,8],[53.5,7.5],[52,4.5],[51,3.5],[50.5,2.5],
            [49.5,-1.5],[48.5,-4.5],[47.5,-2.5],[46,-1],[43.5,-1.8],
            [43.5,-8.5],[38.7,-9.5],[37,-9],[36,-7],[36,-5.5],
            [36,-1],[37.5,0.5],[43,3.5],[44,8],
            [44.5,13.5],[45.5,14.5],[43.5,16.5],[42.5,18.5],
            [41,19.5],[39.5,20],[37.5,23.5],[40,26],[41,28.5],
            [43.5,28.5],[46.5,30.5],[46.5,37.5],[47,39],
            [50,38.5],[54,32],[56.5,31],[59.5,28],
            [60.5,25],[60,24.8],[57.5,21.5],[54.5,18.5],
            [54.5,14.5],[55.5,10.5],[57,8],[60,5],[63,8],
            [66,15],[68,18.5],[70,20],[71.5,26],
        ],
        northAmerica: [
            [71,-141],[71,-156],[63,-170],[55,-130],[48,-124.5],
            [42,-124.5],[34.5,-120],[32.5,-117],[29,-115],
            [22.9,-109.5],[19.5,-105],[15.7,-92.5],[15.7,-88],
            [21.5,-87],[21.3,-86.7],[25,-80.2],[25,-80],
            [30.5,-81],[35,-75.5],[38.5,-74.9],[41,-71.5],
            [43,-70.5],[44.5,-66.9],[47,-53],[52,-55],
            [58,-62],[60,-64.5],[63,-68],[65,-73],[69,-83],
            [70,-95],[71,-120],[71,-141],
        ],
        southAmerica: [
            [11,-74],[8.5,-77],[5,-77.5],[1.5,-80.5],[0.5,-80],
            [-1.5,-80],[-3.5,-81],[-14,-76.5],[-18.5,-70.8],
            [-21.5,-70.3],[-30,-71.5],[-38,-73.5],[-42,-74],
            [-46.5,-75.5],[-52,-74],[-55,-67],[-55.5,-64],
            [-54,-57],[-51.5,-50.5],[-48,-44.5],[-44.5,-37],
            [-28,-48.5],[-23,-43.3],[-7.5,-35],[-4.5,-37],
            [-2.5,-41.5],[-1.5,-48.5],[1,-50],[4,-52],
            [5.5,-54],[7.5,-58],[8,-60],[10.5,-61.5],
            [11,-65],[10.5,-68.5],[11,-74],
        ],
        asia: [
            [40,26.5],[42,35.5],[36.5,36.5],[36.5,42],[30,49],[27,49.5],
            [22,60],[20,68],[8,77.5],[8.4,80.5],[7.5,80.5],
            [22,88.5],[20,92.5],[10.5,98.5],[1,103.5],[5.5,100.5],
            [10,98.5],[16,98],[22.5,101],[20.5,107],[10.5,107],
            [21,108],[22,113.5],[26,119.5],[30,122],[37,122.5],
            [40,122],[41.5,122.5],[42.5,130],[42,131.5],
            [46.5,137],[51,141.5],[55.5,137],[59,143],
            [63,142],[66,142],[68,161],[70,170],
            [73,140],[74.5,100],[73.5,80],[72,72],
            [69.5,58],[65,59],[60,60.5],
            [57,61],[54,53],[52,51],[50,51.5],
            [47.5,39.5],[43,41],[42,44],[40.5,50],[38.5,49.5],
            [36,50.5],[36,45],[39,44.5],[41.5,41.5],[41,35.5],
            [40,32],[40,26.5],
        ],
        japan: [
            [31,130.5],[33.5,130.8],[35.5,136],[36.5,137],[38,141],
            [40.5,141.5],[43,141.5],[44.5,141.5],[44,142],[44,145.5],
            [43,145.5],[43.5,141.5],[40.5,140.5],[36.5,140.5],
            [35,136],[34,133],[33,131.5],[31,130.5],
        ],
        australia: [
            [-16,136],[-12,136.5],[-13.5,130.5],[-11.5,130],[-14,124.5],
            [-20,114],[-27,113.5],[-34.5,115],[-35,117.5],[-33.5,122],
            [-34,135],[-38,140],[-38.5,146.5],[-37,150],[-28,153.5],
            [-23.5,151],[-10,142],[-14,136],[-16,136],
        ],
        greenland: [
            [76,-73],[84,-40],[83,-20],[76,-18],[70,-24],
            [66,-43],[65,-52],[68,-58],[72,-68],[76,-73],
        ],
    };

    // ---------------------------------------------------------------------------
    // Continent view-box definitions [x, y, w, h] in the 1000×500 SVG space
    // ---------------------------------------------------------------------------
    const VIEWBOXES = {
        world:        [0, 0, 1000, 500],
        europe:       [420, 22, 250, 155],
        northAmerica: [10, 18, 370, 210],
        asia:         [550, 10, 465, 400],
        southAmerica: [238, 182, 200, 265],
        africa:       [415, 110, 275, 280],
    };

    // ---------------------------------------------------------------------------
    // Shapes to draw for each view
    // ---------------------------------------------------------------------------
    const VIEW_SHAPES = {
        world:        ['africa', 'europe', 'northAmerica', 'southAmerica', 'asia', 'japan', 'australia', 'greenland'],
        europe:       ['europe'],
        northAmerica: ['northAmerica', 'greenland'],
        asia:         ['asia', 'japan', 'australia'],
        southAmerica: ['southAmerica'],
        africa:       ['africa'],
    };

    // ---------------------------------------------------------------------------
    // Main Alpine component
    // ---------------------------------------------------------------------------
    function geoMap(mapData, currentCityId, compact, dark) {
        return {
            mapData,
            currentCityId,
            compact,
            dark,
            view: compact ? '' : 'world',
            currentVB: [...VIEWBOXES.world],
            tooltip: { visible: false, x: 0, y: 0, label: '', country: '', biome: '', lat: null, lng: null },
            W: 1000,
            H: 500,

            continents: {
                europe:       { label: 'Europe',    cityIds: null, color: '#60a5fa' },
                northAmerica: { label: 'N.America',  cityIds: null, color: '#34d399' },
                asia:         { label: 'Asia',       cityIds: null, color: '#f87171' },
                southAmerica: { label: 'S.America',  cityIds: null, color: '#fb923c' },
                africa:       { label: 'Africa',     cityIds: null, color: '#facc15' },
            },

            get isDark() {
                return this.dark || document.documentElement.classList.contains('dark');
            },

            init() {
                // Build cityIds per continent from node continent data
                for (const node of this.mapData.nodes) {
                    const key = this.continentKey(node.continent);
                    if (key && this.continents[key]) {
                        if (!this.continents[key].cityIds) { this.continents[key].cityIds = []; }
                        this.continents[key].cityIds.push(node.id);
                    }
                }

                // Default view
                if (this.compact) {
                    this.view = this.detectContinent(this.currentCityId) || Object.keys(this.continents)[0];
                }
                if (!this.view) { this.view = 'world'; }

                this.currentVB = [...(VIEWBOXES[this.view] || VIEWBOXES.world)];
                this.$refs.svgEl?.setAttribute('viewBox', this.currentVB.join(' '));
                this.$nextTick(() => this.renderAll());
            },

            continentKey(continent) {
                const map = {
                    'Europe': 'europe',
                    'North America': 'northAmerica',
                    'Asia': 'asia',
                    'South America': 'southAmerica',
                    'Africa': 'africa',
                };
                return map[continent] ?? null;
            },

            detectContinent(cityId) {
                if (!cityId) { return null; }
                const node = this.mapData.nodes.find(n => n.id === cityId);
                return node ? this.continentKey(node.continent) : null;
            },

            setView(key) {
                if (this.view === key) { return; }
                this.view = key;
                const from = [...this.currentVB];
                const to = [...(VIEWBOXES[key] || VIEWBOXES.world)];
                this.animateViewBox(from, to, 480);
            },

            animateViewBox(from, to, duration) {
                const svgEl = this.$refs.svgEl;
                if (!svgEl) { return; }
                const start = performance.now();
                const tick = (now) => {
                    let t = Math.min((now - start) / duration, 1);
                    t = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
                    const vb = from.map((v, i) => v + (to[i] - v) * t);
                    svgEl.setAttribute('viewBox', vb.join(' '));
                    this.currentVB = vb;
                    if (t < 1) {
                        requestAnimationFrame(tick);
                    } else {
                        this.currentVB = [...to];
                        svgEl.setAttribute('viewBox', to.join(' '));
                        this.renderAll();
                    }
                };
                requestAnimationFrame(tick);
                // Re-render immediately for new visible nodes/edges
                this.$nextTick(() => this.renderAll());
            },

            project(lat, lng) {
                return {
                    x: (lng + 180) / 360 * this.W,
                    y: (90 - lat) / 180 * this.H,
                };
            },

            makePathD(coords) {
                return coords.map((c, i) => {
                    const p = this.project(c[0], c[1]);
                    return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ',' + p.y.toFixed(1);
                }).join(' ') + ' Z';
            },

            renderAll() {
                this.renderContinents();
                this.renderEdges();
                this.renderPortals();
                this.renderNodes();
            },

            renderContinents() {
                const layer = this.$refs.continentsLayer;
                if (!layer) { return; }
                layer.replaceChildren();

                const shapes = VIEW_SHAPES[this.view] || VIEW_SHAPES.world;
                const landFill   = this.isDark ? '#1c2030' : '#d1fae5';
                const landStroke = this.isDark ? '#374151' : '#6ee7b7';
                const strokeW    = this.view === 'world' ? 0.6 : 1.0;

                for (const key of shapes) {
                    const coords = OUTLINES[key];
                    if (!coords) { continue; }
                    layer.append(this.mkSvg('path', {
                        d: this.makePathD(coords),
                        fill: landFill,
                        stroke: landStroke,
                        'stroke-width': strokeW,
                        'stroke-linejoin': 'round',
                    }));
                }

                // Graticule lines for world view (subtle grid)
                if (this.view === 'world') {
                    const gc = this.isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.05)';
                    const grp = this.mkSvg('g', {});
                    for (let lng = -150; lng <= 180; lng += 30) {
                        const x = (lng + 180) / 360 * this.W;
                        grp.append(this.mkSvg('line', { x1: x, y1: 0, x2: x, y2: this.H, stroke: gc, 'stroke-width': 0.5 }));
                    }
                    for (let lat = -60; lat <= 60; lat += 30) {
                        const y = (90 - lat) / 180 * this.H;
                        grp.append(this.mkSvg('line', { x1: 0, y1: y, x2: this.W, y2: y, stroke: gc, 'stroke-width': 0.5 }));
                    }
                    layer.append(grp);
                }
            },

            renderEdges() {
                const layer = this.$refs.edgesLayer;
                if (!layer) { return; }
                layer.replaceChildren();

                const nodeById = {};
                this.mapData.nodes.forEach(n => { nodeById[n.id] = n; });

                const localIds = this.localCityIds();
                const isWorldView = this.view === 'world';
                const r = isWorldView ? 5 : 8;
                const offset = isWorldView ? 2 : 4;

                for (const edge of this.mapData.edges) {
                    const from = nodeById[edge.from];
                    const to   = nodeById[edge.to];
                    if (!from?.lat || !to?.lat) { continue; }

                    const fromLocal = isWorldView || localIds.includes(edge.from);
                    const toLocal   = isWorldView || localIds.includes(edge.to);

                    // Skip edges from off-continent cities; portals handled separately
                    if (!fromLocal || !toLocal) { continue; }

                    const fp = this.project(from.lat, from.lng);
                    const tp = this.project(to.lat, to.lng);
                    const dx = tp.x - fp.x, dy = tp.y - fp.y;
                    const d  = Math.sqrt(dx * dx + dy * dy) || 1;

                    const hasPair = this.mapData.edges.some(e => e.from === edge.to && e.to === edge.from);
                    const off     = hasPair ? offset : 0;
                    const px = -dy / d * off, py = dx / d * off;

                    const isCurrent = edge.from === this.currentCityId || edge.to === this.currentCityId;
                    layer.append(this.mkSvg('line', {
                        x1: fp.x + (dx / d) * r + px,
                        y1: fp.y + (dy / d) * r + py,
                        x2: tp.x - (dx / d) * (r + 5) + px,
                        y2: tp.y - (dy / d) * (r + 5) + py,
                        stroke:         isCurrent ? '#34d399' : (this.isDark ? '#3f3f46' : '#9ca3af'),
                        'stroke-width': isCurrent ? 1.5 : 1,
                        'marker-end':   isCurrent ? 'url(#geo-arr-cur)' : 'url(#geo-arr-def)',
                    }));
                }
            },

            renderPortals() {
                const layer = this.$refs.portalsLayer;
                if (!layer) { return; }
                layer.replaceChildren();

                if (this.view === 'world') { return; }

                const nodeById = {};
                this.mapData.nodes.forEach(n => { nodeById[n.id] = n; });

                const localIds = this.localCityIds();
                const vb = VIEWBOXES[this.view] || VIEWBOXES.world;
                const [vbX, vbY, vbW, vbH] = vb;

                for (const edge of this.mapData.edges) {
                    const from = nodeById[edge.from];
                    const to   = nodeById[edge.to];
                    if (!from?.lat || !to?.lat) { continue; }

                    const fromLocal = localIds.includes(edge.from);
                    const toLocal   = localIds.includes(edge.to);
                    if (!fromLocal || toLocal) { continue; } // only from-local to-offscreen

                    const fp   = this.project(from.lat, from.lng);
                    const tp   = this.project(to.lat, to.lng);
                    const exit = this.lineVBExit(fp.x, fp.y, tp.x, tp.y, vbX, vbY, vbW, vbH);
                    if (!exit) { continue; }

                    const isCurrent = edge.from === this.currentCityId;
                    const color     = isCurrent ? '#34d399' : '#f59e0b';
                    const arrowId   = isCurrent ? 'url(#geo-arr-portal-cur)' : 'url(#geo-arr-portal)';

                    // Dashed line toward exit
                    layer.append(this.mkSvg('line', {
                        x1: fp.x, y1: fp.y, x2: exit.x, y2: exit.y,
                        stroke: color, 'stroke-width': 1,
                        'stroke-dasharray': '5,3',
                        'marker-end': arrowId,
                        opacity: 0.8,
                    }));

                    // Label near exit point
                    const onLeft = exit.x < vbX + vbW / 2;
                    const onTop  = exit.y < vbY + vbH / 2;
                    const lx     = exit.x + (onLeft ? 6 : -6);
                    const ly     = exit.y + (onTop  ? 10 : -4);
                    const anchor = onLeft ? 'start' : 'end';
                    const fontSize = this.compact ? 6.5 : 8.5;

                    const g = this.mkSvg('g', { transform: `translate(${lx.toFixed(1)},${ly.toFixed(1)})` });
                    const t1 = this.mkSvg('text', { 'text-anchor': anchor, 'font-size': fontSize, fill: color, style: 'font-family:monospace;font-weight:600;' });
                    t1.textContent = '→ ' + to.label;
                    const t2 = this.mkSvg('text', { 'text-anchor': anchor, y: fontSize + 2, 'font-size': fontSize - 1.5, fill: this.isDark ? '#6b7280' : '#9ca3af', style: 'font-family:monospace;' });
                    t2.textContent = to.continent;
                    g.append(t1, t2);
                    layer.append(g);
                }
            },

            renderNodes() {
                const layer = this.$refs.nodesLayer;
                if (!layer) { return; }
                layer.replaceChildren();

                const localIds   = this.localCityIds();
                const isWorldView = this.view === 'world';
                const fontSize    = isWorldView ? 8 : (this.compact ? 7 : 9.5);
                const rNorm       = isWorldView ? 5 : (this.compact ? 7 : 8);
                const rCur        = isWorldView ? 7 : (this.compact ? 9 : 11);

                for (const node of this.mapData.nodes) {
                    if (!node.lat || !node.lng) { continue; }
                    if (!isWorldView && !localIds.includes(node.id)) { continue; }

                    const p         = this.project(node.lat, node.lng);
                    const isCurrent = node.id === this.currentCityId;
                    const r         = isCurrent ? rCur : rNorm;

                    const g = this.mkSvg('g', { transform: `translate(${p.x.toFixed(1)},${p.y.toFixed(1)})`, style: 'cursor:pointer' });
                    g.addEventListener('mouseenter', (e) => this.showTooltip(node, p, e));
                    g.addEventListener('mouseleave', () => { this.tooltip.visible = false; });

                    // Glow ring for current city
                    if (isCurrent) {
                        g.append(this.mkSvg('circle', {
                            r: r + 4,
                            fill: 'none',
                            stroke: '#34d399',
                            'stroke-width': 1,
                            opacity: 0.35,
                        }));
                    }

                    g.append(this.mkSvg('circle', {
                        r,
                        fill:         isCurrent ? '#10b981' : (this.isDark ? '#3f3f46' : '#e4e4e7'),
                        stroke:       isCurrent ? '#6ee7b7' : (this.isDark ? '#71717a' : '#6b7280'),
                        'stroke-width': isCurrent ? 2 : 1.5,
                        filter:       isCurrent ? 'url(#geo-glow)' : '',
                    }));

                    // Labels only in continent view
                    if (!isWorldView) {
                        const labelY = r + (this.compact ? 8 : 11);
                        const txt = this.mkSvg('text', {
                            'text-anchor': 'middle',
                            y: labelY,
                            'font-size': fontSize,
                            fill: isCurrent ? (this.isDark ? '#6ee7b7' : '#059669') : (this.isDark ? '#9ca3af' : '#374151'),
                            style: 'pointer-events:none;user-select:none;font-family:monospace;',
                        });
                        txt.textContent = node.label;
                        g.append(txt);
                    }

                    layer.append(g);
                }
            },

            localCityIds() {
                if (this.view === 'world') {
                    return this.mapData.nodes.map(n => n.id);
                }
                const cfg = this.continents[this.view];
                return cfg?.cityIds ?? [];
            },

            lineVBExit(x1, y1, x2, y2, vbX, vbY, vbW, vbH) {
                const dx = x2 - x1, dy = y2 - y1;
                const ts = [];
                if (Math.abs(dx) > 0.001) {
                    ts.push((vbX - x1) / dx, (vbX + vbW - x1) / dx);
                }
                if (Math.abs(dy) > 0.001) {
                    ts.push((vbY - y1) / dy, (vbY + vbH - y1) / dy);
                }
                for (const t of ts.filter(t => t > 0.05).sort((a, b) => a - b)) {
                    const ix = x1 + t * dx, iy = y1 + t * dy;
                    if (ix >= vbX - 1 && ix <= vbX + vbW + 1 && iy >= vbY - 1 && iy <= vbY + vbH + 1) {
                        return { x: Math.min(Math.max(ix, vbX), vbX + vbW), y: Math.min(Math.max(iy, vbY), vbY + vbH) };
                    }
                }
                return null;
            },

            showTooltip(node, svgPt) {
                const svgEl = this.$refs.svgEl;
                if (!svgEl) { return; }
                const rect   = svgEl.getBoundingClientRect();
                const vb     = this.currentVB;
                const scaleX = rect.width  / vb[2];
                const scaleY = rect.height / vb[3];
                this.tooltip = {
                    visible: true,
                    x: (svgPt.x - vb[0]) * scaleX + 16,
                    y: (svgPt.y - vb[1]) * scaleY - 10,
                    label:   node.label,
                    country: node.country,
                    biome:   node.biome,
                    lat:     node.lat ?? null,
                    lng:     node.lng ?? null,
                };
            },

            mkSvg(tag, attrs) {
                const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
                for (const [k, v] of Object.entries(attrs)) {
                    if (v != null && v !== '') { el.setAttribute(k, String(v)); }
                }
                return el;
            },
        };
    }

    window.geoMap = geoMap;
})();
</script>
