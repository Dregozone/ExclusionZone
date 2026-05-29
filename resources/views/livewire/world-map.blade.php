<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('World Map') }}</flux:heading>
        <flux:text class="mt-1">{{ __('All cities and their travel connections. Arrows show the allowed direction of travel.') }}</flux:text>
    </div>

    @php
        $mapData = $this->mapData;
        $currentCityId = $this->currentCityId;
    @endphp

    <div
        x-data="worldMap(@js($mapData), @js($currentCityId))"
        x-init="init()"
        class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
    >
        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-6 border-b border-zinc-100 px-6 py-4 dark:border-zinc-800 text-sm">
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-emerald-300 dark:ring-emerald-700"></div>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Your location') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-zinc-400 dark:bg-zinc-600"></div>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('City') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <svg width="28" height="12" class="shrink-0">
                    <line x1="2" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="1.5" class="text-zinc-400 dark:text-zinc-600" />
                    <polygon points="20,3 26,6 20,9" fill="currentColor" class="text-zinc-400 dark:text-zinc-600" />
                </svg>
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('Travel route') }}</span>
            </div>
        </div>

        {{-- Tooltip --}}
        <div
            x-show="tooltip.visible"
            x-cloak
            :style="`left: ${tooltip.x}px; top: ${tooltip.y}px`"
            class="pointer-events-none absolute z-10 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
        >
            <div class="font-semibold text-zinc-900 dark:text-white" x-text="tooltip.label"></div>
            <div class="text-zinc-500 dark:text-zinc-400" x-text="tooltip.country"></div>
            <div class="mt-0.5 text-zinc-500 dark:text-zinc-400 italic" x-text="tooltip.biome"></div>
        </div>

        {{-- SVG canvas --}}
        <div class="relative w-full" x-ref="container">
            <svg
                x-ref="svg"
                :viewBox="`0 0 ${width} ${height}`"
                :width="width"
                :height="height"
                class="w-full block"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <marker id="arrow-default" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                        <path d="M0,0 L0,6 L8,3 z" class="fill-zinc-400 dark:fill-zinc-600" />
                    </marker>
                    <marker id="arrow-current" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto">
                        <path d="M0,0 L0,6 L8,3 z" class="fill-emerald-400" />
                    </marker>
                </defs>

                <g x-ref="edgesLayer" data-map-layer="edges"></g>
                <g x-ref="nodesLayer" data-map-layer="nodes"></g>
            </svg>
        </div>
    </div>
</section>

<script>
function worldMap(mapData, currentCityId) {
    return {
        mapData,
        currentCityId,
        nodes: [],
        edges: [],
        width: 900,
        height: 600,
        tooltip: { visible: false, x: 0, y: 0, label: '', country: '', biome: '' },

        init() {
            this.width = this.$refs.container?.offsetWidth || 900;
            this.height = Math.max(500, Math.round(this.width * 0.6));

            this.layoutNodes(this.mapData.nodes, this.mapData.edges);
            this.computeEdges(this.mapData.edges);
            this.renderSvg();

            this.$nextTick(() => {
                this.$refs.svg?.setAttribute('viewBox', `0 0 ${this.width} ${this.height}`);
            });
        },

        layoutNodes(rawNodes, rawEdges) {
            const nodeCount = rawNodes.length;
            const padding = 80;
            const w = this.width - padding * 2;
            const h = this.height - padding * 2;

            // Build adjacency for force-directed-like spring layout
            const pos = {};
            rawNodes.forEach((n, i) => {
                const angle = (2 * Math.PI * i) / nodeCount;
                const rx = w / 2.5;
                const ry = h / 2.5;
                pos[n.id] = {
                    x: this.width / 2 + rx * Math.cos(angle),
                    y: this.height / 2 + ry * Math.sin(angle),
                };
            });

            // Simple force-directed iterations
            const edgeMap = {};
            rawEdges.forEach(e => {
                edgeMap[`${e.from}-${e.to}`] = true;
            });

            const isConnected = (a, b) => edgeMap[`${a}-${b}`] || edgeMap[`${b}-${a}`];
            const idealLength = Math.min(w, h) / Math.sqrt(nodeCount) * 1.8;
            const repelDist = idealLength * 0.7;

            for (let iter = 0; iter < 200; iter++) {
                const forces = {};
                rawNodes.forEach(n => { forces[n.id] = { x: 0, y: 0 }; });

                // Spring attraction for edges
                rawEdges.forEach(e => {
                    const a = pos[e.from], b = pos[e.to];
                    if (!a || !b) return;
                    const dx = b.x - a.x, dy = b.y - a.y;
                    const d = Math.sqrt(dx * dx + dy * dy) || 1;
                    const f = (d - idealLength) * 0.05;
                    forces[e.from].x += (dx / d) * f;
                    forces[e.from].y += (dy / d) * f;
                    forces[e.to].x -= (dx / d) * f;
                    forces[e.to].y -= (dy / d) * f;
                });

                // Repulsion between all pairs
                for (let i = 0; i < rawNodes.length; i++) {
                    for (let j = i + 1; j < rawNodes.length; j++) {
                        const a = pos[rawNodes[i].id], b = pos[rawNodes[j].id];
                        const dx = b.x - a.x, dy = b.y - a.y;
                        const d = Math.sqrt(dx * dx + dy * dy) || 1;
                        if (d < repelDist) {
                            const f = (repelDist - d) / d * 0.4;
                            forces[rawNodes[i].id].x -= dx * f;
                            forces[rawNodes[i].id].y -= dy * f;
                            forces[rawNodes[j].id].x += dx * f;
                            forces[rawNodes[j].id].y += dy * f;
                        }
                    }
                }

                // Center pull
                rawNodes.forEach(n => {
                    const cx = this.width / 2, cy = this.height / 2;
                    forces[n.id].x += (cx - pos[n.id].x) * 0.005;
                    forces[n.id].y += (cy - pos[n.id].y) * 0.005;
                });

                // Apply
                rawNodes.forEach(n => {
                    pos[n.id].x = Math.max(padding, Math.min(this.width - padding, pos[n.id].x + forces[n.id].x));
                    pos[n.id].y = Math.max(padding, Math.min(this.height - padding, pos[n.id].y + forces[n.id].y));
                });
            }

            this.nodes = rawNodes.map(n => ({
                id: n.id,
                label: n.label,
                country: n.country,
                biome: n.biome,
                x: Math.round(pos[n.id].x),
                y: Math.round(pos[n.id].y),
            }));
        },

        computeEdges(rawEdges) {
            const nodeById = {};
            this.nodes.forEach(n => { nodeById[n.id] = n; });

            const r = 12;

            this.edges = rawEdges.map(e => {
                const from = nodeById[e.from], to = nodeById[e.to];
                if (!from || !to) return null;

                const dx = to.x - from.x, dy = to.y - from.y;
                const d = Math.sqrt(dx * dx + dy * dy) || 1;

                // Offset slightly so bidirectional edges don't overlap
                const hasPair = rawEdges.some(re => re.from === e.to && re.to === e.from);
                const offset = hasPair ? 5 : 0;
                const px = -dy / d * offset, py = dx / d * offset;

                const x1 = from.x + (dx / d) * r + px;
                const y1 = from.y + (dy / d) * r + py;
                const x2 = to.x - (dx / d) * (r + 8) + px;
                const y2 = to.y - (dy / d) * (r + 8) + py;

                const isCurrent = e.from === this.currentCityId || e.to === this.currentCityId;
                return { from: e.from, to: e.to, x1, y1, x2, y2, isCurrent };
            }).filter(Boolean);
        },

        renderSvg() {
            const edgesLayer = this.$refs.edgesLayer;
            const nodesLayer = this.$refs.nodesLayer;

            if (!edgesLayer || !nodesLayer) {
                return;
            }

            edgesLayer.replaceChildren();
            nodesLayer.replaceChildren();

            this.edges.forEach(edge => {
                edgesLayer.append(this.createSvgElement('line', {
                    x1: edge.x1,
                    y1: edge.y1,
                    x2: edge.x2,
                    y2: edge.y2,
                    class: edge.isCurrent ? 'stroke-emerald-400' : 'stroke-zinc-300 dark:stroke-zinc-600',
                    'stroke-width': 1.5,
                    'marker-end': edge.isCurrent ? 'url(#arrow-current)' : 'url(#arrow-default)',
                }));
            });

            this.nodes.forEach(node => {
                const isCurrent = node.id === this.currentCityId;
                const group = this.createSvgElement('g', {
                    transform: `translate(${node.x}, ${node.y})`,
                    class: 'cursor-pointer',
                });

                group.addEventListener('mouseenter', () => this.showTooltip(node));
                group.addEventListener('mouseleave', () => this.hideTooltip());

                group.append(
                    this.createSvgElement('circle', {
                        r: isCurrent ? 14 : 10,
                        class: isCurrent
                            ? 'fill-emerald-500 stroke-emerald-300 dark:stroke-emerald-700'
                            : 'fill-zinc-200 stroke-zinc-400 dark:fill-zinc-700 dark:stroke-zinc-500',
                        'stroke-width': 2,
                    }),
                    this.createSvgElement('text', {
                        'text-anchor': 'middle',
                        'dominant-baseline': 'middle',
                        y: isCurrent ? 28 : 24,
                        'font-size': 11,
                        class: isCurrent
                            ? 'fill-emerald-600 dark:fill-emerald-400 font-semibold'
                            : 'fill-zinc-700 dark:fill-zinc-300',
                        style: 'pointer-events:none; user-select:none;',
                    }, node.label)
                );

                nodesLayer.append(group);
            });
        },

        createSvgElement(tagName, attributes, textContent = null) {
            const element = document.createElementNS('http://www.w3.org/2000/svg', tagName);

            Object.entries(attributes).forEach(([name, value]) => {
                if (value === null || value === undefined) {
                    return;
                }

                element.setAttribute(name, String(value));
            });

            if (textContent !== null) {
                element.textContent = textContent;
            }

            return element;
        },

        showTooltip(node) {
            const svgEl = this.$refs.svg;
            if (!svgEl) {
                return;
            }

            const svgRect = svgEl.getBoundingClientRect();
            const scaleX = svgRect.width / this.width;
            const scaleY = svgRect.height / this.height;

            this.tooltip = {
                visible: true,
                x: node.x * scaleX + 16,
                y: node.y * scaleY - 10,
                label: node.label,
                country: node.country,
                biome: node.biome,
            };
        },

        hideTooltip() {
            this.tooltip.visible = false;
        },
    };
}
</script>
