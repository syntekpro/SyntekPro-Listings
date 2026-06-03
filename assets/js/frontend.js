/**
 * SyntekPro Listings — Frontend JavaScript
 * Handles: gallery, enquiry forms, AJAX search, infinite scroll, autocomplete,
 *          shortlist, saved searches, send-to-friend, tab switching.
 */
/* global syntekproFrontend, jQuery */
(function ($) {
	'use strict';

	var SP = syntekproFrontend || {};

	/* ── Gallery ─────────────────────────────────────────────────────────── */
	$(document).on('click', '.sp-gallery-thumb', function () {
		var $thumb = $(this);
		var src    = $thumb.attr('src');
		$('.sp-main-photo').attr('src', src);
		$('.sp-gallery-thumb').removeClass('sp-thumb-active');
		$thumb.addClass('sp-thumb-active');
	});

	/* ── Tab switching ───────────────────────────────────────────────────── */
	$(document).on('click', '.sp-etab', function () {
		var tab = $(this).data('etab');
		$('.sp-etab').removeClass('active');
		$(this).addClass('active');
		$('.sp-etab-pane').attr('hidden', true).removeClass('active');
		$('#sp-' + tab + '-tab').removeAttr('hidden').addClass('active');
	});

	/* ── Enquiry form ────────────────────────────────────────────────────── */
	$(document).on('submit', '#sp-enquiry-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $btn  = $form.find('.sp-btn-submit').prop('disabled', true);
		var $msg  = $form.find('.sp-enquiry-response').text('');

		$.post(SP.ajaxUrl, $form.serialize(), function (res) {
			$msg.removeClass('success error').addClass(res.success ? 'success' : 'error')
			    .text(res.data && res.data.message ? res.data.message : (res.success ? SP.i18n.sent : SP.i18n.error));
			if (res.success) $form[0].reset();
		}).fail(function () {
			$msg.addClass('error').text(SP.i18n.error);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	/* ── Book viewing form ───────────────────────────────────────────────── */
	$(document).on('submit', '#sp-viewing-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $btn  = $form.find('.sp-btn-submit').prop('disabled', true);
		var $msg  = $form.find('.sp-viewing-response').text('');

		$.post(SP.ajaxUrl, $form.serialize(), function (res) {
			$msg.removeClass('success error').addClass(res.success ? 'success' : 'error')
			    .text(res.data && res.data.message ? res.data.message : (res.success ? SP.i18n.sent : SP.i18n.error));
			if (res.success) $form[0].reset();
		}).fail(function () {
			$msg.addClass('error').text(SP.i18n.error);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	/* ── Send to friend ──────────────────────────────────────────────────── */
	$(document).on('submit', '#sp-stf-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $msg  = $form.find('.sp-stf-response').text('');
		$.post(SP.ajaxUrl, $form.serialize(), function (res) {
			$msg.removeClass('success error').addClass(res.success ? 'success' : 'error')
			    .text(res.data && res.data.message ? res.data.message : (res.success ? SP.i18n.sent : SP.i18n.error));
			if (res.success) $form[0].reset();
		});
	});

	/* ── Shortlist toggle ────────────────────────────────────────────────── */
	$(document).on('click', '.sp-shortlist-toggle', function () {
		var $btn = $(this);
		var id   = $btn.data('id');
		$.post(SP.ajaxUrl, {
			action:  'sp_toggle_shortlist',
			listing_id: id,
			nonce:   SP.nonces.shortlist,
		}, function (res) {
			if (res.success) {
				var shortlisted = res.data && res.data.shortlisted;
				$btn.toggleClass('sp-shortlisted', !!shortlisted);
				$btn.text(shortlisted ? '♥' : '♡');
			}
		});
	});

	/* ── Load shortlist page ─────────────────────────────────────────────── */
	if ($('#sp-shortlist-container').length) {
		$.post(SP.ajaxUrl, {
			action: 'sp_get_shortlist',
			nonce:  SP.nonces.shortlist,
		}, function (res) {
			var $container = $('#sp-shortlist-container');
			$container.html('');
			if (res.success && res.data && res.data.listings && res.data.listings.length) {
				$.each(res.data.listings, function (i, l) {
					$container.append(
						'<div class="sp-listing-card">' +
						(l.thumb ? '<a href="' + l.url + '" class="sp-card-image-link"><img src="' + l.thumb + '" class="sp-card-image" loading="lazy"></a>' : '') +
						'<div class="sp-card-body"><div class="sp-card-price">' + (l.price || '') + '</div>' +
						'<h3 class="sp-card-title"><a href="' + l.url + '">' + l.title + '</a></h3>' +
						'<div class="sp-card-specs"><span>' + (l.beds || '') + '</span></div></div>' +
						'<button class="sp-shortlist-toggle sp-shortlisted" data-id="' + l.id + '">♥</button>' +
						'</div>'
					);
				});
			} else {
				$('#sp-shortlist-empty').removeAttr('hidden');
			}
		});
	}

	/* ── Saved searches page ─────────────────────────────────────────────── */
	if ($('#sp-saved-searches-list').length) {
		$.post(SP.ajaxUrl, {
			action: 'sp_get_saved_searches',
			nonce:  SP.nonces.savedSearch,
		}, function (res) {
			var $list = $('#sp-saved-searches-list');
			if (res.success && res.data && res.data.length) {
				var html = '<table class="sp-saved-searches-table"><thead><tr><th>Name</th><th>Email alerts</th><th></th></tr></thead><tbody>';
				$.each(res.data, function (i, s) {
					html += '<tr><td>' + (s.name || 'Search ' + s.id) + '</td><td>' + s.email + '</td>' +
					        '<td><button class="button sp-delete-saved-search" data-id="' + s.id + '">Delete</button></td></tr>';
				});
				html += '</tbody></table>';
				$list.html(html);
			} else {
				$list.html('<p>No saved searches.</p>');
			}
		});

		$(document).on('click', '.sp-delete-saved-search', function () {
			var id = $(this).data('id');
			if (!confirm('Delete this saved search?')) return;
			$.post(SP.ajaxUrl, { action: 'sp_delete_saved_search', id: id, nonce: SP.nonces.savedSearch }, function (res) {
				if (res.success) location.reload();
			});
		});
	}

	/* ── Save search button ──────────────────────────────────────────────── */
	$(document).on('click', '.sp-btn-save-search', function () {
		var email = prompt(SP.i18n.enterEmail || 'Enter your email for alerts:');
		if (!email) return;
		var params = {};
		$('#sp-search-form').serializeArray().forEach(function (item) { params[item.name] = item.value; });
		$.post(SP.ajaxUrl, {
			action: 'sp_save_search',
			params: JSON.stringify(params),
			email:  email,
			nonce:  SP.nonces.savedSearch,
		}, function (res) {
			alert(res.success ? (SP.i18n.searchSaved || 'Search saved!') : (res.data && res.data.message ? res.data.message : SP.i18n.error));
		});
	});

	/* ── Sort change → reload ────────────────────────────────────────────── */
	$(document).on('change', '#sp-sort', function () {
		var url = new URL(window.location.href);
		url.searchParams.set('sp_sort', this.value);
		window.location.href = url.toString();
	});

	/* ── Map view toggle ─────────────────────────────────────────────────── */
	$(document).on('click', '#sp-toggle-map', function () {
		var $map = $('#sp-map-view');
		var hidden = $map.attr('hidden') !== undefined;
		if (hidden) {
			$map.removeAttr('hidden');
			$(this).text(SP.i18n.gridView || 'Grid view');
		} else {
			$map.attr('hidden', true);
			$(this).text(SP.i18n.mapView || 'Map view');
		}
	});

	/* ── AJAX location autocomplete ──────────────────────────────────────── */
	var autocompleteTimer;
	$(document).on('input', '.sp-autocomplete', function () {
		var $input = $(this);
		var query  = $input.val().trim();
		clearTimeout(autocompleteTimer);
		$('.sp-autocomplete-dropdown').remove();
		if (query.length < 3) return;

		autocompleteTimer = setTimeout(function () {
			$.post(SP.ajaxUrl, {
				action: 'sp_autocomplete',
				q:      query,
				nonce:  SP.nonces.search,
			}, function (res) {
				if (!res.success || !res.data || !res.data.length) return;
				var $ul = $('<ul class="sp-autocomplete-dropdown">');
				var pos = $input.offset();
				var h   = $input.outerHeight();
				$ul.css({ top: pos.top + h, left: pos.left, width: $input.outerWidth() })
				   .css('position', 'absolute');
				$.each(res.data, function (i, item) {
					$ul.append('<li data-val="' + item + '">' + item + '</li>');
				});
				$('body').append($ul);
			});
		}, 300);
	});

	$(document).on('click', '.sp-autocomplete-dropdown li', function () {
		var val = $(this).data('val');
		$(this).closest('.sp-autocomplete-dropdown').prev('.sp-autocomplete').val(val);
		$('.sp-autocomplete-dropdown').remove();
	});

	$(document).on('click', function (e) {
		if (!$(e.target).hasClass('sp-autocomplete')) {
			$('.sp-autocomplete-dropdown').remove();
		}
	});

	/* ── Infinite scroll ─────────────────────────────────────────────────── */
	if ($('#sp-infinite-sentinel').length) {
		var loading = false;
		var sentinel = document.getElementById('sp-infinite-sentinel');
		var page = parseInt(sentinel.dataset.page, 10);
		var max  = parseInt(sentinel.dataset.max, 10);

		var observer = new IntersectionObserver(function (entries) {
			if (!entries[0].isIntersecting || loading || page >= max) return;
			loading = true;
			page++;
			var url = new URL(window.location.href);
			url.searchParams.set('paged', page);
			$.get(url.toString(), function (html) {
				var $cards = $(html).find('.sp-listings-grid .sp-listing-card');
				$('#sp-results-grid').append($cards);
				sentinel.dataset.page = page;
				loading = false;
			});
		}, { rootMargin: '200px' });
		observer.observe(sentinel);
	}

})(jQuery);
