/**
 * SyntekPro Listings — QR Code generator (frontend)
 * Handles: QR button click → show overlay with generated QR image.
 */
/* global syntekproFrontend */
(function () {
	'use strict';

	var SP = window.syntekproFrontend || {};

	// ── Initialise any QR buttons already in the DOM ──────────────────────
	function init() {
		document.querySelectorAll('.sp-qr-btn').forEach(attachQrButton);
	}

	function attachQrButton(btn) {
		if (btn.dataset.qrInit) return;
		btn.dataset.qrInit = '1';
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var url   = btn.dataset.url  || window.location.href;
			var size  = parseInt(btn.dataset.size || 200, 10);
			var label = btn.dataset.label || '';
			showQr(url, size, label, btn);
		});
	}

	// ── Show QR in a small overlay / popover ──────────────────────────────
	function showQr(url, size, label, anchor) {
		var existing = document.getElementById('sp-qr-overlay');
		if (existing) existing.remove();

		// Build overlay element.
		var overlay = document.createElement('div');
		overlay.id = 'sp-qr-overlay';
		overlay.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;box-shadow:0 8px 32px rgba(0,0,0,.25);padding:24px;z-index:99999;text-align:center;min-width:240px;';

		var closeBtn = document.createElement('button');
		closeBtn.textContent = '✕';
		closeBtn.setAttribute('aria-label', 'Close');
		closeBtn.style.cssText = 'position:absolute;top:8px;right:10px;background:none;border:none;font-size:18px;cursor:pointer;';
		closeBtn.addEventListener('click', function () { overlay.remove(); backdrop.remove(); });

		var img = document.createElement('img');
		img.src = buildQrUrl(url, size);
		img.alt = 'QR code';
		img.width  = size;
		img.height = size;

		if (label) {
			var cap = document.createElement('p');
			cap.textContent = label;
			cap.style.marginTop = '8px';
		}

		var dlLink = document.createElement('a');
		dlLink.textContent = 'Download';
		dlLink.href = buildQrUrl(url, size);
		dlLink.download = 'qr-code.png';
		dlLink.style.display = 'block';
		dlLink.style.marginTop = '8px';
		dlLink.style.fontSize = '13px';

		overlay.appendChild(closeBtn);
		overlay.appendChild(img);
		if (label) overlay.appendChild(cap);
		overlay.appendChild(dlLink);

		// Backdrop.
		var backdrop = document.createElement('div');
		backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99998;';
		backdrop.addEventListener('click', function () { overlay.remove(); backdrop.remove(); });

		document.body.appendChild(backdrop);
		document.body.appendChild(overlay);
	}

	// ── Build QR URL (Google Charts API or server AJAX) ───────────────────
	function buildQrUrl(url, size) {
		// Prefer AJAX endpoint so it goes through the server cache.
		if (SP.ajaxUrl && SP.nonces && SP.nonces.qr) {
			return SP.ajaxUrl + '?action=sp_generate_qr&url=' + encodeURIComponent(url) + '&size=' + size + '&nonce=' + SP.nonces.qr;
		}
		// Fallback: Google Charts (publicly available endpoint).
		return 'https://chart.googleapis.com/chart?cht=qr&chs=' + size + 'x' + size + '&chl=' + encodeURIComponent(url);
	}

	// ── Boot ──────────────────────────────────────────────────────────────
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
