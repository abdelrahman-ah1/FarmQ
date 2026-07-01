(function () {
    'use strict';

    var el = document.getElementById('farm-map');
    if (!el || typeof L === 'undefined' || !window.FarmQMap) {
        return;
    }

    var cfg = window.FarmQMap;
    var map = L.map(el).setView([cfg.center.lat, cfg.center.lng], 13);
    var layers = { boundary: null, deficiency: null };

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var boundsLayers = [];

    if (cfg.farmPolygon && cfg.farmPolygon.coordinates) {
        layers.boundary = L.geoJSON({ type: 'Feature', geometry: cfg.farmPolygon }, {
            style: {
                color: '#2ea84a',
                weight: 3,
                fillColor: '#2ea84a',
                fillOpacity: 0.12
            }
        }).addTo(map);
        boundsLayers.push(layers.boundary);
        layers.boundary.bindPopup(cfg.locale === 'ar' ? 'حدود المزرعة' : 'Farm boundary');
    }

    var colors = { n: '#e74c3c', p: '#3498db', k: '#9b59b6' };

    if (cfg.deficiency && cfg.deficiency.features) {
        layers.deficiency = L.geoJSON(cfg.deficiency, {
            style: function (feature) {
                var element = feature.properties && feature.properties.element;
                return {
                    color: colors[element] || '#2ea84a',
                    weight: 2,
                    fillColor: colors[element] || '#2ea84a',
                    fillOpacity: 0.35
                };
            },
            onEachFeature: function (feature, layer) {
                var p = feature.properties || {};
                var label = (p.element || '').toUpperCase() + ' — ' + (p.severity || '');
                layer.bindPopup(label);
            }
        }).addTo(map);
        boundsLayers.push(layers.deficiency);
    }

    L.circleMarker([cfg.center.lat, cfg.center.lng], {
        radius: 8,
        color: '#4a3428',
        fillColor: '#2ea84a',
        fillOpacity: 0.9,
        weight: 2
    }).addTo(map).bindPopup(cfg.locale === 'ar' ? 'مركز المزرعة' : 'Farm center');

    if (boundsLayers.length) {
        try {
            map.fitBounds(L.featureGroup(boundsLayers).getBounds(), { padding: [24, 24] });
        } catch (e) {
            map.setView([cfg.center.lat, cfg.center.lng], 13);
        }
    }

    var boundaryToggle = document.getElementById('layer-boundary');
    var deficiencyToggle = document.getElementById('layer-deficiency');

    if (boundaryToggle && layers.boundary) {
        boundaryToggle.addEventListener('change', function () {
            if (boundaryToggle.checked) {
                map.addLayer(layers.boundary);
            } else {
                map.removeLayer(layers.boundary);
            }
        });
    }

    if (deficiencyToggle && layers.deficiency) {
        deficiencyToggle.addEventListener('change', function () {
            if (deficiencyToggle.checked) {
                map.addLayer(layers.deficiency);
            } else {
                map.removeLayer(layers.deficiency);
            }
        });
    }
})();
