/**
 * Builds resources/js/geo-map/world-geometry.json, the coastlines and country
 * borders the world map draws.
 *
 * Source: Natural Earth 1:50m admin-0 countries (public domain), as packaged by
 * world-atlas@2.0.2. The arcs are projected to Web Mercator, simplified, and
 * written back out as a small topology so shared borders stay seamless.
 *
 * Usage: node resources/js/geo-map/build-world-geometry.mjs [path/to/countries-50m.json]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const SOURCE_URL = 'https://cdn.jsdelivr.net/npm/world-atlas@2.0.2/countries-50m.json';
const OUTPUT = path.join(path.dirname(fileURLToPath(import.meta.url)), 'world-geometry.json');

/** Map width in output units; the height follows from the latitude window. */
const WIDTH = 1000;
const LAT_TOP = 84;
const LAT_BOTTOM = -60;

/** Douglas-Peucker tolerance in output units (1000 units = the whole world). */
const TOLERANCE = 0.06;
/** Polygons smaller than this (square output units) are dropped. */
const MIN_POLYGON_AREA = 0.02;
/** Coordinates are stored as integers at this many steps per output unit. */
const PRECISION = 20;

const mercatorY = (lat) => Math.log(Math.tan(Math.PI / 4 + (lat * Math.PI) / 360));
const yScale = WIDTH / (2 * Math.PI);
const TOP = mercatorY(LAT_TOP) * yScale;
const HEIGHT = TOP - mercatorY(LAT_BOTTOM) * yScale;

function project([lng, lat]) {
    const clamped = Math.max(LAT_BOTTOM, Math.min(LAT_TOP, lat));

    return [((lng + 180) / 360) * WIDTH, TOP - mercatorY(clamped) * yScale];
}

async function loadTopology() {
    const localPath = process.argv[2];

    if (localPath) {
        return JSON.parse(fs.readFileSync(localPath, 'utf8'));
    }

    const response = await fetch(SOURCE_URL);

    if (!response.ok) {
        throw new Error(`Could not download ${SOURCE_URL}: ${response.status}`);
    }

    return response.json();
}

function decodeArcs(topology) {
    const [sx, sy] = topology.transform.scale;
    const [tx, ty] = topology.transform.translate;

    return topology.arcs.map((arc) => {
        let x = 0;
        let y = 0;

        return arc.map(([dx, dy]) => {
            x += dx;
            y += dy;

            return project([x * sx + tx, y * sy + ty]);
        });
    });
}

function perpendicularDistance(point, start, end) {
    const dx = end[0] - start[0];
    const dy = end[1] - start[1];
    const lengthSquared = dx * dx + dy * dy;

    if (lengthSquared === 0) {
        return Math.hypot(point[0] - start[0], point[1] - start[1]);
    }

    const t = Math.max(0, Math.min(1, ((point[0] - start[0]) * dx + (point[1] - start[1]) * dy) / lengthSquared));

    return Math.hypot(point[0] - (start[0] + t * dx), point[1] - (start[1] + t * dy));
}

function simplifyOpen(points) {
    if (points.length < 3) {
        return points;
    }

    const keep = new Uint8Array(points.length);
    keep[0] = 1;
    keep[points.length - 1] = 1;
    const stack = [[0, points.length - 1]];

    while (stack.length) {
        const [first, last] = stack.pop();
        let maxDistance = 0;
        let index = -1;

        for (let i = first + 1; i < last; i++) {
            const distance = perpendicularDistance(points[i], points[first], points[last]);

            if (distance > maxDistance) {
                maxDistance = distance;
                index = i;
            }
        }

        if (maxDistance > TOLERANCE && index !== -1) {
            keep[index] = 1;
            stack.push([first, index], [index, last]);
        }
    }

    return points.filter((_, i) => keep[i]);
}

/** Closed arcs (islands drawn as one arc) are split at their farthest point so both halves survive. */
function simplifyArc(points) {
    const first = points[0];
    const last = points[points.length - 1];
    const closed = first[0] === last[0] && first[1] === last[1];

    if (!closed || points.length < 4) {
        return simplifyOpen(points);
    }

    let farthest = 1;
    let maxDistance = 0;

    points.forEach((point, i) => {
        const distance = Math.hypot(point[0] - first[0], point[1] - first[1]);

        if (distance > maxDistance) {
            maxDistance = distance;
            farthest = i;
        }
    });

    const head = simplifyOpen(points.slice(0, farthest + 1));
    const tail = simplifyOpen(points.slice(farthest));

    return [...head, ...tail.slice(1)];
}

function ringPoints(ring, arcs) {
    const points = [];

    for (const index of ring) {
        const arc = index < 0 ? [...arcs[~index]].reverse() : arcs[index];
        points.push(...(points.length ? arc.slice(1) : arc));
    }

    return points;
}

function ringArea(points) {
    let area = 0;

    for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
        area += (points[j][0] + points[i][0]) * (points[j][1] - points[i][1]);
    }

    return Math.abs(area / 2);
}

const topology = await loadTopology();
const projectedArcs = decodeArcs(topology).map(simplifyArc);

const usedArcs = new Map();
const useArc = (index) => {
    const arcIndex = index < 0 ? ~index : index;

    if (!usedArcs.has(arcIndex)) {
        usedArcs.set(arcIndex, usedArcs.size);
    }

    const newIndex = usedArcs.get(arcIndex);

    return index < 0 ? ~newIndex : newIndex;
};

const countries = [];

for (const geometry of topology.objects.countries.geometries) {
    const name = geometry.properties?.name ?? '';

    if (name === 'Antarctica' || !geometry.arcs) {
        continue;
    }

    const polygons = geometry.type === 'Polygon' ? [geometry.arcs] : geometry.arcs;
    const kept = polygons
        .filter((rings) => {
            const outer = ringPoints(rings[0], projectedArcs);

            return outer.length >= 4 && ringArea(outer) >= MIN_POLYGON_AREA;
        })
        .map((rings) => rings
            .filter((ring, i) => i === 0 || ringArea(ringPoints(ring, projectedArcs)) >= MIN_POLYGON_AREA)
            .map((ring) => ring.map(useArc)));

    if (kept.length) {
        countries.push({ n: name, p: kept });
    }
}

const arcs = [...usedArcs.keys()].map((arcIndex) => {
    let previousX = 0;
    let previousY = 0;

    return projectedArcs[arcIndex].flatMap(([x, y]) => {
        const qx = Math.round(x * PRECISION);
        const qy = Math.round(y * PRECISION);
        const delta = [qx - previousX, qy - previousY];
        previousX = qx;
        previousY = qy;

        return delta;
    });
});

const output = {
    source: 'Natural Earth 1:50m admin-0 countries (public domain) via world-atlas@2.0.2',
    projection: { type: 'mercator', width: WIDTH, height: Number(HEIGHT.toFixed(3)), latTop: LAT_TOP, latBottom: LAT_BOTTOM },
    precision: PRECISION,
    arcs,
    countries,
};

fs.writeFileSync(OUTPUT, JSON.stringify(output));

const pointCount = arcs.reduce((sum, arc) => sum + arc.length / 2, 0);
console.log(`Wrote ${OUTPUT}: ${countries.length} countries, ${arcs.length} arcs, ${pointCount} points, ${(fs.statSync(OUTPUT).size / 1024).toFixed(0)} KB`);
