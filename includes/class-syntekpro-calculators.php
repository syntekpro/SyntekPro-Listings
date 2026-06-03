<?php
/**
 * All financial calculators: Mortgage, Stamp Duty (UK/international),
 * Rental Yield, and Rental Affordability.
 *
 * @package SyntekPro_Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyntekPro_Calculators
 */
class SyntekPro_Calculators {

	public function __construct() {
		add_action( 'wp_ajax_sp_calc_mortgage',            array( $this, 'ajax_mortgage' ) );
		add_action( 'wp_ajax_nopriv_sp_calc_mortgage',     array( $this, 'ajax_mortgage' ) );
		add_action( 'wp_ajax_sp_calc_stamp_duty',          array( $this, 'ajax_stamp_duty' ) );
		add_action( 'wp_ajax_nopriv_sp_calc_stamp_duty',   array( $this, 'ajax_stamp_duty' ) );
		add_action( 'wp_ajax_sp_calc_rental_yield',        array( $this, 'ajax_rental_yield' ) );
		add_action( 'wp_ajax_nopriv_sp_calc_rental_yield', array( $this, 'ajax_rental_yield' ) );
		add_action( 'wp_ajax_sp_calc_affordability',          array( $this, 'ajax_affordability' ) );
		add_action( 'wp_ajax_nopriv_sp_calc_affordability',   array( $this, 'ajax_affordability' ) );
	}

	// ─── AJAX Handlers ───────────────────────────────────────────────────────

	public function ajax_mortgage() {
		check_ajax_referer( 'syntekpro_calc_nonce', 'nonce' );

		$price     = floatval( $_POST['price']     ?? 0 );
		$deposit   = floatval( $_POST['deposit']   ?? 0 );
		$rate      = floatval( $_POST['rate']      ?? 3.5 );
		$term      = absint( $_POST['term']        ?? 25 );
		$type      = sanitize_text_field( $_POST['type'] ?? 'repayment' ); // repayment|interest_only

		if ( $price <= 0 ) {
			wp_send_json_error( __( 'Invalid property price.', 'syntekpro-listings' ) );
			return;
		}

		$result = $this->calculate_mortgage( $price, $deposit, $rate, $term, $type );
		wp_send_json_success( $result );
	}

	public function ajax_stamp_duty() {
		check_ajax_referer( 'syntekpro_calc_nonce', 'nonce' );

		$price        = floatval( $_POST['price']         ?? 0 );
		$country      = sanitize_text_field( $_POST['country']      ?? 'england' );
		$buyer_type   = sanitize_text_field( $_POST['buyer_type']   ?? 'standard' ); // standard|first_time|additional
		$property_use = sanitize_text_field( $_POST['property_use'] ?? 'residential' ); // residential|non_residential

		if ( $price <= 0 ) {
			wp_send_json_error( __( 'Invalid property price.', 'syntekpro-listings' ) );
			return;
		}

		$result = $this->calculate_stamp_duty( $price, $country, $buyer_type, $property_use );
		wp_send_json_success( $result );
	}

	public function ajax_rental_yield() {
		check_ajax_referer( 'syntekpro_calc_nonce', 'nonce' );

		$price          = floatval( $_POST['price']          ?? 0 );
		$monthly_rent   = floatval( $_POST['monthly_rent']   ?? 0 );
		$annual_costs   = floatval( $_POST['annual_costs']   ?? 0 );

		if ( $price <= 0 || $monthly_rent <= 0 ) {
			wp_send_json_error( __( 'Invalid values.', 'syntekpro-listings' ) );
			return;
		}

		$result = $this->calculate_rental_yield( $price, $monthly_rent, $annual_costs );
		wp_send_json_success( $result );
	}

	public function ajax_affordability() {
		check_ajax_referer( 'syntekpro_calc_nonce', 'nonce' );

		$annual_income = floatval( $_POST['annual_income']   ?? 0 );
		$monthly_costs = floatval( $_POST['monthly_costs']   ?? 0 );
		$rent_type     = sanitize_text_field( $_POST['rent_type'] ?? 'monthly' ); // weekly|monthly

		if ( $annual_income <= 0 ) {
			wp_send_json_error( __( 'Invalid income.', 'syntekpro-listings' ) );
			return;
		}

		$result = $this->calculate_affordability( $annual_income, $monthly_costs );
		wp_send_json_success( $result );
	}

	// ─── Mortgage Calculator ─────────────────────────────────────────────────

	/**
	 * Calculate monthly mortgage repayment.
	 *
	 * @param float  $price     Property price.
	 * @param float  $deposit   Deposit amount.
	 * @param float  $rate      Annual interest rate (%).
	 * @param int    $term      Mortgage term (years).
	 * @param string $type      'repayment' or 'interest_only'.
	 * @return array
	 */
	public function calculate_mortgage( $price, $deposit, $rate, $term, $type = 'repayment' ) {
		$loan      = $price - $deposit;
		$ltv       = $price > 0 ? ( $loan / $price ) * 100 : 0;
		$monthly_r = ( $rate / 100 ) / 12;
		$months    = $term * 12;

		if ( 'interest_only' === $type ) {
			$monthly_payment = $loan * $monthly_r;
			$total_paid      = ( $monthly_payment * $months ) + $loan;
			$total_interest  = $total_paid - $loan;
		} else {
			if ( $monthly_r > 0 ) {
				$monthly_payment = $loan * ( $monthly_r * pow( 1 + $monthly_r, $months ) ) / ( pow( 1 + $monthly_r, $months ) - 1 );
			} else {
				$monthly_payment = $months > 0 ? $loan / $months : 0;
			}
			$total_paid     = $monthly_payment * $months;
			$total_interest = $total_paid - $loan;
		}

		return array(
			'loan'             => round( $loan, 2 ),
			'ltv'              => round( $ltv, 2 ),
			'monthly_payment'  => round( $monthly_payment, 2 ),
			'total_paid'       => round( $total_paid, 2 ),
			'total_interest'   => round( $total_interest, 2 ),
			'deposit_percent'  => round( $price > 0 ? ( $deposit / $price ) * 100 : 0, 2 ),
			'term_years'       => $term,
			'annual_rate'      => $rate,
			'type'             => $type,
		);
	}

	// ─── Stamp Duty Calculator ────────────────────────────────────────────────

	/**
	 * Calculate Stamp Duty / LBTT / LTT based on country and buyer type.
	 *
	 * Supports England/N.Ireland (SDLT), Scotland (LBTT), Wales (LTT),
	 * and a generic international fallback.
	 *
	 * @param float  $price        Purchase price.
	 * @param string $country      england|scotland|wales|northern_ireland|international
	 * @param string $buyer_type   standard|first_time|additional
	 * @param string $property_use residential|non_residential
	 * @return array
	 */
	public function calculate_stamp_duty( $price, $country, $buyer_type, $property_use ) {
		$duty       = 0;
		$bands_used = array();
		$label      = '';

		switch ( $country ) {
			case 'scotland':
				$label = 'LBTT';
				list( $duty, $bands_used ) = $this->lbtt( $price, $buyer_type, $property_use );
				break;

			case 'wales':
				$label = 'LTT';
				list( $duty, $bands_used ) = $this->ltt( $price, $buyer_type );
				break;

			default: // England & Northern Ireland (SDLT).
				$label = 'SDLT';
				list( $duty, $bands_used ) = $this->sdlt( $price, $buyer_type, $property_use );
		}

		$effective_rate = $price > 0 ? ( $duty / $price ) * 100 : 0;

		return array(
			'duty'            => round( $duty, 2 ),
			'effective_rate'  => round( $effective_rate, 4 ),
			'total_cost'      => round( $price + $duty, 2 ),
			'tax_name'        => $label,
			'country'         => $country,
			'buyer_type'      => $buyer_type,
			'bands'           => $bands_used,
		);
	}

	/** England & Northern Ireland SDLT (2024 rates). */
	private function sdlt( $price, $buyer_type, $property_use ) {
		if ( 'non_residential' === $property_use ) {
			$bands = array(
				array( 'from' => 0,       'to' => 150000,  'rate' => 0 ),
				array( 'from' => 150001,  'to' => 250000,  'rate' => 2 ),
				array( 'from' => 250001,  'to' => PHP_INT_MAX, 'rate' => 5 ),
			);
		} elseif ( 'first_time' === $buyer_type ) {
			// First time buyer relief (as of 2024).
			$bands = array(
				array( 'from' => 0,       'to' => 425000,  'rate' => 0 ),
				array( 'from' => 425001,  'to' => 625000,  'rate' => 5 ),
				array( 'from' => 625001,  'to' => PHP_INT_MAX, 'rate' => 5 ),
			);
			if ( $price > 625000 ) {
				// First-time buyer relief doesn't apply above £625k — fall back to standard.
				$buyer_type = 'standard';
			}
		}

		if ( 'standard' === $buyer_type && 'residential' === $property_use ) {
			$bands = array(
				array( 'from' => 0,       'to' => 250000,  'rate' => 0 ),
				array( 'from' => 250001,  'to' => 925000,  'rate' => 5 ),
				array( 'from' => 925001,  'to' => 1500000, 'rate' => 10 ),
				array( 'from' => 1500001, 'to' => PHP_INT_MAX, 'rate' => 12 ),
			);
		} elseif ( 'additional' === $buyer_type ) {
			$bands = array(
				array( 'from' => 0,       'to' => 250000,  'rate' => 3 ),
				array( 'from' => 250001,  'to' => 925000,  'rate' => 8 ),
				array( 'from' => 925001,  'to' => 1500000, 'rate' => 13 ),
				array( 'from' => 1500001, 'to' => PHP_INT_MAX, 'rate' => 15 ),
			);
		}

		return $this->apply_bands( $price, $bands );
	}

	/** Scotland LBTT (2024 rates). */
	private function lbtt( $price, $buyer_type, $property_use ) {
		if ( 'non_residential' === $property_use ) {
			$bands = array(
				array( 'from' => 0,      'to' => 150000,  'rate' => 0 ),
				array( 'from' => 150001, 'to' => 250000,  'rate' => 1 ),
				array( 'from' => 250001, 'to' => PHP_INT_MAX, 'rate' => 5 ),
			);
		} elseif ( 'first_time' === $buyer_type ) {
			$bands = array(
				array( 'from' => 0,      'to' => 175000,  'rate' => 0 ),
				array( 'from' => 175001, 'to' => 250000,  'rate' => 2 ),
				array( 'from' => 250001, 'to' => 325000,  'rate' => 5 ),
				array( 'from' => 325001, 'to' => 750000,  'rate' => 10 ),
				array( 'from' => 750001, 'to' => PHP_INT_MAX, 'rate' => 12 ),
			);
		} else {
			$bands = array(
				array( 'from' => 0,      'to' => 145000,  'rate' => 0 ),
				array( 'from' => 145001, 'to' => 250000,  'rate' => 2 ),
				array( 'from' => 250001, 'to' => 325000,  'rate' => 5 ),
				array( 'from' => 325001, 'to' => 750000,  'rate' => 10 ),
				array( 'from' => 750001, 'to' => PHP_INT_MAX, 'rate' => 12 ),
			);
		}

		list( $duty, $bands_used ) = $this->apply_bands( $price, $bands );

		// ADS surcharge (3% on additional residential).
		if ( 'additional' === $buyer_type ) {
			$duty        += $price * 0.03;
			$bands_used[] = array( 'label' => 'ADS (3%)', 'amount' => round( $price * 0.03, 2 ) );
		}

		return array( $duty, $bands_used );
	}

	/** Wales LTT (2024 rates). */
	private function ltt( $price, $buyer_type ) {
		$bands = array(
			array( 'from' => 0,      'to' => 225000,  'rate' => 0 ),
			array( 'from' => 225001, 'to' => 400000,  'rate' => 6 ),
			array( 'from' => 400001, 'to' => 750000,  'rate' => 7.5 ),
			array( 'from' => 750001, 'to' => 1500000, 'rate' => 10 ),
			array( 'from' => 1500001,'to' => PHP_INT_MAX, 'rate' => 12 ),
		);

		list( $duty, $bands_used ) = $this->apply_bands( $price, $bands );

		// Higher rates surcharge (4% for additional properties).
		if ( 'additional' === $buyer_type ) {
			$duty        += $price * 0.04;
			$bands_used[] = array( 'label' => 'Higher rate (4%)', 'amount' => round( $price * 0.04, 2 ) );
		}

		return array( $duty, $bands_used );
	}

	/**
	 * Apply tiered band rates to a price.
	 *
	 * @param float $price
	 * @param array $bands  Each band: from, to, rate (%).
	 * @return array [ $total_duty, $bands_used ]
	 */
	private function apply_bands( $price, $bands ) {
		$total      = 0;
		$bands_used = array();

		foreach ( $bands as $band ) {
			if ( $price <= $band['from'] ) {
				break;
			}
			$taxable = min( $price, $band['to'] ) - $band['from'];
			$amount  = $taxable * ( $band['rate'] / 100 );
			$total  += $amount;
			$bands_used[] = array(
				'rate'     => $band['rate'],
				'taxable'  => round( $taxable, 2 ),
				'amount'   => round( $amount, 2 ),
				'from'     => $band['from'],
				'to'       => $band['to'] === PHP_INT_MAX ? 'above' : $band['to'],
			);
		}

		return array( round( $total, 2 ), $bands_used );
	}

	// ─── Rental Yield Calculator ─────────────────────────────────────────────

	/**
	 * @param float $price         Purchase price.
	 * @param float $monthly_rent  Monthly rental income.
	 * @param float $annual_costs  Annual operating costs.
	 * @return array
	 */
	public function calculate_rental_yield( $price, $monthly_rent, $annual_costs = 0 ) {
		$annual_rent    = $monthly_rent * 12;
		$gross_yield    = $price > 0 ? ( $annual_rent / $price ) * 100 : 0;
		$net_income     = $annual_rent - $annual_costs;
		$net_yield      = $price > 0 ? ( $net_income / $price ) * 100 : 0;
		$payback_years  = $net_income > 0 ? $price / $net_income : 0;
		$roi_per_year   = $net_income;

		return array(
			'annual_rent'   => round( $annual_rent, 2 ),
			'gross_yield'   => round( $gross_yield, 4 ),
			'net_income'    => round( $net_income, 2 ),
			'net_yield'     => round( $net_yield, 4 ),
			'payback_years' => $payback_years > 0 ? round( $payback_years, 1 ) : 'N/A',
			'roi_per_year'  => round( $roi_per_year, 2 ),
		);
	}

	// ─── Rental Affordability Calculator ────────────────────────────────────

	/**
	 * Determine maximum affordable rent given an annual income.
	 *
	 * Standard lender rule: max rent ≈ 30–35% of gross income.
	 *
	 * @param float $annual_income   Gross annual income.
	 * @param float $monthly_costs   Monthly outgoings/debts.
	 * @return array
	 */
	public function calculate_affordability( $annual_income, $monthly_costs = 0 ) {
		$monthly_income    = $annual_income / 12;
		$disposable        = $monthly_income - $monthly_costs;

		// 30% rule.
		$max_rent_30       = $monthly_income * 0.30;
		// 35% rule (used by some letting agents).
		$max_rent_35       = $monthly_income * 0.35;
		// Net of other costs.
		$max_rent_net      = max( 0, $disposable * 0.40 );

		// Required income for a given rent input (inverse: income = rent / 0.3).
		$required_income_30 = fn( $rent ) => $rent / 0.30 * 12;

		return array(
			'annual_income'     => round( $annual_income, 2 ),
			'monthly_income'    => round( $monthly_income, 2 ),
			'monthly_costs'     => round( $monthly_costs, 2 ),
			'disposable_income' => round( $disposable, 2 ),
			'max_rent_30_rule'  => round( $max_rent_30, 2 ),
			'max_rent_35_rule'  => round( $max_rent_35, 2 ),
			'max_rent_net'      => round( $max_rent_net, 2 ),
			'max_weekly_rent'   => round( $max_rent_30 / 4.33, 2 ),
		);
	}
}
