/**
 * SyntekPro Listings — Admin JavaScript
 * Handles: media uploader, room repeater, geocode button, gallery drag-sort,
 *          CRM CRUD, import/export, portals management.
 */
/* global syntekproAdmin, jQuery, wp, FullCalendar */
(function ($) {
	'use strict';

	var SP = syntekproAdmin || {};

	/* ─── Media uploader ──────────────────────────────────────────────── */
	$(document).on('click', '.sp-media-upload-btn', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var $target = $($btn.data('target'));
		var $preview= $($btn.data('preview'));
		var frame = wp.media({
			title:    $btn.data('title') || 'Select Image',
			button:   { text: 'Use this image' },
			multiple: !!$btn.data('multiple'),
		});
		frame.on('select', function () {
			var selection = frame.state().get('selection');
			if ($btn.data('multiple')) {
				var urls = [];
				selection.each(function (att) { urls.push(att.toJSON().url); });
				$target.val(JSON.stringify(urls));
				$preview.html('');
				urls.forEach(function (url) {
					$preview.append('<img src="' + url + '" style="width:80px;height:56px;object-fit:cover;margin:2px;">');
				});
			} else {
				var att = selection.first().toJSON();
				$target.val(att.url);
				if ($preview.length) $preview.attr('src', att.url).removeAttr('hidden');
			}
		});
		frame.open();
	});

	/* ─── Rooms repeater ──────────────────────────────────────────────── */
	$(document).on('click', '.sp-add-room-btn', function () {
		var $list = $('#sp-rooms-list');
		var index = $list.children().length;
		$list.append(
			'<div class="sp-room-row" style="display:flex;gap:8px;margin-bottom:6px;">' +
			'<input type="text"   name="sp_rooms[' + index + '][name]"   placeholder="Name (e.g. Master Bedroom)" style="flex:2;">' +
			'<input type="number" name="sp_rooms[' + index + '][length]" placeholder="Length (ft)" style="flex:1;" min="0" step="0.1">' +
			'<input type="number" name="sp_rooms[' + index + '][width]"  placeholder="Width (ft)"  style="flex:1;" min="0" step="0.1">' +
			'<button type="button" class="button sp-remove-room-btn">&times;</button>' +
			'</div>'
		);
	});
	$(document).on('click', '.sp-remove-room-btn', function () {
		$(this).closest('.sp-room-row').remove();
	});

	/* ─── Geocode button ──────────────────────────────────────────────── */
	$(document).on('click', '#sp-geocode-btn', function () {
		var address = $('#_sp_address1').val() + ', ' + $('#_sp_town').val() + ', ' + $('#_sp_postcode').val();
		if (!address.trim()) return;
		$.post(ajaxurl, {
			action:  'sp_geocode',
			address: address,
			nonce:   SP.nonces.geocode,
		}, function (res) {
			if (res.success && res.data) {
				$('#_sp_latitude').val(res.data.lat);
				$('#_sp_longitude').val(res.data.lng);
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Geocoding failed.');
			}
		});
	});

	/* ─── Gallery sortable ────────────────────────────────────────────── */
	if ($('#sp-gallery-preview').length) {
		$('#sp-gallery-preview').sortable({ update: function () {
			var urls = [];
			$('#sp-gallery-preview img').each(function () { urls.push($(this).attr('src')); });
			$('#_sp_photos').val(JSON.stringify(urls));
		}});
	}

	/* ─── CRM tabs ────────────────────────────────────────────────────── */
	$(document).on('click', '.sp-tab-btn', function () {
		var tab = $(this).data('tab');
		$('.sp-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.sp-tab-pane').attr('hidden', true);
		$('#sp-tab-' + tab).removeAttr('hidden');
		loadCrmTab(tab);
	});

	/* ─── Load CRM enquiries on page load ─────────────────────────────── */
	if ($('#sp-tab-enquiries').length) loadCrmTab('enquiries');

	function loadCrmTab(tab) {
		switch (tab) {
			case 'enquiries': loadEnquiries(); break;
			case 'viewings':  loadViewings();  break;
			case 'contacts':  loadContacts();  break;
			case 'tasks':     loadTasks();     break;
			case 'calendar':  initCalendar();  break;
		}
	}

	/* ─── Enquiries ───────────────────────────────────────────────────── */
	function loadEnquiries() {
		$.post(ajaxurl, { action: 'sp_crm_get_enquiries', nonce: SP.nonces.crm }, function (res) {
			if (!res.success) return;
			var html = renderTable(['Name','Email','Listing','Status','Date'], res.data, ['name','email','listing_title','status','created_at']);
			$('#sp-enquiries-list').html(html);
		});
	}

	/* ─── Viewings ────────────────────────────────────────────────────── */
	function loadViewings() {
		$.post(ajaxurl, { action: 'sp_crm_get_viewings', nonce: SP.nonces.crm }, function (res) {
			if (!res.success) return;
			var html = renderTable(['Name','Email','Listing','Date','Time','Status'], res.data, ['name','email','listing_title','viewing_date','viewing_time','status']);
			$('#sp-viewings-list').html(html);
		});
	}

	/* ─── Contacts ────────────────────────────────────────────────────── */
	function loadContacts() {
		$.post(ajaxurl, { action: 'sp_crm_get_contacts', nonce: SP.nonces.crm }, function (res) {
			if (!res.success) return;
			var html = renderTableWithActions(['First','Last','Email','Phone'], res.data, ['first_name','last_name','email','phone'], 'contact');
			$('#sp-contacts-list').html(html);
		});
	}
	$(document).on('click', '#sp-add-contact-btn', function () {
		$('#sp-contact-id').val('');
		$('#sp-contact-first-name,#sp-contact-last-name,#sp-contact-email,#sp-contact-phone,#sp-contact-notes').val('');
		$('#sp-contact-modal').removeAttr('hidden');
	});
	$(document).on('click', '.sp-modal-close', function () {
		$(this).closest('.sp-modal').attr('hidden', true);
	});
	$(document).on('click', '#sp-save-contact-btn', function () {
		$.post(ajaxurl, {
			action:      'sp_crm_save_contact',
			nonce:       SP.nonces.crm,
			id:          $('#sp-contact-id').val(),
			first_name:  $('#sp-contact-first-name').val(),
			last_name:   $('#sp-contact-last-name').val(),
			email:       $('#sp-contact-email').val(),
			phone:       $('#sp-contact-phone').val(),
			notes:       $('#sp-contact-notes').val(),
		}, function (res) {
			if (res.success) { $('#sp-contact-modal').attr('hidden', true); loadContacts(); }
			else alert(res.data && res.data.message ? res.data.message : 'Error saving contact.');
		});
	});
	$(document).on('click', '.sp-edit-contact', function () {
		var id = $(this).data('id');
		// Populate from row data attributes.
		var $row = $(this).closest('tr');
		$('#sp-contact-id').val(id);
		$('#sp-contact-first-name').val($row.data('first'));
		$('#sp-contact-last-name').val($row.data('last'));
		$('#sp-contact-email').val($row.data('email'));
		$('#sp-contact-phone').val($row.data('phone'));
		$('#sp-contact-modal').removeAttr('hidden');
	});

	/* ─── Tasks ───────────────────────────────────────────────────────── */
	function loadTasks() {
		$.post(ajaxurl, { action: 'sp_crm_get_tasks', nonce: SP.nonces.crm }, function (res) {
			if (!res.success) return;
			var html = renderTable(['Task','Due date','Status'], res.data, ['title','due_date','status']);
			$('#sp-tasks-list').html(html);
		});
	}

	/* ─── FullCalendar ────────────────────────────────────────────────── */
	var calendarInit = false;
	function initCalendar() {
		if (calendarInit) return;
		calendarInit = true;
		var el = document.getElementById('sp-fullcalendar');
		if (!el || typeof FullCalendar === 'undefined') return;
		var cal = new FullCalendar.Calendar(el, {
			initialView: 'dayGridMonth',
			events: function (info, successCallback) {
				$.post(ajaxurl, {
					action: 'sp_crm_get_calendar',
					nonce:  SP.nonces.crm,
					start:  info.startStr,
					end:    info.endStr,
				}, function (res) {
					if (res.success && res.data) successCallback(res.data);
				});
			},
		});
		cal.render();
	}

	/* ─── Portals page ────────────────────────────────────────────────── */
	if ($('#sp-portals-list').length) loadPortals();

	function loadPortals() {
		$.post(ajaxurl, { action: 'sp_portal_get_all', nonce: SP.nonces.portal }, function (res) {
			if (!res.success) return;
			if (!res.data || !res.data.length) { $('#sp-portals-list').html('<p>No portals configured.</p>'); return; }
			var html = '<table class="widefat"><thead><tr><th>Name</th><th>Format</th><th>Active</th><th></th></tr></thead><tbody>';
			$.each(res.data, function (i, p) {
				html += '<tr><td>' + p.name + '</td><td>' + p.format.toUpperCase() + '</td><td>' + (parseInt(p.active) ? '✓' : '—') + '</td>' +
				        '<td><button class="button sp-edit-portal" data-id="' + p.id + '" data-name="' + p.name + '" data-slug="' + p.slug + '" data-format="' + p.format + '" data-active="' + p.active + '">Edit</button> ' +
				        '<button class="button sp-delete-portal" data-id="' + p.id + '">Delete</button></td></tr>';
			});
			html += '</tbody></table>';
			$('#sp-portals-list').html(html);
		});
	}
	$(document).on('click', '#sp-add-portal-btn', function () {
		$('#sp-portal-id,#sp-portal-name,#sp-portal-slug').val('');
		$('#sp-portal-active').prop('checked', true);
		$('#sp-portal-format').val('blm');
		$('#sp-portal-modal').removeAttr('hidden');
	});
	$(document).on('click', '.sp-edit-portal', function () {
		var $b = $(this);
		$('#sp-portal-id').val($b.data('id'));
		$('#sp-portal-name').val($b.data('name'));
		$('#sp-portal-slug').val($b.data('slug'));
		$('#sp-portal-format').val($b.data('format'));
		$('#sp-portal-active').prop('checked', !!parseInt($b.data('active')));
		$('#sp-portal-modal').removeAttr('hidden');
	});
	$(document).on('click', '#sp-save-portal-btn', function () {
		$.post(ajaxurl, {
			action:  'sp_portal_save',
			nonce:   SP.nonces.portal,
			id:      $('#sp-portal-id').val(),
			name:    $('#sp-portal-name').val(),
			slug:    $('#sp-portal-slug').val(),
			format:  $('#sp-portal-format').val(),
			active:  $('#sp-portal-active').is(':checked') ? 1 : 0,
		}, function (res) {
			if (res.success) { $('#sp-portal-modal').attr('hidden', true); loadPortals(); }
			else alert(res.data && res.data.message ? res.data.message : 'Error.');
		});
	});
	$(document).on('click', '.sp-delete-portal', function () {
		if (!confirm('Delete this portal?')) return;
		$.post(ajaxurl, { action: 'sp_portal_delete', nonce: SP.nonces.portal, id: $(this).data('id') }, function (res) {
			if (res.success) loadPortals();
		});
	});

	/* ─── Export page ─────────────────────────────────────────────────── */
	$(document).on('click', '#sp-export-btn', function () {
		var scope = $('[name="sp-export-scope"]:checked').val() || 'all';
		var ids   = scope === 'ids' ? $('#sp-export-ids').val() : '';
		$.post(ajaxurl, {
			action:  'sp_export_listings',
			nonce:   SP.nonces.export,
			format:  $('#sp-export-format').val(),
			scope:   scope,
			ids:     ids,
		}, function (res) {
			if (res.success && res.data) {
				$('#sp-export-content').val(res.data.content || '');
				$('#sp-export-output').show();
			}
		});
	});
	$(document).on('click', '#sp-export-download-btn', function () {
		var content = $('#sp-export-content').val();
		var format  = $('#sp-export-format').val();
		var ext = { blm: 'blm', xml: 'xml', json: 'json', csv: 'csv' };
		var blob = new Blob([content], { type: 'text/plain' });
		var a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = 'listings.' + (ext[format] || 'txt');
		a.click();
	});

	$(document).on('click', '.sp-portal-export-btn', function () {
		var id = $(this).data('portal-id');
		$.post(ajaxurl, { action: 'sp_portal_export_now', nonce: SP.nonces.portal, portal_id: id }, function (res) {
			alert(res.success ? 'Export queued.' : (res.data && res.data.message ? res.data.message : 'Error.'));
		});
	});

	/* ─── Import page ─────────────────────────────────────────────────── */
	$(document).on('click', '#sp-import-preview-btn', function () {
		var data = buildImportData();
		data.action = 'sp_import_preview';
		$.post(ajaxurl, data, function (res) {
			var $el = $('#sp-import-preview').show();
			if (res.success && res.data) $el.html('<pre>' + JSON.stringify(res.data, null, 2) + '</pre>');
			else $el.html('<p style="color:red;">' + (res.data && res.data.message ? res.data.message : 'Error') + '</p>');
		});
	});
	$(document).on('click', '#sp-import-run-btn', function () {
		var data = buildImportData();
		data.action = 'sp_import_listings';
		$('#sp-import-result').show().html('<p>Importing…</p>');
		$.post(ajaxurl, data, function (res) {
			var $el = $('#sp-import-result');
			if (res.success && res.data) $el.html('<p>Imported: ' + (res.data.imported || 0) + ' | Updated: ' + (res.data.updated || 0) + ' | Errors: ' + (res.data.errors || 0) + '</p>');
			else $el.html('<p style="color:red;">' + (res.data && res.data.message ? res.data.message : 'Error') + '</p>');
		});
	});
	function buildImportData() {
		return {
			nonce:   SP.nonces.import,
			adapter: $('#sp-import-adapter').val(),
			content: $('#sp-import-content').val(),
			update:  $('#sp-import-update').is(':checked') ? 1 : 0,
		};
	}

	/* File reader for import upload */
	$(document).on('change', '#sp-import-file', function () {
		var file = this.files[0];
		if (!file) return;
		var reader = new FileReader();
		reader.onload = function (e) { $('#sp-import-content').val(e.target.result); };
		reader.readAsText(file);
	});

	/* ─── Import tab switching ────────────────────────────────────────── */
	$(document).on('click', '[data-import-tab]', function () {
		var tab = $(this).data('import-tab');
		$('[data-import-tab]').removeClass('active');
		$(this).addClass('active');
		$('.sp-import-section').attr('hidden', true);
		$('#sp-import-tab-' + tab).removeAttr('hidden');
	});

	/* ─── Utilities ───────────────────────────────────────────────────── */
	function renderTable(headers, rows, keys) {
		if (!rows || !rows.length) return '<p>No records.</p>';
		var html = '<table class="widefat"><thead><tr>' + headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '</tr></thead><tbody>';
		$.each(rows, function (i, row) {
			html += '<tr>' + keys.map(function (k) { return '<td>' + (row[k] || '') + '</td>'; }).join('') + '</tr>';
		});
		return html + '</tbody></table>';
	}

	function renderTableWithActions(headers, rows, keys, type) {
		if (!rows || !rows.length) return '<p>No records.</p>';
		var html = '<table class="widefat"><thead><tr>' + headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '<th></th></tr></thead><tbody>';
		$.each(rows, function (i, row) {
			html += '<tr data-id="' + row.id + '" data-first="' + (row.first_name || '') + '" data-last="' + (row.last_name || '') + '" data-email="' + (row.email || '') + '" data-phone="' + (row.phone || '') + '">' +
			        keys.map(function (k) { return '<td>' + (row[k] || '') + '</td>'; }).join('') +
			        '<td><button class="button sp-edit-' + type + '" data-id="' + row.id + '">Edit</button></td></tr>';
		});
		return html + '</tbody></table>';
	}

})(jQuery);
