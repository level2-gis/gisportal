(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.GPPlan3dDxf = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function inspectEntities(text) {
        var lines = text.replace(/^\uFEFF/, '').split(/\r\n|\r|\n/);
        while (lines.length && lines[lines.length - 1].trim() === '') {
            lines.pop();
        }
        if (lines.length % 2 !== 0) {
            throw new Error('Neveljavna datoteka DXF.');
        }
        var inEntities = false;
        var seenEntities = false;
        var inPolyline = false;
        var count = 0;
        var eof = false;
        for (var i = 0; i < lines.length; i += 2) {
            var code = lines[i].trim();
            var value = lines[i + 1].trim();
            if (!/^\d+$/.test(code)) {
                throw new Error('Neveljavna datoteka DXF.');
            }
            if (code === '0' && value === 'EOF') {
                eof = true;
            }
            if (code === '0' && value === 'SECTION' && lines[i + 2] && lines[i + 2].trim() === '2' && lines[i + 3] && lines[i + 3].trim() === 'ENTITIES') {
                if (seenEntities) {
                    throw new Error('Neveljavna datoteka DXF.');
                }
                seenEntities = true;
                inEntities = true;
                i += 2;
                continue;
            }
            if (!inEntities || code !== '0') {
                continue;
            }
            if (inPolyline) {
                if (value === 'VERTEX') {
                    continue;
                }
                if (value === 'SEQEND') {
                    inPolyline = false;
                    continue;
                }
                throw new Error('Neveljavna polilinija DXF.');
            }
            if (value === 'ENDSEC') {
                inEntities = false;
                continue;
            }
            if (value !== 'LINE' && value !== 'LWPOLYLINE' && value !== 'POLYLINE') {
                throw new Error('DXF vsebuje nepodprt element: ' + value + '.');
            }
            count++;
            if (count > 2) {
                throw new Error('DXF lahko vsebuje največ eno linijo in en poligon.');
            }
            inPolyline = value === 'POLYLINE';
        }
        if (!seenEntities || inEntities || inPolyline || !eof || !count) {
            throw new Error('DXF ne vsebuje veljavne geometrije.');
        }
        return count;
    }

    function samePoint(a, b) {
        return a[0] === b[0] && a[1] === b[1];
    }

    function read(text, Parser) {
        var count = inspectEntities(text);
        if (typeof Parser !== 'function') {
            throw new Error('Knjižnica za DXF ni na voljo.');
        }
        var parsed = new Parser().parseSync(text.replace(/^\uFEFF/, ''));
        if (!parsed || !parsed.entities || parsed.entities.length !== count) {
            throw new Error('Datoteke DXF ni bilo mogoče prebrati.');
        }
        var result = [];
        var found = {};
        parsed.entities.forEach(function (entity) {
            var extrusion = entity.extrusionDirection;
            if ((extrusion && (extrusion.x !== 0 || extrusion.y !== 0 || extrusion.z !== 1)) ||
                (entity.extrusionDirectionX !== undefined && entity.extrusionDirectionX !== 0) ||
                (entity.extrusionDirectionY !== undefined && entity.extrusionDirectionY !== 0) ||
                (entity.extrusionDirectionZ !== undefined && entity.extrusionDirectionZ !== 1) ||
                entity.includesCurveFitVertices || entity.includesSplineFitVertices ||
                entity.is3dPolygonMesh || entity.isPolyfaceMesh) {
                throw new Error('DXF vsebuje nepodprto polilinijo.');
            }
            var vertices = entity.vertices || [];
            var coordinates = vertices.map(function (vertex) {
                if (!isFinite(vertex.x) || !isFinite(vertex.y) || vertex.x === undefined || vertex.y === undefined ||
                    (vertex.z !== undefined && !isFinite(vertex.z)) ||
                    (vertex.bulge !== undefined && (!isFinite(vertex.bulge) || vertex.bulge !== 0)) ||
                    vertex.curveFittingVertex || vertex.curveFitTangent || vertex.splineVertex ||
                    vertex.splineControlPoint || vertex.threeDPolylineMesh || vertex.polyfaceMeshVertex) {
                    throw new Error('DXF vsebuje neveljavne koordinate ali ukrivljene odseke.');
                }
                return [vertex.x, vertex.y];
            });
            var closed = !!entity.shape || (coordinates.length > 2 && samePoint(coordinates[0], coordinates[coordinates.length - 1]));
            if (closed && coordinates.length && !samePoint(coordinates[0], coordinates[coordinates.length - 1])) {
                coordinates.push(coordinates[0].slice());
            }
            if (coordinates.length < (closed ? 4 : 2)) {
                throw new Error('DXF vsebuje neveljavno geometrijo.');
            }
            var type = closed ? 'Polygon' : 'LineString';
            if (found[type]) {
                throw new Error('DXF lahko vsebuje največ eno linijo in en poligon.');
            }
            found[type] = true;
            result.push({type: type, coordinates: closed ? [coordinates] : coordinates});
        });
        return result;
    }

    function write(type, coordinates) {
        var closed = type === 'Polygon';
        if (type !== 'LineString' && !closed) {
            throw new Error('Geometrija ni podprta za izvoz DXF.');
        }
        if (closed) {
            if (coordinates.length !== 1) {
                throw new Error('Izvoz DXF ne podpira poligonov z luknjami.');
            }
            coordinates = coordinates[0].slice();
            if (coordinates.length && samePoint(coordinates[0], coordinates[coordinates.length - 1])) {
                coordinates.pop();
            }
        }
        if (coordinates.length < (closed ? 3 : 2)) {
            throw new Error('Geometrija ni veljavna za izvoz DXF.');
        }
        var hasZ = coordinates.some(function (point) { return point.length > 2; });
        var pairs = ['0', 'SECTION', '2', 'HEADER', '9', '$ACADVER', '1', 'AC1009', '0', 'ENDSEC',
            '0', 'SECTION', '2', 'ENTITIES', '0', 'POLYLINE', '8', '0', '66', '1', '10', '0', '20', '0', '30', '0',
            '70', String((closed ? 1 : 0) + (hasZ ? 8 : 0))];
        coordinates.forEach(function (point) {
            if (!isFinite(point[0]) || !isFinite(point[1]) || (hasZ && (point.length < 3 || !isFinite(point[2])))) {
                throw new Error('Geometrija vsebuje neveljavne koordinate.');
            }
            pairs.push('0', 'VERTEX', '8', '0', '10', String(point[0]), '20', String(point[1]),
                '30', String(hasZ ? point[2] : 0), '70', hasZ ? '32' : '0');
        });
        pairs.push('0', 'SEQEND', '8', '0', '0', 'ENDSEC', '0', 'EOF');
        return pairs.join('\r\n') + '\r\n';
    }

    return {read: read, write: write};
}));
