/**
 * Interactive world map (Alpine component `geoMap`).
 *
 * The geography (coastlines and borders) lives in world units inside one <g>
 * that is scaled as the player zooms. Cities, routes and labels are drawn in
 * screen pixels on top, so they stay the same readable size at any zoom.
 */

const SVG_NS = 'http://www.w3.org/2000/svg';

/* Keep these in step with build-world-geometry.mjs. */
const MAP_WIDTH = 1000;
const LAT_TOP = 84;
const LAT_BOTTOM = -60;
const Y_SCALE = MAP_WIDTH / (2 * Math.PI);
const mercatorY = (lat) => Math.log(Math.tan(Math.PI / 4 + (lat * Math.PI) / 360));
const TOP = mercatorY(LAT_TOP) * Y_SCALE;
const MAP_HEIGHT = TOP - mercatorY(LAT_BOTTOM) * Y_SCALE;

const CONTINENT_ORDER = ['Europe', 'Asia', 'Africa', 'North America', 'South America', 'Oceania'];

const THEMES = {
    dark: {
        ocean: '#0a0f1a',
        land: '#1a2331',
        zoneLand: '#1b332f',
        border: 'rgba(148, 163, 184, 0.22)',
        coast: '#33435a',
        graticule: 'rgba(148, 163, 184, 0.07)',
        route: 'rgba(148, 163, 184, 0.38)',
        routeFromYou: '#34d399',
        path: '#6ee7b7',
        transit: '#fbbf24',
        city: '#64748b',
        cityStroke: '#0a0f1a',
        reachable: '#38bdf8',
        current: '#10b981',
        objective: '#f59e0b',
        selected: '#f8fafc',
        label: '#e2e8f0',
        labelMuted: '#94a3b8',
        labelCurrent: '#6ee7b7',
        labelReachable: '#7dd3fc',
        halo: 'rgba(10, 15, 26, 0.92)',
        countryLabel: 'rgba(148, 163, 184, 0.5)',
    },
    light: {
        ocean: '#d9e8f5',
        land: '#f8fafc',
        zoneLand: '#e3f5ea',
        border: 'rgba(71, 85, 105, 0.22)',
        coast: '#9fb0c4',
        graticule: 'rgba(30, 64, 175, 0.07)',
        route: 'rgba(71, 85, 105, 0.45)',
        routeFromYou: '#059669',
        path: '#047857',
        transit: '#d97706',
        city: '#94a3b8',
        cityStroke: '#ffffff',
        reachable: '#0284c7',
        current: '#059669',
        objective: '#d97706',
        selected: '#0f172a',
        label: '#0f172a',
        labelMuted: '#475569',
        labelCurrent: '#047857',
        labelReachable: '#0369a1',
        halo: 'rgba(255, 255, 255, 0.92)',
        countryLabel: 'rgba(71, 85, 105, 0.55)',
    },
};

/** City projected into world units. */
export function project(lat, lng) {
    const clamped = Math.max(LAT_BOTTOM, Math.min(LAT_TOP, lat));

    return { x: ((lng + 180) / 360) * MAP_WIDTH, y: TOP - mercatorY(clamped) * Y_SCALE };
}

let geometryPromise = null;

/** Geometry is shared by every map on the page and loaded on first use. */
function loadGeometry() {
    geometryPromise ??= import('./world-geometry.json').then((module) => buildGeometry(module.default ?? module));

    return geometryPromise;
}

function buildGeometry(topology) {
    if (topology.projection.width !== MAP_WIDTH || topology.projection.latTop !== LAT_TOP) {
        console.warn('[geo-map] world-geometry.json was built with a different projection; rebuild it.');
    }

    const precision = topology.precision;
    const arcs = topology.arcs.map((flat) => {
        const points = [];
        let x = 0;
        let y = 0;

        for (let i = 0; i < flat.length; i += 2) {
            x += flat[i];
            y += flat[i + 1];
            points.push([x / precision, y / precision]);
        }

        return points;
    });

    const arcUse = new Array(arcs.length).fill(0);
    const ringFrom = (ring) => {
        const points = [];

        for (const index of ring) {
            const arc = index < 0 ? [...arcs[~index]].reverse() : arcs[index];
            arcUse[index < 0 ? ~index : index]++;
            points.push(...(points.length ? arc.slice(1) : arc));
        }

        return points;
    };
    const pathFrom = (points) => 'M' + points.map(([x, y]) => `${+x.toFixed(2)},${+y.toFixed(2)}`).join('L') + 'Z';

    /*
     * A few countries (Russia, Fiji) cross the 180° meridian, so their rings jump
     * from one edge of the map to the other. Unwrap them into one continuous
     * shape, then add a copy shifted by a map width; the map clip hides the rest.
     */
    const unwrapPolygon = (rings) => {
        let crosses = false;
        const unwrapped = rings.map((ring) => {
            let offset = 0;

            return ring.map(([x, y], i) => {
                if (i > 0) {
                    const previous = ring[i - 1][0];

                    if (x - previous > MAP_WIDTH / 2) {
                        offset -= MAP_WIDTH;
                        crosses = true;
                    } else if (previous - x > MAP_WIDTH / 2) {
                        offset += MAP_WIDTH;
                        crosses = true;
                    }
                }

                return [x + offset, y];
            });
        });

        if (!crosses) {
            return [rings];
        }

        const minX = Math.min(...unwrapped[0].map(([x]) => x));
        const shift = minX < 0 ? MAP_WIDTH : -MAP_WIDTH;

        return [unwrapped, unwrapped.map((ring) => ring.map(([x, y]) => [x + shift, y]))];
    };

    const countries = topology.countries.map((country) => {
        const polygons = country.p.map((rings) => rings.map(ringFrom)).flatMap(unwrapPolygon);
        let largest = polygons[0];
        let largestArea = 0;

        for (const polygon of polygons) {
            const area = Math.abs(signedArea(polygon[0]));

            if (area > largestArea) {
                largestArea = area;
                largest = polygon;
            }
        }

        return {
            name: country.n,
            polygons,
            d: polygons.map((rings) => rings.map(pathFrom).join('')).join(''),
            labelPoint: centroid(largest[0]),
            labelArea: largestArea,
        };
    });

    const linesFrom = (filter) => arcs
        .filter((_, i) => filter(arcUse[i]))
        .map((points) => points
            .map(([x, y], i) => {
                const jumps = i > 0 && Math.abs(x - points[i - 1][0]) > MAP_WIDTH / 2;

                return `${i === 0 || jumps ? 'M' : 'L'}${+x.toFixed(2)},${+y.toFixed(2)}`;
            })
            .join(''))
        .join('');

    return {
        countries,
        coastD: linesFrom((uses) => uses === 1),
        bordersD: linesFrom((uses) => uses > 1),
    };
}

function signedArea(points) {
    let area = 0;

    for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
        area += (points[j][0] + points[i][0]) * (points[j][1] - points[i][1]);
    }

    return area / 2;
}

function centroid(points) {
    let x = 0;
    let y = 0;
    let area = 0;

    for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
        const cross = points[j][0] * points[i][1] - points[i][0] * points[j][1];
        area += cross;
        x += (points[j][0] + points[i][0]) * cross;
        y += (points[j][1] + points[i][1]) * cross;
    }

    return area === 0 ? points[0] : [x / (3 * area), y / (3 * area)];
}

function pointInRing([px, py], ring) {
    let inside = false;

    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];

        if ((yi > py) !== (yj > py) && px < ((xj - xi) * (py - yi)) / (yj - yi) + xi) {
            inside = !inside;
        }
    }

    return inside;
}

function pointInCountry(point, country) {
    return country.polygons.some((rings) => pointInRing(point, rings[0]) && !rings.slice(1).some((hole) => pointInRing(point, hole)));
}

function distanceToSegment([px, py], [ax, ay], [bx, by]) {
    const dx = bx - ax;
    const dy = by - ay;
    const lengthSquared = dx * dx + dy * dy;
    const t = lengthSquared === 0 ? 0 : Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / lengthSquared));

    return Math.hypot(px - (ax + t * dx), py - (ay + t * dy));
}

/**
 * The country a city belongs to. Coastal cities can sit just offshore of the
 * simplified coastline, so the nearest country within a short distance counts.
 */
function countryAt(point, countries, tolerance = 2) {
    const containing = countries.find((country) => pointInCountry(point, country));

    if (containing) {
        return containing;
    }

    let nearest = null;
    let nearestDistance = tolerance;

    for (const country of countries) {
        for (const rings of country.polygons) {
            const ring = rings[0];

            for (let i = 1; i < ring.length; i++) {
                const distance = distanceToSegment(point, ring[i - 1], ring[i]);

                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearest = country;
                }
            }
        }
    }

    return nearest;
}

function svg(tag, attributes = {}) {
    const element = document.createElementNS(SVG_NS, tag);
    setAttributes(element, attributes);

    return element;
}

function setAttributes(element, attributes) {
    for (const [key, value] of Object.entries(attributes)) {
        if (value === null || value === undefined || value === false) {
            element.removeAttribute(key);
        } else {
            element.setAttribute(key, String(value));
        }
    }
}

function rectsOverlap(a, b) {
    return a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
}

function formatDuration(seconds) {
    if (!seconds) {
        return null;
    }

    if (seconds < 90) {
        return `${seconds}s`;
    }

    const minutes = Math.round(seconds / 60);

    return minutes < 90 ? `${minutes} min` : `${Math.round(minutes / 6) / 10} h`;
}

const prefersReducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

/**
 * @param {{nodes: Array, edges: Array, player: ?Object}} data
 * @param {{compact?: boolean, dark?: boolean}} options
 */
export function geoMap(data, options = {}) {
    /*
     * Everything that changes every frame, or is too large for Alpine to wrap
     * in a reactive proxy, lives in this closure instead of on the component.
     */
    const internal = {
        nodes: [],
        nodeById: new Map(),
        outgoing: new Map(),
        pairs: [],
        geometry: null,
        view: { k: 1, tx: 0, ty: 0 },
        size: { w: 0, h: 0 },
        elements: {},
        nodeElements: new Map(),
        labelElements: new Map(),
        pairElements: [],
        hintElements: new Map(),
        countryLabelElements: [],
        zoneCountries: [],
        pointers: new Map(),
        gesture: null,
        frame: null,
        animation: null,
        pendingZoom: null,
        measureContext: null,
        fontFamily: 'ui-sans-serif, system-ui, sans-serif',
        observers: [],
        cleanups: [],
    };

    const player = data.player ?? null;

    return {
        compact: !!options.compact,
        forceDark: !!options.dark,
        player,
        currentCityId: player?.current_city_id ?? null,
        selectedId: null,
        hoveredId: null,
        activePreset: null,
        presets: [],
        nextStops: [],
        query: '',
        searchOpen: false,
        searchIndex: 0,
        geometryReady: false,
        geometryFailed: false,
        isDark: false,
        showHint: true,

        init() {
            this.prepareData();
            this.isDark = this.computeIsDark();
            this.buildPresets();
            this.buildScene();
            this.bindInteractions();
            this.watchTheme();

            const resize = new ResizeObserver(() => this.handleResize());
            resize.observe(this.$refs.viewport);
            internal.observers.push(resize);
            this.handleResize(true);

            loadGeometry()
                .then((geometry) => {
                    internal.geometry = geometry;
                    this.drawGeography();
                    this.geometryReady = true;
                    this.scheduleRender();
                })
                .catch((error) => {
                    console.error('[geo-map] could not load world geometry', error);
                    this.geometryFailed = true;
                });

            const pendingFocus = window.__geoMapPendingFocus;

            if (pendingFocus) {
                window.__geoMapPendingFocus = null;
                this.$nextTick(() => this.focusCity(pendingFocus));
            }

            const onFocusRequest = (event) => this.focusCity(event.detail?.id);
            window.addEventListener('geo-map:focus', onFocusRequest);
            internal.cleanups.push(() => window.removeEventListener('geo-map:focus', onFocusRequest));

            this.$watch('selectedId', () => {
                this.restyle();
                this.$nextTick(() => this.revealSelected());
            });
            this.$watch('hoveredId', () => this.restyle());
        },

        destroy() {
            internal.observers.forEach((observer) => observer.disconnect());
            internal.cleanups.forEach((cleanup) => cleanup());

            if (internal.frame) {
                cancelAnimationFrame(internal.frame);
            }

            if (internal.animation) {
                cancelAnimationFrame(internal.animation);
            }
        },

        /* ------------------------------------------------------------------ */
        /* Data                                                                */
        /* ------------------------------------------------------------------ */

        prepareData() {
            internal.nodes = data.nodes
                .filter((node) => node.lat !== null && node.lng !== null)
                .map((node) => ({ ...node, ...project(node.lat, node.lng) }));
            internal.nodes.forEach((node) => internal.nodeById.set(node.id, node));

            const pairs = new Map();

            for (const edge of data.edges) {
                if (!internal.nodeById.has(edge.from) || !internal.nodeById.has(edge.to)) {
                    continue;
                }

                if (!internal.outgoing.has(edge.from)) {
                    internal.outgoing.set(edge.from, new Map());
                }

                internal.outgoing.get(edge.from).set(edge.to, edge);

                const key = edge.from < edge.to ? `${edge.from}-${edge.to}` : `${edge.to}-${edge.from}`;

                if (!pairs.has(key)) {
                    pairs.set(key, { a: Math.min(edge.from, edge.to), b: Math.max(edge.from, edge.to), forward: false, backward: false });
                }

                const pair = pairs.get(key);
                pair[edge.from === pair.a ? 'forward' : 'backward'] = true;
            }

            internal.pairs = [...pairs.values()];

            this.nextStops = this.currentCityId
                ? [...(internal.outgoing.get(this.currentCityId)?.keys() ?? [])]
                    .map((id) => internal.nodeById.get(id))
                    .sort((a, b) => a.label.localeCompare(b.label))
                    .map((node) => ({ id: node.id, label: node.label, country: node.country, objective: this.objectivesFor(node.id).length > 0 }))
                : [];
        },

        node(id) {
            return internal.nodeById.get(id) ?? null;
        },

        objectivesFor(id) {
            return (this.player?.objectives ?? []).filter((objective) => objective.city_id === id);
        },

        isReachable(id) {
            return !!this.currentCityId && !!internal.outgoing.get(this.currentCityId)?.has(id);
        },

        shortestPath(fromId, toId) {
            if (!fromId || !toId || fromId === toId) {
                return fromId === toId ? [fromId] : [];
            }

            const previous = new Map([[fromId, null]]);
            const queue = [fromId];

            while (queue.length) {
                const id = queue.shift();

                for (const next of internal.outgoing.get(id)?.keys() ?? []) {
                    if (previous.has(next)) {
                        continue;
                    }

                    previous.set(next, id);

                    if (next === toId) {
                        const path = [toId];
                        let step = id;

                        while (step !== null) {
                            path.unshift(step);
                            step = previous.get(step);
                        }

                        return path;
                    }

                    queue.push(next);
                }
            }

            return [];
        },

        get transit() {
            const transit = this.player?.transit;

            if (!transit) {
                return null;
            }

            const from = this.node(transit.from);
            const to = this.node(transit.to);
            const queued = (transit.queued ?? []).map((id) => this.node(id)).filter(Boolean);

            return from && to ? { from, to, queued } : null;
        },

        get currentCity() {
            return this.node(this.currentCityId);
        },

        get selectedInfo() {
            const node = this.node(this.selectedId);

            if (!node) {
                return null;
            }

            const info = {
                node,
                objectives: this.objectivesFor(node.id),
                mode: 'guest',
                path: [],
                stops: 0,
                via: [],
                duration: null,
                travelTarget: null,
                canTravel: !!this.player?.can_travel,
                restriction: this.player?.travel_restriction ?? null,
            };

            if (!this.player) {
                return info;
            }

            if (!this.currentCityId) {
                info.mode = 'unplaced';

                return info;
            }

            if (this.transit?.to.id === node.id || this.transit?.queued.some((stop) => stop.id === node.id)) {
                info.mode = 'arriving';

                return info;
            }

            if (node.id === this.currentCityId) {
                info.mode = 'here';

                return info;
            }

            const path = this.shortestPath(this.currentCityId, node.id);

            if (!path.length) {
                info.mode = 'unreachable';

                return info;
            }

            info.path = path;
            info.stops = path.length - 1;
            info.via = path.slice(1, -1).map((id) => this.node(id)?.label);
            info.travelTarget = this.node(path[1]);
            info.duration = formatDuration(internal.outgoing.get(this.currentCityId)?.get(path[1])?.duration_seconds);
            info.mode = info.stops === 1 ? 'direct' : 'route';

            return info;
        },

        /* ------------------------------------------------------------------ */
        /* Theme                                                               */
        /* ------------------------------------------------------------------ */

        computeIsDark() {
            return this.forceDark || document.documentElement.classList.contains('dark');
        },

        get theme() {
            return this.isDark ? THEMES.dark : THEMES.light;
        },

        watchTheme() {
            if (this.forceDark) {
                return;
            }

            const observer = new MutationObserver(() => {
                const isDark = this.computeIsDark();

                if (isDark !== this.isDark) {
                    this.isDark = isDark;
                    this.applyTheme();
                }
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            internal.observers.push(observer);
        },

        applyTheme() {
            const theme = this.theme;
            const { elements } = internal;
            this.$refs.viewport.style.background = theme.ocean;
            setAttributes(elements.graticule, { stroke: theme.graticule });
            setAttributes(elements.borders, { stroke: theme.border });
            setAttributes(elements.coast, { stroke: theme.coast });
            internal.countryPaths?.forEach(({ element, isZone }) => setAttributes(element, { fill: isZone ? theme.zoneLand : theme.land }));
            internal.countryLabelElements.forEach(({ element }) => setAttributes(element, { fill: theme.countryLabel, stroke: theme.halo }));
            this.restyle();
        },

        /* ------------------------------------------------------------------ */
        /* Scene                                                               */
        /* ------------------------------------------------------------------ */

        buildScene() {
            const root = this.$refs.svg;
            const { elements } = internal;
            internal.fontFamily = getComputedStyle(this.$root).fontFamily || internal.fontFamily;

            const clipId = `geo-map-clip-${Math.random().toString(36).slice(2, 9)}`;
            const clip = svg('clipPath', { id: clipId });
            clip.append(svg('rect', { x: 0, y: 0, width: MAP_WIDTH, height: MAP_HEIGHT }));
            const defs = svg('defs');
            defs.append(clip);
            root.prepend(defs);

            elements.geo = svg('g', { 'data-layer': 'geography', 'clip-path': `url(#${clipId})` });
            elements.countries = svg('g');
            elements.graticule = svg('path', { d: this.graticulePath(), fill: 'none', 'stroke-width': 1, 'vector-effect': 'non-scaling-stroke' });
            elements.borders = svg('path', { fill: 'none', 'stroke-width': 0.75, 'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke' });
            elements.coast = svg('path', { fill: 'none', 'stroke-width': 1, 'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke' });
            elements.geo.append(elements.graticule, elements.countries, elements.borders, elements.coast);

            elements.countryLabels = svg('g', { 'pointer-events': 'none', 'aria-hidden': 'true' });
            elements.routes = svg('g', { 'pointer-events': 'none', 'aria-hidden': 'true' });
            elements.highlight = svg('g', { 'pointer-events': 'none', 'aria-hidden': 'true' });
            elements.cities = svg('g', { role: 'list', 'aria-label': 'Cities' });
            elements.labels = svg('g', { 'pointer-events': 'none', 'aria-hidden': 'true' });

            root.querySelector('[data-layer="land"]').append(elements.geo);
            root.querySelector('[data-layer="routes"]').append(elements.routes, elements.highlight);
            root.querySelector('[data-layer="cities"]').append(elements.countryLabels, elements.cities, elements.labels);

            for (const pair of internal.pairs) {
                const path = svg('path', { fill: 'none', 'stroke-linecap': 'round' });
                const arrow = pair.forward !== pair.backward ? svg('path') : null;
                elements.routes.append(path);

                if (arrow) {
                    elements.routes.append(arrow);
                }

                internal.pairElements.push({ pair, path, arrow });
            }

            elements.pathLine = svg('path', { fill: 'none', 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-dasharray': '2 7' });
            elements.transitLine = svg('path', { fill: 'none', 'stroke-width': 2.5, 'stroke-linecap': 'round', 'stroke-dasharray': '6 6' });

            if (!prefersReducedMotion()) {
                elements.pathLine.append(svg('animate', { attributeName: 'stroke-dashoffset', from: 18, to: 0, dur: '0.9s', repeatCount: 'indefinite' }));
                elements.transitLine.append(svg('animate', { attributeName: 'stroke-dashoffset', from: 24, to: 0, dur: '1.2s', repeatCount: 'indefinite' }));
            }

            elements.highlight.append(elements.transitLine, elements.pathLine);

            for (const node of internal.nodes) {
                const group = svg('g', {
                    'data-node-id': node.id,
                    role: 'button',
                    tabindex: 0,
                    'aria-label': `${node.label}, ${node.country}`,
                    style: 'cursor:pointer;outline:none',
                });
                const hit = svg('circle', { r: this.compact ? 16 : 14, fill: 'transparent' });
                const pulse = svg('circle', { fill: 'none', 'stroke-width': 2 });
                const ring = svg('circle', { fill: 'none', 'stroke-width': 2 });
                const dot = svg('circle', { 'stroke-width': 2 });
                const badge = svg('path', { 'stroke-width': 1.5, 'stroke-linejoin': 'round' });
                group.append(hit, pulse, ring, dot, badge);
                elements.cities.append(group);

                const label = svg('text', { 'paint-order': 'stroke', 'stroke-linejoin': 'round', style: `font-family:${internal.fontFamily};user-select:none` });
                elements.labels.append(label);

                internal.nodeElements.set(node.id, { group, pulse, ring, dot, badge });
                internal.labelElements.set(node.id, { element: label, width: 0, height: 0, key: null });
            }

            this.applyTheme();
        },

        graticulePath() {
            let d = '';

            for (let lng = -150; lng <= 150; lng += 30) {
                const x = ((lng + 180) / 360) * MAP_WIDTH;
                d += `M${x},0V${MAP_HEIGHT}`;
            }

            for (const lat of [-30, 0, 30, 60]) {
                d += `M0,${project(lat, 0).y}H${MAP_WIDTH}`;
            }

            return d;
        },

        drawGeography() {
            const { geometry, elements } = internal;
            const theme = this.theme;
            const zoneCountries = new Set(internal.nodes.map((node) => countryAt([node.x, node.y], geometry.countries)).filter(Boolean));

            internal.countryPaths = geometry.countries.map((country) => {
                const isZone = zoneCountries.has(country);
                const element = svg('path', { d: country.d, fill: isZone ? theme.zoneLand : theme.land });
                elements.countries.append(element);

                return { element, isZone, country };
            });

            setAttributes(elements.borders, { d: geometry.bordersD });
            setAttributes(elements.coast, { d: geometry.coastD });

            internal.countryLabelElements = internal.countryPaths
                .filter(({ isZone, country }) => isZone && pointInCountry(country.labelPoint, country))
                .map(({ country }) => {
                    const element = svg('text', {
                        'text-anchor': 'middle',
                        'font-size': 10,
                        'font-weight': 600,
                        'letter-spacing': '0.14em',
                        fill: theme.countryLabel,
                        stroke: theme.halo,
                        'stroke-width': 3,
                        'paint-order': 'stroke',
                        style: `font-family:${internal.fontFamily};text-transform:uppercase`,
                    });
                    element.textContent = country.name;
                    elements.countryLabels.append(element);

                    return { element, country, width: this.measure(country.name.toUpperCase(), 10, 600) * 1.14 };
                });
        },

        /* ------------------------------------------------------------------ */
        /* View transform                                                       */
        /* ------------------------------------------------------------------ */

        handleResize(initial = false) {
            const rect = this.$refs.viewport.getBoundingClientRect();

            if (rect.width === 0 || rect.height === 0) {
                return;
            }

            const previous = { ...internal.size };
            internal.size = { w: rect.width, h: rect.height };

            if (initial || previous.w === 0) {
                this.applyPreset(this.presets.find((preset) => preset.key === 'around') ? 'around' : 'world', false);

                return;
            }

            const { view } = internal;
            const centerX = (previous.w / 2 - view.tx) / view.k;
            const centerY = (previous.h / 2 - view.ty) / view.k;
            this.setView(view.k, centerX, centerY);
        },

        minZoom() {
            const { w, h } = internal.size;

            return Math.min(w / MAP_WIDTH, h / MAP_HEIGHT);
        },

        maxZoom() {
            return Math.max(this.minZoom() * 4, internal.size.w / 22);
        },

        /** Centre the view on a world point at zoom k, clamped so the map never drifts away. */
        setView(k, centerX, centerY, render = true) {
            const { w, h } = internal.size;
            const zoom = Math.max(this.minZoom(), Math.min(this.maxZoom(), k));
            let tx = w / 2 - centerX * zoom;
            let ty = h / 2 - centerY * zoom;
            const mapW = MAP_WIDTH * zoom;
            const mapH = MAP_HEIGHT * zoom;

            tx = mapW <= w ? (w - mapW) / 2 : Math.min(0, Math.max(w - mapW, tx));
            ty = mapH <= h ? (h - mapH) / 2 : Math.min(0, Math.max(h - mapH, ty));
            internal.view = { k: zoom, tx, ty };

            if (render) {
                this.scheduleRender();
            }
        },

        viewCenter() {
            const { view, size } = internal;

            return { x: (size.w / 2 - view.tx) / view.k, y: (size.h / 2 - view.ty) / view.k };
        },

        zoomAt(screenX, screenY, factor) {
            const { view } = internal;
            const worldX = (screenX - view.tx) / view.k;
            const worldY = (screenY - view.ty) / view.k;
            const k = Math.max(this.minZoom(), Math.min(this.maxZoom(), view.k * factor));
            const { w, h } = internal.size;
            this.setView(k, worldX + (w / 2 - screenX) / k, worldY + (h / 2 - screenY) / k);
            this.activePreset = null;
        },

        zoomBy(factor) {
            const target = this.viewCenter();
            this.flyTo(internal.view.k * factor, target.x, target.y, 260);
            this.activePreset = null;
        },

        flyTo(k, centerX, centerY, duration = 520) {
            if (internal.animation) {
                cancelAnimationFrame(internal.animation);
            }

            const start = { k: internal.view.k, ...this.viewCenter() };
            const endK = Math.max(this.minZoom(), Math.min(this.maxZoom(), k));

            if (prefersReducedMotion() || duration === 0) {
                this.setView(endK, centerX, centerY);

                return;
            }

            const began = performance.now();
            const step = (now) => {
                const t = Math.min(1, (now - began) / duration);
                const eased = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
                const zoom = start.k * Math.pow(endK / start.k, eased);
                this.setView(zoom, start.x + (centerX - start.x) * eased, start.y + (centerY - start.y) * eased, false);
                this.render();
                internal.animation = t < 1 ? requestAnimationFrame(step) : null;
            };
            internal.animation = requestAnimationFrame(step);
        },

        fitBounds(bounds, animate = true, padding = null) {
            const { w, h } = internal.size;
            const pad = padding ?? (this.compact ? 36 : 64);
            const minSpanX = 60;
            const minSpanY = 40;
            const spanX = Math.max(minSpanX, bounds.x1 - bounds.x0);
            const spanY = Math.max(minSpanY, bounds.y1 - bounds.y0);
            const k = Math.min((w - pad * 2) / spanX, (h - pad * 2) / spanY);
            const centerX = (bounds.x0 + bounds.x1) / 2;
            const centerY = (bounds.y0 + bounds.y1) / 2;

            if (animate) {
                this.flyTo(k, centerX, centerY);
            } else {
                this.setView(k, centerX, centerY);
            }
        },

        boundsOf(ids) {
            const nodes = ids.map((id) => this.node(id)).filter(Boolean);

            if (!nodes.length) {
                return { x0: 0, y0: 0, x1: MAP_WIDTH, y1: MAP_HEIGHT };
            }

            return {
                x0: Math.min(...nodes.map((node) => node.x)),
                y0: Math.min(...nodes.map((node) => node.y)),
                x1: Math.max(...nodes.map((node) => node.x)),
                y1: Math.max(...nodes.map((node) => node.y)),
            };
        },

        /* ------------------------------------------------------------------ */
        /* Presets                                                             */
        /* ------------------------------------------------------------------ */

        buildPresets() {
            const presets = [];

            if (this.currentCityId && this.node(this.currentCityId)) {
                presets.push({ key: 'around', label: 'Around me', count: null });
            }

            presets.push({ key: 'world', label: 'World', count: internal.nodes.length });

            for (const continent of CONTINENT_ORDER) {
                const count = internal.nodes.filter((node) => node.continent === continent).length;

                if (count) {
                    presets.push({ key: continent, label: continent, count });
                }
            }

            this.presets = presets;
        },

        applyPreset(key, animate = true) {
            this.activePreset = key;

            if (key === 'world') {
                if (animate) {
                    this.flyTo(this.minZoom(), MAP_WIDTH / 2, MAP_HEIGHT / 2);
                } else {
                    this.setView(this.minZoom(), MAP_WIDTH / 2, MAP_HEIGHT / 2);
                }

                return;
            }

            if (key === 'around') {
                const ids = [this.currentCityId, ...(internal.outgoing.get(this.currentCityId)?.keys() ?? [])];

                if (this.transit) {
                    ids.push(this.transit.to.id, this.transit.from.id);
                }

                this.fitBounds(this.boundsOf(ids), animate);

                return;
            }

            this.fitBounds(this.boundsOf(internal.nodes.filter((node) => node.continent === key).map((node) => node.id)), animate);
        },

        findMe() {
            if (this.currentCity) {
                this.focusCity(this.currentCity.id);
            }
        },

        /* ------------------------------------------------------------------ */
        /* Selection & search                                                  */
        /* ------------------------------------------------------------------ */

        select(id) {
            this.selectedId = id;
            this.showHint = false;
        },

        clearSelection() {
            this.selectedId = null;
        },

        /** Select a city and bring it into a comfortable view. */
        focusCity(id) {
            const node = this.node(Number(id));

            if (!node) {
                return;
            }

            const { view, size } = internal;
            internal.pendingZoom = view.k >= size.w / 400 ? null : size.w / 300;

            if (this.selectedId === node.id) {
                this.revealSelected();
            } else {
                this.select(node.id);
            }
        },

        /**
         * Keep the selected city in sight: if it sits under the details card or
         * off the edge, pan it into the open part of the map.
         */
        revealSelected() {
            const node = this.node(this.selectedId);
            const zoom = internal.pendingZoom;
            internal.pendingZoom = null;

            if (!node) {
                return;
            }

            const { view, size } = internal;
            const area = { x0: 0, y0: 0, x1: size.w, y1: size.h };
            const card = this.$refs.viewport.querySelector('[data-test="map-city-card"]');

            if (card) {
                if (card.offsetWidth > size.w * 0.6) {
                    area.y1 = card.offsetTop;
                } else {
                    area.x0 = card.offsetLeft + card.offsetWidth;
                }
            }

            const margin = Math.min(48, (area.y1 - area.y0) / 4);
            const sx = node.x * view.k + view.tx;
            const sy = node.y * view.k + view.ty;
            const visible = sx > area.x0 + margin && sx < area.x1 - margin && sy > area.y0 + margin && sy < area.y1 - margin;

            if (visible && zoom === null) {
                return;
            }

            const k = zoom ?? view.k;
            const targetX = (area.x0 + area.x1) / 2;
            const targetY = (area.y0 + area.y1) / 2;
            this.flyTo(k, node.x + (size.w / 2 - targetX) / k, node.y + (size.h / 2 - targetY) / k);
            this.activePreset = null;
        },

        get searchResults() {
            const query = this.query.trim().toLowerCase();

            if (!query) {
                return [];
            }

            return internal.nodes
                .filter((node) => node.label.toLowerCase().includes(query) || node.country.toLowerCase().includes(query))
                .sort((a, b) => Number(!a.label.toLowerCase().startsWith(query)) - Number(!b.label.toLowerCase().startsWith(query)) || a.label.localeCompare(b.label))
                .slice(0, 8)
                .map((node) => ({ id: node.id, label: node.label, country: node.country, here: node.id === this.currentCityId, reachable: this.isReachable(node.id) }));
        },

        chooseSearchResult(id) {
            this.focusCity(id);
            this.query = '';
            this.searchOpen = false;
            this.searchIndex = 0;
        },

        onSearchKey(event) {
            const results = this.searchResults;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.searchIndex = Math.min(results.length - 1, this.searchIndex + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.searchIndex = Math.max(0, this.searchIndex - 1);
            } else if (event.key === 'Enter' && results[this.searchIndex]) {
                event.preventDefault();
                this.chooseSearchResult(results[this.searchIndex].id);
            } else if (event.key === 'Escape') {
                this.query = '';
                this.searchOpen = false;
            }
        },

        travel(id) {
            const path = this.shortestPath(this.currentCityId, id);

            if (!this.player?.can_travel || path.length < 2) {
                return;
            }

            const form = this.$refs.travelForm;

            if (!form) {
                return;
            }

            form.querySelector('[name="city_id"]').value = id;
            form.querySelectorAll('[name="via[]"]').forEach((input) => input.remove());

            for (const stopId of path.slice(1, -1)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'via[]';
                input.value = stopId;
                form.append(input);
            }

            form.submit();
        },

        /* ------------------------------------------------------------------ */
        /* Interaction                                                         */
        /* ------------------------------------------------------------------ */

        bindInteractions() {
            const viewport = this.$refs.viewport;
            const on = (target, type, handler, opts) => {
                target.addEventListener(type, handler, opts);
                internal.cleanups.push(() => target.removeEventListener(type, handler, opts));
            };
            const local = (event) => {
                const rect = viewport.getBoundingClientRect();

                return { x: event.clientX - rect.left, y: event.clientY - rect.top };
            };
            const nodeIdFrom = (target) => {
                const id = target?.closest?.('[data-node-id]')?.getAttribute('data-node-id');

                return id ? Number(id) : null;
            };

            on(viewport, 'pointerdown', (event) => {
                if (event.button !== 0 && event.pointerType === 'mouse') {
                    return;
                }

                if (event.target.closest('[data-map-ui]')) {
                    return;
                }

                internal.pointers.set(event.pointerId, local(event));
                viewport.setPointerCapture?.(event.pointerId);

                if (internal.animation) {
                    cancelAnimationFrame(internal.animation);
                    internal.animation = null;
                }

                if (internal.pointers.size === 1) {
                    const point = local(event);
                    internal.gesture = { type: 'pan', start: point, view: { ...internal.view }, moved: false, nodeId: nodeIdFrom(event.target) };
                } else if (internal.pointers.size === 2) {
                    const [a, b] = [...internal.pointers.values()];
                    internal.gesture = {
                        type: 'pinch',
                        distance: Math.hypot(a.x - b.x, a.y - b.y),
                        mid: { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 },
                        view: { ...internal.view },
                        moved: true,
                    };
                }
            });

            on(viewport, 'pointermove', (event) => {
                if (!internal.pointers.has(event.pointerId)) {
                    if (event.pointerType === 'mouse') {
                        const id = nodeIdFrom(event.target);

                        if (id !== this.hoveredId) {
                            this.hoveredId = id;
                        }
                    }

                    return;
                }

                internal.pointers.set(event.pointerId, local(event));
                const gesture = internal.gesture;

                if (!gesture) {
                    return;
                }

                if (gesture.type === 'pan') {
                    const point = local(event);
                    const dx = point.x - gesture.start.x;
                    const dy = point.y - gesture.start.y;

                    if (!gesture.moved && Math.hypot(dx, dy) < 5) {
                        return;
                    }

                    gesture.moved = true;
                    viewport.style.cursor = 'grabbing';
                    const { k, tx, ty } = gesture.view;
                    const { w, h } = internal.size;
                    this.setView(k, (w / 2 - tx - dx) / k, (h / 2 - ty - dy) / k);
                    this.activePreset = null;
                    this.showHint = false;
                } else if (gesture.type === 'pinch' && internal.pointers.size >= 2) {
                    const [a, b] = [...internal.pointers.values()];
                    const distance = Math.hypot(a.x - b.x, a.y - b.y);
                    const mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
                    const { view } = gesture;
                    const k = Math.max(this.minZoom(), Math.min(this.maxZoom(), view.k * (distance / gesture.distance)));
                    const worldX = (gesture.mid.x - view.tx) / view.k;
                    const worldY = (gesture.mid.y - view.ty) / view.k;
                    const { w, h } = internal.size;
                    this.setView(k, worldX + (w / 2 - mid.x) / k, worldY + (h / 2 - mid.y) / k);
                    this.activePreset = null;
                    this.showHint = false;
                }
            });

            const end = (event) => {
                if (!internal.pointers.has(event.pointerId)) {
                    return;
                }

                internal.pointers.delete(event.pointerId);
                const gesture = internal.gesture;
                viewport.style.cursor = '';

                if (gesture?.type === 'pan' && !gesture.moved && event.type === 'pointerup') {
                    if (gesture.nodeId) {
                        this.select(gesture.nodeId);
                    } else {
                        this.clearSelection();
                    }
                }

                if (internal.pointers.size === 1 && gesture?.type === 'pinch') {
                    const [point] = internal.pointers.values();
                    internal.gesture = { type: 'pan', start: point, view: { ...internal.view }, moved: true, nodeId: null };
                } else if (internal.pointers.size === 0) {
                    internal.gesture = null;
                }
            };
            on(viewport, 'pointerup', end);
            on(viewport, 'pointercancel', end);

            on(viewport, 'pointerleave', () => {
                if (!internal.pointers.size) {
                    this.hoveredId = null;
                }
            });

            on(viewport, 'wheel', (event) => {
                if (event.target.closest('[data-map-ui]')) {
                    return;
                }

                event.preventDefault();
                const point = local(event);
                const unit = event.deltaMode === 1 ? 16 : 1;
                const factor = Math.exp(-event.deltaY * unit * (event.ctrlKey ? 0.012 : 0.0022));
                this.zoomAt(point.x, point.y, factor);
                this.showHint = false;
            }, { passive: false });

            on(viewport, 'dblclick', (event) => {
                if (event.target.closest('[data-map-ui]') || event.target.closest('[data-node-id]')) {
                    return;
                }

                const point = local(event);
                const { view, size } = internal;
                const k = view.k * 2;
                const worldX = (point.x - view.tx) / view.k;
                const worldY = (point.y - view.ty) / view.k;
                this.flyTo(k, worldX + (size.w / 2 - point.x) / Math.min(k, this.maxZoom()), worldY + (size.h / 2 - point.y) / Math.min(k, this.maxZoom()), 300);
                this.activePreset = null;
            });

            on(viewport, 'keydown', (event) => {
                const nodeId = nodeIdFrom(event.target);

                if (nodeId && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    this.select(nodeId);

                    return;
                }

                if (event.target.closest('[data-map-ui]')) {
                    return;
                }

                const pan = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[event.key];

                if (pan) {
                    event.preventDefault();
                    const center = this.viewCenter();
                    const step = 90 / internal.view.k;
                    this.flyTo(internal.view.k, center.x + pan[0] * step, center.y + pan[1] * step, 160);
                    this.activePreset = null;
                } else if (event.key === '+' || event.key === '=') {
                    this.zoomBy(1.6);
                } else if (event.key === '-' || event.key === '_') {
                    this.zoomBy(1 / 1.6);
                } else if (event.key === 'Escape') {
                    this.clearSelection();
                }
            });

            on(viewport, 'focusin', (event) => {
                const nodeId = nodeIdFrom(event.target);

                if (nodeId) {
                    this.hoveredId = nodeId;
                }
            });

            on(viewport, 'focusout', (event) => {
                if (nodeIdFrom(event.target) === this.hoveredId) {
                    this.hoveredId = null;
                }
            });
        },

        /* ------------------------------------------------------------------ */
        /* Rendering                                                           */
        /* ------------------------------------------------------------------ */

        scheduleRender() {
            if (internal.frame) {
                return;
            }

            internal.frame = requestAnimationFrame(() => {
                internal.frame = null;
                this.render();
            });
        },

        toScreen(point) {
            const { k, tx, ty } = internal.view;

            return { x: point.x * k + tx, y: point.y * k + ty };
        },

        /** Gently curved route in screen space, split across the date line when that is the short way round. */
        routeD(from, to) {
            const segments = [];

            if (Math.abs(to.x - from.x) > MAP_WIDTH / 2) {
                const shift = to.x > from.x ? -MAP_WIDTH : MAP_WIDTH;
                segments.push([from, { x: to.x + shift, y: to.y }], [{ x: from.x - shift, y: from.y }, to]);
            } else {
                segments.push([from, to]);
            }

            return segments.map(([a, b]) => this.curve(this.toScreen(a), this.toScreen(b))).join('');
        },

        curve(a, b) {
            const dx = b.x - a.x;
            const dy = b.y - a.y;
            const length = Math.hypot(dx, dy) || 1;
            let nx = -dy / length;
            let ny = dx / length;

            if (ny > 0) {
                nx = -nx;
                ny = -ny;
            }

            const bend = Math.min(length * 0.16, 160);
            const cx = (a.x + b.x) / 2 + nx * bend;
            const cy = (a.y + b.y) / 2 + ny * bend;

            return `M${a.x.toFixed(1)},${a.y.toFixed(1)}Q${cx.toFixed(1)},${cy.toFixed(1)} ${b.x.toFixed(1)},${b.y.toFixed(1)}`;
        },

        render() {
            if (!internal.size.w) {
                return;
            }

            const { k, tx, ty } = internal.view;
            internal.elements.geo.setAttribute('transform', `matrix(${k},0,0,${k},${tx},${ty})`);

            for (const { pair, path, arrow } of internal.pairElements) {
                const a = this.node(pair.a);
                const b = this.node(pair.b);
                path.setAttribute('d', this.routeD(a, b));

                if (arrow) {
                    const [from, to] = pair.forward ? [a, b] : [b, a];
                    const sa = this.toScreen(from);
                    const sb = this.toScreen(to);
                    const angle = Math.atan2(sb.y - sa.y, sb.x - sa.x);
                    const mx = (sa.x + sb.x) / 2;
                    const my = (sa.y + sb.y) / 2;
                    const size = 6;
                    arrow.setAttribute('d', `M${mx + Math.cos(angle) * size},${my + Math.sin(angle) * size}L${mx + Math.cos(angle + 2.5) * size},${my + Math.sin(angle + 2.5) * size}L${mx + Math.cos(angle - 2.5) * size},${my + Math.sin(angle - 2.5) * size}Z`);
                }
            }

            const info = this.selectedInfo;
            const pathIds = info?.path ?? [];
            internal.elements.pathLine.setAttribute('d', pathIds.length > 1
                ? pathIds.slice(1).map((id, i) => this.routeD(this.node(pathIds[i]), this.node(id))).join('')
                : '');

            const transit = this.transit;
            internal.elements.transitLine.setAttribute('d', transit ? this.routeD(transit.from, transit.to) : '');

            for (const node of internal.nodes) {
                const point = this.toScreen(node);
                internal.nodeElements.get(node.id).group.setAttribute('transform', `translate(${point.x.toFixed(1)},${point.y.toFixed(1)})`);
            }

            this.placeLabels();
            this.renderHints();
        },

        /** Visual state of a city, most important first. */
        roleOf(id) {
            if (id === this.currentCityId) {
                return 'current';
            }

            if (this.transit?.to.id === id) {
                return 'arriving';
            }

            if (this.objectivesFor(id).length) {
                return 'objective';
            }

            if (this.isReachable(id)) {
                return 'reachable';
            }

            return 'city';
        },

        restyle() {
            const theme = this.theme;
            const info = this.selectedInfo;
            const onPath = new Set(info?.path ?? []);
            const reducedMotion = prefersReducedMotion();

            for (const { pair, path, arrow } of internal.pairElements) {
                const fromYou = this.currentCityId && (pair.a === this.currentCityId || pair.b === this.currentCityId);
                const dimmed = info && info.path.length > 1 && !fromYou;
                setAttributes(path, {
                    stroke: fromYou ? theme.routeFromYou : theme.route,
                    'stroke-width': fromYou ? 2 : 1.25,
                    'stroke-opacity': dimmed ? 0.45 : fromYou ? 0.9 : 1,
                });

                if (arrow) {
                    setAttributes(arrow, { fill: fromYou ? theme.routeFromYou : theme.route });
                }
            }

            setAttributes(internal.elements.pathLine, { stroke: theme.path });
            setAttributes(internal.elements.transitLine, { stroke: theme.transit });

            for (const node of internal.nodes) {
                const { pulse, ring, dot, badge, group } = internal.nodeElements.get(node.id);
                const role = this.roleOf(node.id);
                const selected = node.id === this.selectedId;
                const hovered = node.id === this.hoveredId;
                const compact = this.compact;
                const baseRadius = { current: 7, arriving: 6, objective: 6, reachable: 6, city: 4.5 }[role] + (compact ? 0.5 : 0);
                const radius = baseRadius + (hovered || selected ? 1.5 : 0) + (onPath.has(node.id) && role === 'city' ? 1 : 0);
                const fill = {
                    current: theme.current,
                    arriving: theme.transit,
                    objective: theme.objective,
                    reachable: theme.reachable,
                    city: onPath.has(node.id) ? theme.path : theme.city,
                }[role];

                setAttributes(dot, { r: radius, fill, stroke: theme.cityStroke });
                setAttributes(ring, {
                    r: radius + 4.5,
                    stroke: selected ? theme.selected : hovered ? fill : 'none',
                    'stroke-opacity': selected ? 0.95 : 0.5,
                });

                const pulseState = role === 'current' || role === 'arriving' ? `${baseRadius}|${fill}` : 'off';

                if (pulse.dataset.state !== pulseState) {
                    pulse.dataset.state = pulseState;
                    pulse.replaceChildren();

                    if (pulseState === 'off') {
                        setAttributes(pulse, { r: 0, stroke: 'none' });
                    } else {
                        setAttributes(pulse, { r: baseRadius + 3, stroke: fill, 'stroke-opacity': 0.5 });

                        if (!reducedMotion) {
                            pulse.append(
                                svg('animate', { attributeName: 'r', values: `${baseRadius + 2};${baseRadius + 13}`, dur: '1.8s', repeatCount: 'indefinite' }),
                                svg('animate', { attributeName: 'stroke-opacity', values: '0.6;0', dur: '1.8s', repeatCount: 'indefinite' }),
                            );
                        }
                    }
                }

                const hasObjective = this.objectivesFor(node.id).length > 0;

                if (hasObjective) {
                    const bx = radius * 0.75 + 2;
                    const by = -radius * 0.75 - 2;
                    setAttributes(badge, {
                        d: `M${bx},${by - 4}L${bx + 4},${by}L${bx},${by + 4}L${bx - 4},${by}Z`,
                        fill: theme.objective,
                        stroke: theme.cityStroke,
                    });
                } else {
                    setAttributes(badge, { d: null });
                }

                group.setAttribute('aria-label', [
                    `${node.label}, ${node.country}`,
                    role === 'current' ? 'you are here' : null,
                    role === 'reachable' ? 'you can travel here' : null,
                    hasObjective ? 'job objective' : null,
                ].filter(Boolean).join(', '));
                group.setAttribute('aria-pressed', String(selected));
            }

            this.scheduleRender();
        },

        measure(text, size, weight) {
            internal.measureContext ??= document.createElement('canvas').getContext('2d');
            internal.measureContext.font = `${weight} ${size}px ${internal.fontFamily}`;

            return internal.measureContext.measureText(text).width;
        },

        /**
         * Labels are placed greedily, most important city first, trying four spots
         * around each dot and dropping any label that would collide.
         */
        placeLabels() {
            const theme = this.theme;
            const { w, h } = internal.size;
            const info = this.selectedInfo;
            const onPath = new Set(info?.path ?? []);
            const placed = [];
            const visible = [];

            for (const node of internal.nodes) {
                const point = this.toScreen(node);

                if (point.x < -40 || point.x > w + 40 || point.y < -40 || point.y > h + 40) {
                    internal.labelElements.get(node.id).element.setAttribute('visibility', 'hidden');
                    continue;
                }

                const role = this.roleOf(node.id);
                const priority = node.id === this.selectedId ? 0
                    : role === 'current' ? 1
                        : node.id === this.hoveredId ? 2
                            : role === 'arriving' ? 3
                                : role === 'objective' ? 4
                                    : role === 'reachable' ? 5
                                        : onPath.has(node.id) ? 6 : 7;
                visible.push({ node, point, role, priority });
            }

            const dots = visible.map(({ node, point }) => ({ id: node.id, rect: { x: point.x - 6, y: point.y - 6, w: 12, h: 12 } }));

            visible.sort((a, b) => a.priority - b.priority || a.node.label.localeCompare(b.node.label));

            for (const { node, point, role, priority } of visible) {
                const label = internal.labelElements.get(node.id);
                const important = priority <= 1;
                const fontSize = this.compact ? 11 : 12;
                const weight = role === 'city' ? 500 : 650;
                const subtitle = role === 'current' ? 'You are here' : role === 'arriving' ? 'Arriving' : null;
                const key = `${node.label}|${fontSize}|${weight}|${subtitle}`;

                if (label.key !== key) {
                    label.key = key;
                    label.element.replaceChildren();
                    const title = svg('tspan', { x: 0, 'font-size': fontSize, 'font-weight': weight });
                    title.textContent = node.label;
                    label.element.append(title);
                    label.width = this.measure(node.label, fontSize, weight);
                    label.height = fontSize + 2;

                    if (subtitle) {
                        const sub = svg('tspan', { x: 0, dy: fontSize, 'font-size': fontSize - 2.5, 'font-weight': 600, 'letter-spacing': '0.06em' });
                        sub.textContent = subtitle.toUpperCase();
                        label.element.append(sub);
                        label.width = Math.max(label.width, this.measure(subtitle.toUpperCase(), fontSize - 2.5, 600) * 1.08);
                        label.height += fontSize;
                    }

                    label.subtitle = !!subtitle;
                }

                const gap = role === 'city' ? 8 : 11;
                const candidates = [
                    { anchor: 'start', x: point.x + gap, y: point.y - label.height / 2 },
                    { anchor: 'end', x: point.x - gap - label.width, y: point.y - label.height / 2 },
                    { anchor: 'middle', x: point.x - label.width / 2, y: point.y - gap - label.height },
                    { anchor: 'middle', x: point.x - label.width / 2, y: point.y + gap },
                ];
                let chosen = null;

                for (const candidate of candidates) {
                    const rect = { x: candidate.x - 2, y: candidate.y - 1, w: label.width + 4, h: label.height + 2 };
                    const inside = rect.x >= 2 && rect.y >= 2 && rect.x + rect.w <= w - 2 && rect.y + rect.h <= h - 2;
                    const hitsDot = dots.some((dot) => dot.id !== node.id && rectsOverlap(rect, dot.rect));

                    if (inside && !hitsDot && !placed.some((other) => rectsOverlap(rect, other))) {
                        chosen = { ...candidate, rect };
                        break;
                    }
                }

                if (!chosen && important) {
                    chosen = { ...candidates[0], rect: { x: candidates[0].x, y: candidates[0].y, w: label.width, h: label.height } };
                }

                if (!chosen) {
                    label.element.setAttribute('visibility', 'hidden');
                    continue;
                }

                placed.push(chosen.rect);
                const textX = chosen.anchor === 'start' ? chosen.x : chosen.anchor === 'end' ? chosen.x + label.width : chosen.x + label.width / 2;
                const color = { current: theme.labelCurrent, arriving: theme.transit, objective: theme.objective, reachable: theme.labelReachable, city: theme.label }[role];

                setAttributes(label.element, {
                    visibility: 'visible',
                    transform: `translate(${textX.toFixed(1)},${(chosen.y + (this.compact ? 10 : 11)).toFixed(1)})`,
                    'text-anchor': chosen.anchor,
                    fill: role === 'city' && node.id !== this.selectedId && node.id !== this.hoveredId ? theme.labelMuted : color,
                    stroke: theme.halo,
                    'stroke-width': 3.5,
                });
            }

            const cityRects = [...placed, ...dots.map((dot) => dot.rect)];

            for (const { element, country, width } of internal.countryLabelElements) {
                const point = this.toScreen({ x: country.labelPoint[0], y: country.labelPoint[1] });
                const screenArea = country.labelArea * internal.view.k * internal.view.k;
                const rect = { x: point.x - width / 2 - 4, y: point.y - 9, w: width + 8, h: 14 };
                const fits = screenArea > width * 40 && rect.x > 0 && rect.y > 0 && rect.x + rect.w < w && rect.y + rect.h < h;

                if (fits && !cityRects.some((other) => rectsOverlap(rect, other))) {
                    cityRects.push(rect);
                    setAttributes(element, { visibility: 'visible', x: point.x.toFixed(1), y: (point.y + 3).toFixed(1) });
                } else {
                    element.setAttribute('visibility', 'hidden');
                }
            }
        },

        /** Pills on the edge of the map pointing at important cities that are out of view. */
        renderHints() {
            const layer = this.$refs.hints;

            if (!layer) {
                return;
            }

            const { w, h } = internal.size;
            const targets = new Map();

            for (const id of internal.outgoing.get(this.currentCityId)?.keys() ?? []) {
                targets.set(id, 'reachable');
            }

            for (const objective of this.player?.objectives ?? []) {
                targets.set(objective.city_id, 'objective');
            }

            if (this.transit) {
                targets.set(this.transit.to.id, 'arriving');
            }

            if (this.currentCityId) {
                targets.set(this.currentCityId, 'current');
            }

            if (this.selectedId) {
                targets.set(this.selectedId, targets.get(this.selectedId) ?? 'selected');
            }

            const seen = new Set();
            const inset = this.compact ? 16 : 22;
            const obstacles = [];
            for (const overlay of this.$refs.viewport.querySelectorAll('[data-test="map-city-card"], [data-map-controls]')) {
                obstacles.push({ x: overlay.offsetLeft - 4, y: overlay.offsetTop - 4, w: overlay.offsetWidth + 8, h: overlay.offsetHeight + 8 });
            }

            const ordered = [...targets].sort(([, a], [, b]) => ['current', 'selected', 'arriving', 'objective', 'reachable'].indexOf(a) - ['current', 'selected', 'arriving', 'objective', 'reachable'].indexOf(b));

            for (const [id, role] of ordered) {
                const node = this.node(id);

                if (!node) {
                    continue;
                }

                const point = this.toScreen(node);

                if (point.x >= 0 && point.x <= w && point.y >= 0 && point.y <= h) {
                    continue;
                }

                seen.add(id);
                let hint = internal.hintElements.get(id);

                if (!hint) {
                    hint = this.createHint(node);
                    internal.hintElements.set(id, hint);
                    layer.append(hint.element);
                }

                const cx = w / 2;
                const cy = h / 2;
                const dx = point.x - cx;
                const dy = point.y - cy;
                const scale = Math.min((w / 2 - inset) / Math.abs(dx || 1e-6), (h / 2 - inset) / Math.abs(dy || 1e-6));
                const x = cx + dx * scale;
                const y = cy + dy * scale;
                const angle = (Math.atan2(dy, dx) * 180) / Math.PI;

                hint.element.dataset.role = role;
                hint.arrow.style.transform = `rotate(${angle.toFixed(0)}deg)`;
                hint.element.style.flexDirection = x > w / 2 ? 'row-reverse' : 'row';
                hint.caption.textContent = { current: 'You', reachable: 'Can travel', objective: 'Objective', arriving: 'Arriving', selected: 'Selected' }[role];

                /* Slide along the edge until the pill clears the others and the city card. */
                const width = hint.element.offsetWidth;
                const height = hint.element.offsetHeight;
                const onHorizontalEdge = Math.abs(y - inset) < 1 || Math.abs(y - (h - inset)) < 1;
                const start = { x: x > w / 2 ? x - width : x, y: y - height / 2 };
                let rect = null;

                for (const direction of [1, -1]) {
                    const candidate = { ...start, w: width, h: height };

                    for (let attempt = 0; attempt < 12; attempt++) {
                        const blocker = obstacles.find((other) => rectsOverlap(candidate, other));

                        if (!blocker) {
                            break;
                        }

                        if (onHorizontalEdge) {
                            candidate.x = direction > 0 ? blocker.x + blocker.w + 4 : blocker.x - width - 4;
                        } else {
                            candidate.y = direction > 0 ? blocker.y + blocker.h + 4 : blocker.y - height - 4;
                        }
                    }

                    const inBounds = candidate.x >= 4 && candidate.y >= 4 && candidate.x + width <= w - 4 && candidate.y + height <= h - 4;

                    if (inBounds && !obstacles.some((other) => rectsOverlap(candidate, other))) {
                        rect = candidate;
                        break;
                    }
                }

                if (!rect) {
                    hint.element.style.visibility = 'hidden';
                    continue;
                }

                obstacles.push({ x: rect.x - 2, y: rect.y - 2, w: rect.w + 4, h: rect.h + 4 });
                hint.element.style.visibility = 'visible';
                hint.element.style.transform = `translate(${rect.x.toFixed(1)}px, ${rect.y.toFixed(1)}px)`;
            }

            for (const [id, hint] of internal.hintElements) {
                if (!seen.has(id)) {
                    hint.element.remove();
                    internal.hintElements.delete(id);
                }
            }
        },

        createHint(node) {
            const element = document.createElement('button');
            element.type = 'button';
            element.dataset.mapUi = '';
            element.dataset.mapHint = '';
            element.className = [
                'absolute left-0 top-0 flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium shadow-sm backdrop-blur-sm transition-colors',
                'border-zinc-300 bg-white/90 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900/90 dark:text-zinc-200 dark:hover:border-zinc-500',
                'data-[role=reachable]:text-sky-700 dark:data-[role=reachable]:text-sky-300',
                'data-[role=objective]:text-amber-700 dark:data-[role=objective]:text-amber-300',
                'data-[role=arriving]:text-amber-700 dark:data-[role=arriving]:text-amber-300',
                'data-[role=current]:text-emerald-700 dark:data-[role=current]:text-emerald-300',
            ].join(' ');
            element.setAttribute('aria-label', `Show ${node.label} on the map`);

            const arrow = document.createElement('span');
            arrow.setAttribute('aria-hidden', 'true');
            arrow.className = 'inline-block leading-none';
            arrow.textContent = '➜';

            const text = document.createElement('span');
            text.className = 'flex flex-col items-start leading-tight';
            const name = document.createElement('span');
            name.textContent = node.label;
            const caption = document.createElement('span');
            caption.className = 'text-[9px] uppercase tracking-wider opacity-70';
            text.append(name, caption);

            element.append(arrow, text);
            element.addEventListener('click', () => this.focusCity(node.id));

            return { element, arrow, caption };
        },
    };
}

window.geoMap = geoMap;
