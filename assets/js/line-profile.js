/**
 * GPLineProfile - reusable, dependency-free line elevation profile chart.
 *
 * No jQuery / OpenLayers / ExtJS dependency so it can be shared between the
 * CodeIgniter "plan3d" module (Bootstrap + OL) and other clients such as the
 * ExtJS 3.4 "gisapp" viewer. Only plain DOM APIs are used.
 *
 * Expected backend response shape (Level2 "geometry/drape" endpoint):
 * {
 *   geometry: "LINESTRING Z (x y z, x y z, ...)",
 *   distances: [0, 12.3, ...],
 *   segment_slopes_percent: [3.2, -1.1, ...],
 *   stats: {...}
 * }
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.GPLineProfile = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var MAX_PROFILE_LENGTH_M = 10000;

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function isFiniteNumber(value) {
        return typeof value === 'number' && isFinite(value);
    }

    function isProfileLengthAllowed(length) {
        return isFiniteNumber(length) && length >= 0 && length <= MAX_PROFILE_LENGTH_M;
    }

    function forEach(list, callback) {
        if (!list) {
            return;
        }
        for (var i = 0; i < list.length; i += 1) {
            if (callback(list[i], i) === false) {
                break;
            }
        }
    }

    function parseLineStringZCoordinates(geometryWkt) {
        if (typeof geometryWkt !== 'string') {
            return [];
        }

        var match = geometryWkt.match(/LINESTRING\s+Z\s*\((.*)\)/i);
        if (!match || !match[1]) {
            return [];
        }

        var coordinates = [];
        forEach(match[1].split(','), function (part) {
            var values = part.replace(/^\s+|\s+$/g, '').split(/\s+/);
            if (values.length < 3) {
                return;
            }

            var x = parseFloat(values[0]);
            var y = parseFloat(values[1]);
            var z = parseFloat(values[2]);
            if (!isFinite(x) || !isFinite(y) || !isFinite(z)) {
                return;
            }

            coordinates.push({x: x, y: y, z: z});
        });

        return coordinates;
    }

    function ensureDistanceSeries(distances) {
        var parsedDistances = [];
        forEach(distances, function (value) {
            var parsed = parseFloat(value);
            if (isFinite(parsed)) {
                parsedDistances.push(parsed);
            }
        });

        return parsedDistances;
    }

    function normalizeSlopeSeries(slopes, maxSegments) {
        var parsed = [];
        forEach(slopes, function (value, index) {
            if (index >= maxSegments) {
                return false;
            }

            var slope = parseFloat(value);
            parsed.push(isFinite(slope) ? slope : null);
            return true;
        });

        while (parsed.length < maxSegments) {
            parsed.push(null);
        }

        return parsed;
    }

    function slopeColor(slope) {
        if (!isFinite(slope) || Math.abs(slope) <= 1) {
            return '#8c93a1';
        }

        if (slope >= 0) {
            var upStrength = clamp(slope / 60, 0, 1);
            var upGreen = Math.round(190 - (130 * upStrength));
            var upBlue = Math.round(80 - (65 * upStrength));
            return 'rgb(214,' + upGreen + ',' + upBlue + ')';
        }

        var downStrength = clamp(Math.abs(slope) / 60, 0, 1);
        var downRed = Math.round(84 - (28 * downStrength));
        var downGreen = Math.round(171 - (66 * downStrength));
        return 'rgb(' + downRed + ',' + downGreen + ',214)';
    }

    function verticalExaggeration(points, opts) {
        opts = opts || {};
        var referenceWidthPx = opts.referenceWidthPx || 900;
        var referenceHeightPx = opts.referenceHeightPx || 210;
        var targetHeightRatio = opts.targetHeightRatio || 0.7;
        var minExaggeration = typeof opts.min === 'number' ? opts.min : 1;
        var maxExaggeration = typeof opts.max === 'number' ? opts.max : 10;

        if (!points || points.length < 2) {
            return minExaggeration;
        }

        var first = points[0];
        var last = points[points.length - 1];
        var length = Math.max(last.distance - first.distance, 0);
        if (length <= 0) {
            return minExaggeration;
        }

        var zMin = points[0].z;
        var zMax = points[0].z;
        forEach(points, function (point) {
            if (point.z < zMin) {
                zMin = point.z;
            }
            if (point.z > zMax) {
                zMax = point.z;
            }
        });

        var zRange = zMax - zMin;
        if (zRange <= 0) {
            return minExaggeration;
        }

        var exag = (targetHeightRatio * referenceHeightPx * length) / (zRange * referenceWidthPx);
        return Math.round(clamp(exag, minExaggeration, maxExaggeration));
    }

    /**
     * Builds a normalized profile state from a raw endpoint response.
     * Returns null when the response does not contain a usable profile.
     */
    function stateFromResponse(data, opts) {
        var coordinates = parseLineStringZCoordinates(data && data.geometry);
        if (coordinates.length < 2) {
            return null;
        }

        var distances = ensureDistanceSeries(data && data.distances);
        if (distances.length !== coordinates.length || distances.length < 2) {
            return null;
        }

        var size = coordinates.length;
        var points = [];
        for (var index = 0; index < size; index += 1) {
            points.push({
                x: coordinates[index].x,
                y: coordinates[index].y,
                z: coordinates[index].z,
                distance: distances[index]
            });
        }

        var slopes = normalizeSlopeSeries(data && data.segment_slopes_percent, size - 1);
        return {
            points: points,
            slopes: slopes,
            verticalExaggeration: verticalExaggeration(points, opts)
        };
    }

    function nearestPointIndex(distances, distanceValue) {
        if (!distances || !distances.length) {
            return -1;
        }

        if (distanceValue <= distances[0]) {
            return 0;
        }

        var lastIndex = distances.length - 1;
        if (distanceValue >= distances[lastIndex]) {
            return lastIndex;
        }

        var left = 0;
        var right = lastIndex;
        while (left <= right) {
            var middle = Math.floor((left + right) / 2);
            var current = distances[middle];
            if (current === distanceValue) {
                return middle;
            }

            if (current < distanceValue) {
                left = middle + 1;
            } else {
                right = middle - 1;
            }
        }

        if (left <= 0) {
            return 0;
        }

        if (left >= distances.length) {
            return distances.length - 1;
        }

        var previousDistance = distances[left - 1];
        var nextDistance = distances[left];
        return Math.abs(distanceValue - previousDistance) <= Math.abs(nextDistance - distanceValue) ? left - 1 : left;
    }

    function segmentIndexAtDistance(distances, distanceValue) {
        var left = 0;
        var right = distances.length;
        while (left < right) {
            var middle = Math.floor((left + right) / 2);
            if (distances[middle] <= distanceValue) {
                left = middle + 1;
            } else {
                right = middle;
            }
        }
        return clamp(left - 1, 0, distances.length - 2);
    }

    function interpolate(start, end, ratio) {
        return start + (end - start) * ratio;
    }

    function formatNumber(value, decimals, locale, naText) {
        var parsed = parseFloat(value);
        if (!isFinite(parsed)) {
            return naText || 'n/a';
        }

        return parsed.toLocaleString(locale || 'sl-SI', {
            minimumFractionDigits: 0,
            maximumFractionDigits: typeof decimals === 'number' ? decimals : 2
        });
    }

    function formatWithUnit(value, decimals, unit, locale, naText) {
        var formatted = formatNumber(value, decimals, locale, naText);
        if (formatted === (naText || 'n/a')) {
            return formatted;
        }

        return formatted + (unit ? ' ' + unit : '');
    }

    function escapeXml(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function endpointTangent(h0, h1, slope0, slope1) {
        var tangent = ((2 * h0 + h1) * slope0 - h0 * slope1) / (h0 + h1);
        if ((tangent > 0) !== (slope0 > 0) || slope0 === 0) {
            return 0;
        }
        if ((slope0 > 0) !== (slope1 > 0) && Math.abs(tangent) > 3 * Math.abs(slope0)) {
            return 3 * slope0;
        }
        return tangent;
    }

    function profileTangents(points) {
        var tangents = [];
        var start = 0;
        while (start < points.length - 1) {
            var end = start;
            while (end < points.length - 1 && points[end + 1].x > points[end].x) {
                end += 1;
            }
            if (end === start) {
                start += 1;
                continue;
            }

            var widths = [];
            var slopes = [];
            for (var i = start; i < end; i += 1) {
                widths.push(points[i + 1].x - points[i].x);
                slopes.push((points[i + 1].y - points[i].y) / widths[widths.length - 1]);
            }
            if (end === start + 1) {
                tangents[start] = slopes[0];
                tangents[end] = slopes[0];
            } else {
                tangents[start] = endpointTangent(widths[0], widths[1], slopes[0], slopes[1]);
                for (i = start + 1; i < end; i += 1) {
                    var previousSlope = slopes[i - start - 1];
                    var nextSlope = slopes[i - start];
                    if (previousSlope === 0 || nextSlope === 0 || (previousSlope > 0) !== (nextSlope > 0)) {
                        tangents[i] = 0;
                    } else {
                        var previousWidth = widths[i - start - 1];
                        var nextWidth = widths[i - start];
                        var weight1 = 2 * nextWidth + previousWidth;
                        var weight2 = nextWidth + 2 * previousWidth;
                        tangents[i] = (weight1 + weight2) / (weight1 / previousSlope + weight2 / nextSlope);
                    }
                }
                var last = slopes.length - 1;
                tangents[end] = endpointTangent(widths[last], widths[last - 1], slopes[last], slopes[last - 1]);
            }
            start = end + 1;
        }
        return tangents;
    }

    function niceTickStep(target) {
        var magnitude = Math.pow(10, Math.floor(Math.log(target) / Math.LN10));
        var fraction = target / magnitude;
        return (fraction >= 5 ? 5 : fraction >= 2 ? 2 : 1) * magnitude;
    }

    function tickValues(min, max, step) {
        var values = [];
        var first = Math.ceil(min / step - 1e-10);
        var last = Math.floor(max / step + 1e-10);
        for (var index = 0; index <= last - first; index += 1) {
            var tickIndex = first + index;
            values.push(tickIndex === 0 ? 0 : tickIndex * step);
        }
        return values;
    }

    /**
     * Builds the SVG markup and pixel layout for a profile state.
     * `idPrefix` should be unique per chart instance on the page.
     */
    function buildChart(state, opts) {
        opts = opts || {};
        var idPrefix = opts.idPrefix || 'gp-line-profile';
        var width = opts.width || 920;
        var height = opts.height || 230;
        var margin = opts.margin || {
            top: 14,
            right: 16,
            bottom: opts.distanceAxisLabel ? 43 : 28,
            left: opts.elevationAxisLabel ? 76 : 35
        };
        var plotWidth = width - margin.left - margin.right;
        var plotHeight = height - margin.top - margin.bottom;
        var points = state.points;
        var slopes = state.slopes;
        var firstDistance = points[0].distance;
        var lastDistance = points[points.length - 1].distance;
        var distanceRange = Math.max(lastDistance - firstDistance, 1e-9);
        var xScale = plotWidth / distanceRange;
        var exag = state.verticalExaggeration;

        var zMin = points[0].z;
        var zMax = points[0].z;
        forEach(points, function (point) {
            if (point.z < zMin) {
                zMin = point.z;
            }
            if (point.z > zMax) {
                zMax = point.z;
            }
        });

        var zRange = Math.max(zMax - zMin, 0);
        var yScale = xScale;
        if (zRange > 0) {
            var exaggeratedHeightPx = zRange * exag * yScale;
            if (exaggeratedHeightPx > plotHeight) {
                yScale = plotHeight / (zRange * exag);
            }
        }

        var finalHeightPx = zRange * exag * yScale;
        var yOffset = margin.top + (plotHeight - finalHeightPx) / 2;

        var projected = [];
        forEach(points, function (point) {
            var x = margin.left + ((point.distance - firstDistance) * xScale);
            var y = yOffset + ((zMax - point.z) * exag * yScale);
            projected.push({x: x, y: y});
        });

        var guideId = idPrefix + '-hover-guide';
        var horizontalGuideId = idPrefix + '-hover-horizontal-guide';
        var pointId = idPrefix + '-hover-point';
        var hitId = idPrefix + '-hover-hit';

        var svgParts = [];
        svgParts.push('<svg class="terrain-line-profile-svg" viewBox="0 0 ' + width + ' ' + height + '" preserveAspectRatio="none" aria-label="' + escapeXml(opts.ariaLabel || 'Height profile') + '">');
        svgParts.push('<line x1="' + margin.left + '" y1="' + (height - margin.bottom) + '" x2="' + (width - margin.right) + '" y2="' + (height - margin.bottom) + '" stroke="#9aa3b3" stroke-width="1"/>');
        svgParts.push('<line x1="' + margin.left + '" y1="' + margin.top + '" x2="' + margin.left + '" y2="' + (height - margin.bottom) + '" stroke="#9aa3b3" stroke-width="1"/>');

        var axisY = height - margin.bottom;
        var elevationScale = exag * yScale;
        var distanceIntervals = Math.max(1, Math.min(6, Math.floor(plotWidth / 110)));
        var elevationIntervals = Math.max(1, Math.min(5, Math.floor(plotHeight / 30)));
        var distanceStep = niceTickStep(Math.max((lastDistance - firstDistance) / distanceIntervals, 1));
        var elevationMin = zMax - (axisY - yOffset) / elevationScale;
        var elevationMax = zMax - (margin.top - yOffset) / elevationScale;
        var elevationTarget = (elevationMax - elevationMin) / elevationIntervals;
        var elevationStep = niceTickStep(Math.max(elevationTarget, 0.1));
        var elevationTicks = tickValues(elevationMin, elevationMax, elevationStep);
        if (elevationTicks.length < 3 && elevationTarget > 0 && elevationTarget < 0.1) {
            elevationStep = niceTickStep(Math.max(elevationTarget, 1e-10));
            elevationTicks = tickValues(elevationMin, elevationMax, elevationStep);
        }
        var elevationDecimals = clamp(Math.ceil(-Math.log(elevationStep) / Math.LN10 - 1e-10), 0, 10);
        svgParts.push('<g fill="#364154" font-family="sans-serif" font-size="11">');
        forEach(tickValues(firstDistance, lastDistance, distanceStep), function (tickDistance) {
            var tickX = margin.left + (tickDistance - firstDistance) * xScale;
            var anchor = tickX - margin.left < 25 ? 'start' : (width - margin.right - tickX < 25 ? 'end' : 'middle');
            svgParts.push('<line x1="' + tickX + '" y1="' + axisY + '" x2="' + tickX + '" y2="' + (axisY + 4) + '" stroke="#9aa3b3"/>');
            svgParts.push('<text x="' + tickX + '" y="' + (axisY + 16) + '" text-anchor="' + anchor + '">' + escapeXml(formatNumber(tickDistance, 0, opts.locale)) + '</text>');
        });
        forEach(elevationTicks, function (tickElevation) {
            var tickY = yOffset + (zMax - tickElevation) * elevationScale;
            var tickLabel = tickElevation.toLocaleString(opts.locale || 'sl-SI', {
                minimumFractionDigits: elevationDecimals,
                maximumFractionDigits: elevationDecimals
            });
            svgParts.push('<line x1="' + (margin.left - 4) + '" y1="' + tickY + '" x2="' + margin.left + '" y2="' + tickY + '" stroke="#9aa3b3"/>');
            svgParts.push('<text x="' + (margin.left - 7) + '" y="' + tickY + '" text-anchor="end" dominant-baseline="middle">' + escapeXml(tickLabel) + '</text>');
        });
        if (opts.distanceAxisLabel) {
            svgParts.push('<text x="' + (margin.left + plotWidth / 2) + '" y="' + Math.max(axisY + 27, height - 10) + '" text-anchor="middle">' + escapeXml(opts.distanceAxisLabel) + '</text>');
        }
        if (opts.elevationAxisLabel) {
            svgParts.push('<text transform="translate(' + Math.min(19, Math.max(12, margin.left - 34)) + ' ' + (margin.top + plotHeight / 2) + ') rotate(-90)" text-anchor="middle">' + escapeXml(opts.elevationAxisLabel) + '</text>');
        }
        svgParts.push('</g>');

        var tangents = profileTangents(projected);
        for (var i = 0; i < projected.length - 1; i += 1) {
            var current = projected[i];
            var next = projected[i + 1];
            var segmentWidth = next.x - current.x;
            var path = 'M ' + current.x.toFixed(2) + ' ' + current.y.toFixed(2);
            if (segmentWidth > 0) {
                path += ' C ' + (current.x + segmentWidth / 3).toFixed(2) + ' ' + (current.y + tangents[i] * segmentWidth / 3).toFixed(2) +
                    ' ' + (next.x - segmentWidth / 3).toFixed(2) + ' ' + (next.y - tangents[i + 1] * segmentWidth / 3).toFixed(2) +
                    ' ' + next.x.toFixed(2) + ' ' + next.y.toFixed(2);
            } else {
                path += ' L ' + next.x.toFixed(2) + ' ' + next.y.toFixed(2);
            }
            svgParts.push('<path d="' + path + '" fill="none" stroke="' + slopeColor(slopes[i]) + '" stroke-width="3" stroke-linecap="round"/>');
        }

        svgParts.push('<line id="' + guideId + '" x1="' + margin.left + '" y1="' + margin.top + '" x2="' + margin.left + '" y2="' + (height - margin.bottom) + '" stroke="#364154" stroke-width="1" stroke-dasharray="4 4" style="display:none;"/>');
        svgParts.push('<line id="' + horizontalGuideId + '" x1="' + margin.left + '" y1="' + margin.top + '" x2="' + (width - margin.right) + '" y2="' + margin.top + '" stroke="#364154" stroke-width="1" stroke-dasharray="4 4" style="display:none;"/>');
        svgParts.push('<ellipse id="' + pointId + '" cx="' + margin.left + '" cy="' + (height - margin.bottom) + '" rx="5" ry="5" fill="#1f2937" stroke="#fff" stroke-width="2" vector-effect="non-scaling-stroke" style="display:none;"/>');
        svgParts.push('<rect id="' + hitId + '" x="' + margin.left + '" y="' + margin.top + '" width="' + plotWidth + '" height="' + plotHeight + '" fill="transparent"/>');
        svgParts.push('</svg>');

        return {
            svg: svgParts.join(''),
            layout: {
                idPrefix: idPrefix,
                guideId: guideId,
                horizontalGuideId: horizontalGuideId,
                pointId: pointId,
                hitId: hitId,
                width: width,
                height: height,
                margin: margin,
                plotWidth: plotWidth,
                plotHeight: plotHeight,
                firstDistance: firstDistance,
                distanceRange: distanceRange,
                rightEdge: width - margin.right,
                projected: projected
            }
        };
    }

    /**
     * Binds hover interaction to a chart previously built with buildChart().
     * `container` must be the DOM element the chart SVG was inserted into.
     * Returns a detach() function to remove the listeners.
     */
    function attachHover(container, state, layout, callbacks) {
        callbacks = callbacks || {};
        var hoverGuide = container.querySelector('#' + layout.guideId);
        var horizontalGuide = layout.horizontalGuideId && container.querySelector('#' + layout.horizontalGuideId);
        var hoverPoint = container.querySelector('#' + layout.pointId);
        var hoverHit = container.querySelector('#' + layout.hitId);
        if (!hoverHit) {
            return function () {};
        }

        var distances = [];
        var orderedDistances = true;
        forEach(state.points, function (point, index) {
            if (index > 0 && point.distance < distances[index - 1]) {
                orderedDistances = false;
            }
            distances.push(point.distance);
        });

        function onMouseMove(event) {
            var rect = hoverHit.getBoundingClientRect();
            var hitWidth = rect.width || layout.plotWidth;
            var ratio = clamp((event.clientX - rect.left) / hitWidth, 0, 1);
            if (!distances.length) {
                return;
            }

            var lastIndex = distances.length - 1;
            var distance = orderedDistances ?
                interpolate(distances[0], distances[lastIndex], ratio) :
                layout.firstDistance + (ratio * layout.distanceRange);
            var segmentIndex = orderedDistances && lastIndex > 0 ?
                segmentIndexAtDistance(distances, distance) : -1;
            var profilePoint;
            var projectedPoint;
            var slopeValue;
            if (segmentIndex >= 0 && distances[segmentIndex + 1] > distances[segmentIndex]) {
                var start = state.points[segmentIndex];
                var end = state.points[segmentIndex + 1];
                var startProjected = layout.projected[segmentIndex];
                var endProjected = layout.projected[segmentIndex + 1];
                var segmentRatio = clamp((distance - start.distance) / (end.distance - start.distance), 0, 1);
                profilePoint = {
                    x: interpolate(start.x, end.x, segmentRatio),
                    y: interpolate(start.y, end.y, segmentRatio),
                    z: interpolate(start.z, end.z, segmentRatio),
                    distance: distance
                };
                projectedPoint = {
                    x: interpolate(startProjected.x, endProjected.x, segmentRatio),
                    y: interpolate(startProjected.y, endProjected.y, segmentRatio)
                };
                slopeValue = state.slopes[segmentIndex];
            } else {
                var nearestIndex = orderedDistances && distances[0] !== distances[lastIndex] ?
                    lastIndex : nearestPointIndex(distances, distance);
                profilePoint = state.points[nearestIndex];
                projectedPoint = layout.projected[nearestIndex];
                slopeValue = nearestIndex < state.slopes.length ?
                    state.slopes[nearestIndex] : state.slopes[nearestIndex - 1];
            }

            if (hoverGuide) {
                hoverGuide.setAttribute('x1', projectedPoint.x);
                hoverGuide.setAttribute('x2', projectedPoint.x);
                hoverGuide.style.display = '';
            }
            if (horizontalGuide) {
                horizontalGuide.setAttribute('y1', projectedPoint.y);
                horizontalGuide.setAttribute('y2', projectedPoint.y);
                horizontalGuide.style.display = '';
            }
            if (hoverPoint) {
                var hitHeight = rect.height || layout.plotHeight;
                hoverPoint.setAttribute('cx', projectedPoint.x);
                hoverPoint.setAttribute('cy', projectedPoint.y);
                // Compensate for unequal SVG scaling in the two directions.
                hoverPoint.setAttribute('ry', 5 * (hitWidth / layout.plotWidth) / (hitHeight / layout.plotHeight));
                hoverPoint.style.display = '';
            }

            if (typeof callbacks.onHover === 'function') {
                callbacks.onHover(profilePoint, slopeValue, projectedPoint);
            }
        }

        function onMouseLeave() {
            if (hoverGuide) {
                hoverGuide.style.display = 'none';
            }
            if (horizontalGuide) {
                horizontalGuide.style.display = 'none';
            }
            if (hoverPoint) {
                hoverPoint.style.display = 'none';
            }

            if (typeof callbacks.onLeave === 'function') {
                callbacks.onLeave();
            }
        }

        hoverHit.addEventListener('mousemove', onMouseMove);
        hoverHit.addEventListener('mouseleave', onMouseLeave);

        return function detach() {
            hoverHit.removeEventListener('mousemove', onMouseMove);
            hoverHit.removeEventListener('mouseleave', onMouseLeave);
        };
    }

    return {
        maxProfileLengthM: MAX_PROFILE_LENGTH_M,
        clamp: clamp,
        isFiniteNumber: isFiniteNumber,
        isProfileLengthAllowed: isProfileLengthAllowed,
        parseLineStringZCoordinates: parseLineStringZCoordinates,
        ensureDistanceSeries: ensureDistanceSeries,
        normalizeSlopeSeries: normalizeSlopeSeries,
        slopeColor: slopeColor,
        verticalExaggeration: verticalExaggeration,
        stateFromResponse: stateFromResponse,
        nearestPointIndex: nearestPointIndex,
        formatNumber: formatNumber,
        formatWithUnit: formatWithUnit,
        buildChart: buildChart,
        attachHover: attachHover
    };
}));
