/**
 * SyntekPro Listings — Draw search (polygon freehand / polygon draw mode)
 * Supports Google Maps drawing manager and Leaflet.draw.
 */
/* global syntekproMaps, google, L */
(function () {
	'use strict';

	var cfg      = window.syntekproMaps || {};
	var provider = cfg.provider || 'leaflet';
	var drawMode = false;
	var currentPolygon = null;

	/* ── Toggle draw mode ─────────────────────────────────────────────── */
	document.addEventListener('click', function (e) {
		if (e.target && e.target.id === 'sp-draw-mode-btn') {
			drawMode = !drawMode;
			e.target.textContent = drawMode ? (cfg.i18n && cfg.i18n.cancelDraw ? cfg.i18n.cancelDraw : 'Cancel draw') : (cfg.i18n && cfg.i18n.drawArea ? cfg.i18n.drawArea : 'Draw search area');
			document.getElementById('sp-clear-draw-btn') && (document.getElementById('sp-clear-draw-btn').hidden = !drawMode);
			if (drawMode) {
				startDraw();
			} else {
				stopDraw();
			}
		}
		if (e.target && e.target.id === 'sp-clear-draw-btn') {
			clearDraw();
		}
	});

	function startDraw() {
		if (provider === 'google' && window.google) startGoogleDraw();
		else if (provider === 'leaflet' && window.L) startLeafletDraw();
	}

	function stopDraw() {
		drawMode = false;
	}

	function clearDraw() {
		if (currentPolygon) {
			if (typeof currentPolygon.setMap === 'function') currentPolygon.setMap(null);
			if (typeof currentPolygon.remove === 'function') currentPolygon.remove();
			currentPolygon = null;
		}
		// Clear hidden input.
		var input = document.getElementById('sp-polygon-input');
		if (input) input.value = '';
	}

	/* ── Google Drawing Manager ───────────────────────────────────────── */
	function startGoogleDraw() {
		var map = window._spGoogleMap;
		if (!map) return;
		var dm = new google.maps.drawing.DrawingManager({
			drawingMode:    google.maps.drawing.OverlayType.POLYGON,
			drawingControl: false,
			polygonOptions: { fillColor: '#1a73e8', fillOpacity: 0.2, strokeColor: '#1a73e8', strokeWeight: 2 },
		});
		dm.setMap(map);
		google.maps.event.addListener(dm, 'polygoncomplete', function (polygon) {
			currentPolygon = polygon;
			dm.setDrawingMode(null);
			dm.setMap(null);
			// Extract path and store in hidden input.
			var path = polygon.getPath().getArray().map(function (p) { return { lat: p.lat(), lng: p.lng() }; });
			storePolygon(path);
		});
	}

	/* ── Leaflet.draw ─────────────────────────────────────────────────── */
	function startLeafletDraw() {
		var map = window._spLeafletMap;
		if (!map) return;
		if (!L.Control.Draw) return; // Leaflet.draw not loaded.
		var drawnItems = new L.FeatureGroup().addTo(map);
		var drawControl = new L.Control.Draw({ draw: { polygon: true, polyline: false, circle: false, marker: false, rectangle: false } });
		map.addControl(drawControl);
		map.on(L.Draw.Event.CREATED, function (event) {
			if (currentPolygon) currentPolygon.remove();
			currentPolygon = event.layer;
			drawnItems.addLayer(currentPolygon);
			var latlngs = currentPolygon.getLatLngs()[0].map(function (p) { return { lat: p.lat, lng: p.lng }; });
			storePolygon(latlngs);
			map.removeControl(drawControl);
		});
	}

	/* ── Store polygon path and trigger search ────────────────────────── */
	function storePolygon(path) {
		var json = JSON.stringify(path);
		var input = document.getElementById('sp-polygon-input');
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'sp_polygon';
			input.id   = 'sp-polygon-input';
			var form = document.getElementById('sp-search-form');
			if (form) form.appendChild(input);
		}
		input.value = json;
		// Optionally auto-submit.
		if (cfg.autoSearchOnDraw) {
			var form = document.getElementById('sp-search-form');
			if (form) form.submit();
		}
	}

})();
