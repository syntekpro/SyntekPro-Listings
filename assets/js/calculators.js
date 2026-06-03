/**
 * SyntekPro Listings — Calculators JavaScript
 * Handles real-time calculation for mortgage, stamp duty, rental yield, affordability.
 */
/* global syntekproFrontend, jQuery */
(function ($) {
	'use strict';

	var SP     = syntekproFrontend || {};
	var ajaxUrl = SP.ajaxUrl;

	/* ── Helper: post to AJAX and show result ─────────────────────────── */
	function doCalc(action, data, $resultEl) {
		data.action = action;
		data.nonce  = SP.nonces.calc;
		$.post(ajaxUrl, data, function (res) {
			if (res.success && res.data) {
				$resultEl.html(res.data.html || JSON.stringify(res.data)).addClass('active');
			} else {
				$resultEl.html('<span style="color:#dc3545;">' + (res.data && res.data.message ? res.data.message : 'Error') + '</span>').addClass('active');
			}
		}).fail(function () {
			$resultEl.html('<span style="color:#dc3545;">Request failed.</span>').addClass('active');
		});
	}

	/* ── Mortgage ─────────────────────────────────────────────────────── */
	$(document).on('click', '.sp-mortgage-calc-btn', function () {
		var $c = $(this).closest('.sp-mortgage-calc');
		doCalc('sp_calc_mortgage', {
			price:   $c.find('#sp-mc-price').val(),
			deposit: $c.find('#sp-mc-deposit').val(),
			rate:    $c.find('#sp-mc-rate').val(),
			term:    $c.find('#sp-mc-term').val(),
			type:    $c.find('#sp-mc-type').val(),
		}, $c.find('.sp-mortgage-result'));
	});

	/* ── Stamp Duty ───────────────────────────────────────────────────── */
	$(document).on('click', '.sp-stamp-duty-calc-btn', function () {
		var $c = $(this).closest('.sp-stamp-duty-calc');
		doCalc('sp_calc_stamp_duty', {
			price:      $c.find('#sp-sd-price').val(),
			country:    $c.find('#sp-sd-country').val(),
			first_time: $c.find('#sp-sd-ftb').is(':checked') ? '1' : '0',
			additional: $c.find('#sp-sd-additional').is(':checked') ? '1' : '0',
		}, $c.find('.sp-stamp-duty-result'));
	});

	/* ── Rental Yield ─────────────────────────────────────────────────── */
	$(document).on('click', '.sp-yield-calc-btn', function () {
		var $c = $(this).closest('.sp-yield-calc');
		doCalc('sp_calc_rental_yield', {
			property_value: $c.find('#sp-ry-price').val(),
			monthly_rent:   $c.find('#sp-ry-rent').val(),
			annual_costs:   $c.find('#sp-ry-costs').val(),
		}, $c.find('.sp-yield-result'));
	});

	/* ── Affordability ────────────────────────────────────────────────── */
	$(document).on('click', '.sp-afford-calc-btn', function () {
		var $c = $(this).closest('.sp-afford-calc');
		doCalc('sp_calc_rental_affordability', {
			annual_income: $c.find('#sp-ra-income').val(),
			ratio:         $c.find('#sp-ra-ratio').val(),
		}, $c.find('.sp-afford-result'));
	});

})(jQuery);
