<?php
/**
 * Plugin Name: WWNTBM Donations
 * Plugin URI: https://github.com/macbookandrew/wwntbm-donations
 * Description: WWNTBM Tithe.ly integration
 * Version: 1.1.0
 * Author: AndrewRMinion Design
 * Author URI: https://andrewrminion.com
 *
 * @package WWNTBM_Donations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WWNTBM_DONATIONS_VERSION = '1.1.0';

global $give_url;
$give_url = 'https://tithe.ly/give_new/www/#/tithely/give-one-time/17262';

/**
 * Register JS and CSS assets
 */
function wwntbm_load_chosen() {
	wp_register_style( 'chosen', plugins_url( 'assets/css/chosen.min.css', __FILE__ ), array(), '1.6.2', 'screen' );
	wp_register_script( 'chosen', plugins_url( 'assets/js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), '1.6.2', true );

	wp_register_script( 'wwntbm-donations', plugins_url( 'assets/js/wwntbm-donations.js', __FILE__ ), array( 'jquery', 'chosen' ), WWNTBM_DONATIONS_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'wwntbm_load_chosen' );

/**
 * Shortcode for donations list
 *
 * @param  array $attributes Array of attributes.
 * @return string HTML content string
 */
function wwntbm_tithely_donations_list( $attributes ) {
	$shortcode_attributes = shortcode_atts(
		array(), $attributes
	);
	$shortcode_content    = '';

	wp_enqueue_style( 'chosen' );
	wp_enqueue_script( 'chosen' );
	wp_enqueue_script( 'wwntbm-donations' );

	$missionary_args = array(
		'post_type' => 'wwntbm_missionaries',
		'order'     => 'ASC',
		'orderby'   => 'meta_value',
		'meta_key'  => 'missionary_key',
	);

	$missionary_query = new WP_Query( $missionary_args );

	if ( $missionary_query->have_posts() ) {
		$shortcode_content .= '<form class="donations" method="get" action="https://tithe.ly/give_new/www/#/tithely/give-one-time/17262">
		<select name="giving_to">
		<option value="General Office Fund">General Office Fund</option>
		<option value="Europe Conference">Encouragement Conference</option>
		<option value="See the Harvest">See the Harvest</option>
		';

		while ( $missionary_query->have_posts() ) {
			$missionary_query->the_post();
			$shortcode_content .= '<option value="' . get_the_title() . '">' . esc_attr( get_the_title() ) . '</option>';
		}

		$shortcode_content .= '</select>
		<input type="submit" class="tithely-give-btn" value="Give" />
		</form>';
	}

	wp_reset_postdata();

	return $shortcode_content;
}
add_shortcode( 'tithely_donations_list', 'wwntbm_tithely_donations_list' );

/**
 * Add customized donation button
 *
 * @param  array $attributes Array of attributes.
 * @return string HTML content string
 */
function wwntbm_custom_donation_button( $attributes ) {
	$shortcode_attributes = shortcode_atts(
		array(
			'giving_to' => get_the_title(),
		), $attributes
	);
	$shortcode_content    = '';
	global $give_url;

	wp_enqueue_style( 'chosen' );
	wp_enqueue_script( 'chosen' );

	$shortcode_content .= '<a href="' . esc_url( $give_url . '?giving_to=' . rawurlencode( $shortcode_attributes['giving_to'] ) ) . '" class="tithely-give-btn">Give to ' . esc_attr( $shortcode_attributes['giving_to'] ) . '</a>';

	$shortcode_content .= '<p class="donation-meta">Please note that our credit card processing company does take a small fee out of each donation, and your tax-deductible receipt will show a slightly lower amount than the one given. You may choose to cover that fee (above your donation amount) by selecting the prompt on the following screen. For alternative giving options, please see <a href="' . esc_url( home_url( 'give' ) ) . '">wwntbm.com/give</a>.</p>';

	return $shortcode_content;
}
add_shortcode( 'donation_button', 'wwntbm_custom_donation_button' );

/**
 * Add a custom donation button to each missionary
 *
 * @param  string $content HTML content.
 * @return string modified HTML content
 */
function wwntbm_missionary_donation_button( $content ) {
	if ( get_post_type() === 'wwntbm_missionaries' ) {
		$custom_tithely = get_field( 'custom_tithely' );
		if ( ! empty( $custom_tithely ) ) {
			$content .= wp_kses_post( do_shortcode( $custom_tithely ) );
		} else {
			$content .= do_shortcode( '<p>[donation_button]</p>' );
		}
	}
	return $content;
}
add_filter( 'the_content', 'wwntbm_missionary_donation_button' );
