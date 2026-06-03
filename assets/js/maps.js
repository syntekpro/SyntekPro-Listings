/**
 * SyntekPro Listings — Maps JavaScript
 * Initialises Google Maps / Mapbox / Leaflet per provider config.
 * Handles: property single map, archive/search map, marker clustering, AJAX markers.
 */
/* global syntekproMaps, google, mapboxgl, L */
(function () {
	'use strict';

	var cfg = window.syntekproMaps || {};
	var provider = cfg.provider || 'leaflet';

	/* ── Dispatch per provider ────────────────────────────────────────── */
	function initAll() {
		document.querySelectorAll('[id^="sp-map"]').forEach(function (el) {
			if (el.dataset.spMapInit) return;
			el.dataset.spMapInit = '1';

			var lat  = parseFloat(el.dataset.lat  || cfg.defaultLat || 51.5);
			var lng  = parseFloat(el.dataset.lng  || cfg.defaultLng || -0.12);
			var zoom = parseInt(el.dataset.zoom   || cfg.defaultZoom || 12, 10);
			var isSingle = !!el.dataset.lat;

			if (provider === 'google')  initGoogle(el, lat, lng, zoom, isSingle);
			else if (provider === 'mapbox') initMapbox(el, lat, lng, zoom, isSingle);
			else                            initLeaflet(el, lat, lng, zoom, isSingle);
		});
	}

	/* ── Google Maps ──────────────────────────────────────────────────── */
	function initGoogle(el, lat, lng, zoom, isSingle) {
		var map = new google.maps.Map(el, { center: { lat: lat, lng: lng }, zoom: zoom });
		if (isSingle) {
			new google.maps.Marker({ position: { lat: lat, lng: lng }, map: map });
		} else {
			loadMarkers(function (markers) {
				markers.forEach(function (m) {
					var marker = new google.maps.Marker({ position: { lat: m.lat, lng: m.lng }, map: map, title: m.title });
					marker.addListener('click', function () { window.location.href = m.url; });
				});
			});
			map.addListener('idle', function () {
				var bounds = map.getBounds();
				if (bounds) loadMarkersInBounds(bounds.toJSON(), map, 'google');
			});
		}
	}

	/* ── Mapbox GL JS ─────────────────────────────────────────────────── */
	function initMapbox(el, lat, lng, zoom, isSingle) {
		mapboxgl.accessToken = cfg.apiKey || '';
		var map = new mapboxgl.Map({ container: el, style: 'mapbox://styles/mapbox/streets-v12', center: [lng, lat], zoom: zoom });
		if (isSingle) {
			new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
		} else {
			map.on('moveend', function () {
				var bounds = map.getBounds();
				loadMarkersInBounds({ north: bounds.getNorth(), south: bounds.getSouth(), east: bounds.getEast(), west: bounds.getWest() }, map, 'mapbox');
			});
		}
	}

	/* ── Leaflet / OpenStreetMap ──────────────────────────────────────── */
	function initLeaflet(el, lat, lng, zoom, isSingle) {
		var map = L.map(el).setView([lat, lng], zoom);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			maxZoom: 19,
		}).addTo(map);
		if (isSingle) {
			L.marker([lat, lng]).addTo(map);
		} else {
			map.on('moveend', function () {
				var b = map.getBounds();
				loadMarkersInBounds({ north: b.getNorth(), south: b.getSouth(), east: b.getEast(), west: b.getWest() }, map, 'leaflet');
			});
			map.fire('moveend');
		}
	}

	/* ── Load markers via AJAX ────────────────────────────────────────── */
	function loadMarkersInBounds(bounds, map, type) {
		if (!window.jQuery) return;
		jQuery.post(cfg.ajaxUrl, {
			action: 'sp_map_listings',
			nonce:  cfg.nonce,
			north:  bounds.north, south: bounds.south,
			east:   bounds.east,  west:  bounds.west,
		}, function (res) {
			if (!res.success || !res.data) return;
			renderMarkers(res.data, map, type);
		});
	}

	function loadMarkers(callback) {
		if (!window.jQuery) return;
		jQuery.post(cfg.ajaxUrl, { action: 'sp_map_listings', nonce: cfg.nonce }, function (res) {
			if (res.success && res.data) callback(res.data);
		});
	}

	function renderMarkers(markers, map, type) {
		markers.forEach(function (m) {
			if (type === 'leaflet') {
				L.marker([m.lat, m.lng]).addTo(map)
				 .bindPopup('<a href="' + m.url + '"><strong>' + m.title + '</strong></a><br>' + (m.price || ''));
			} else if (type === 'mapbox') {
				var el2 = document.createElement('div');
				el2.className = 'sp-mapbox-marker';
				el2.innerHTML = '<div class="sp-marker-label">' + (m.price || '') + '</div>';
				new mapboxgl.Marker(el2).setLngLat([m.lng, m.lat]).setPopup(
					new mapboxgl.Popup({ offset: 25 }).setHTML('<a href="' + m.url + '">' + m.title + '</a>')
				).addTo(map);
			}
		});
	}

	/* ── Boot ─────────────────────────────────────────────────────────── */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	// Expose for Google Maps callback= usage.
	window.syntekproMapsInit = initAll;

})();
