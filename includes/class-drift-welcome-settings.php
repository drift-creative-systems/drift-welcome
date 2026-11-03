<?php
/**
 * The dashboard settings: welcome panel, quick links and colours, all in one
 * option (self::OPTION). The White Label tab has its own option, see
 * class-drift-welcome-white-label.php.
 *
 * Analytics and Support differ per site, so each is entered once and its
 * card only appears once a URL is set. The Team card's post type slug is
 * configurable, since themes name it differently (see
 * class-drift-welcome-dashboard.php). Custom cards are a free-form repeater
 * for anything else a site wants on its dashboard.
 *
 * @package Drift_Welcome
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings storage and sanitising.
 */
final class Drift_Welcome_Settings {

	const OPTION = 'drift_welcome_settings';

	const DEFAULT_TEAM_CPT_SLUG = 'team';

	const DEFAULT_CARD_ICON = 'dashicons-admin-links';

	const DEFAULT_LOGO_WIDTH = 200;

	const MIN_LOGO_WIDTH = 40;

	const MAX_LOGO_WIDTH = 600;

	/**
	 * Which option keys each settings tab owns. The settings screen has one
	 * form per tab, so a save only touches the keys of the tab that was
	 * submitted. Every other key keeps its saved value.
	 */
	const SECTIONS = array(
		'welcome'     => array( 'logo_id', 'logo_width', 'welcome_heading', 'welcome_text' ),
		'quick-links' => array( 'analytics_url', 'support_url', 'team_cpt_slug', 'custom_cards' ),
		'colours'     => array( 'welcome_bg_color', 'welcome_text_color', 'icon_color', 'text_color', 'hover_bg_color', 'hover_text_color' ),
	);

	/**
	 * Every setting with its default. A blank heading means "Welcome back,
	 * {name}"; blank colours mean the built-in ones in dashboard.css.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'analytics_url'      => '',
			'support_url'        => '',
			'team_cpt_slug'      => self::DEFAULT_TEAM_CPT_SLUG,
			'logo_id'            => 0,
			'logo_width'         => self::DEFAULT_LOGO_WIDTH,
			'welcome_heading'    => '',
			'welcome_text'       => '<p>' . __( 'Use the links below to get straight to the parts of the site you edit most.', 'drift-welcome' ) . '</p>',
			'custom_cards'       => array(),
			'welcome_bg_color'   => '',
			'welcome_text_color' => '',
			'icon_color'         => '',
			'text_color'         => '',
			'hover_bg_color'     => '',
			'hover_text_color'   => '',
		);
	}

	/**
	 * Saved settings over the defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Sanitises and saves one tab's fields (self::SECTIONS), leaving every
	 * other key as it was.
	 *
	 * `custom_cards` always resolves against `$input` rather than falling back
	 * to the saved value: the Quick links tab posts the whole form, so a
	 * missing key means every card row was removed before saving.
	 *
	 * @param array  $input   Raw $_POST['settings'], already unslashed.
	 * @param string $section A self::SECTIONS key. Unknown sections save nothing.
	 * @return void
	 */
	public static function save( array $input, $section ) {
		if ( ! isset( self::SECTIONS[ $section ] ) ) {
			return;
		}

		$settings = self::get();

		foreach ( self::SECTIONS[ $section ] as $key ) {
			if ( 'custom_cards' === $key ) {
				$settings[ $key ] = self::sanitize_custom_cards( isset( $input['custom_cards'] ) ? $input['custom_cards'] : array() );
				continue;
			}
			$settings[ $key ] = self::sanitize_field( $key, isset( $input[ $key ] ) ? $input[ $key ] : $settings[ $key ] );
		}

		update_option( self::OPTION, $settings, false );
	}

	/**
	 * Sanitises one scalar field. `welcome_text` is the one rich field (edited
	 * with wp_editor()), so it goes through wp_kses_post(), here and again on
	 * output.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	private static function sanitize_field( $key, $value ) {
		switch ( $key ) {
			case 'analytics_url':
			case 'support_url':
				return esc_url_raw( (string) $value );
			case 'team_cpt_slug':
				$slug = sanitize_key( (string) $value );
				return '' !== $slug ? $slug : self::DEFAULT_TEAM_CPT_SLUG;
			case 'logo_id':
				return self::sanitize_image_id( $value );
			case 'logo_width':
				return self::sanitize_logo_width( $value );
			case 'welcome_heading':
				return sanitize_text_field( (string) $value );
			case 'welcome_text':
				return wp_kses_post( (string) $value );
			default:
				// Every remaining key is a colour (self::SECTIONS['colours']).
				return (string) sanitize_hex_color( (string) $value );
		}
	}

	/**
	 * Images are stored as attachment IDs rather than URLs, so they survive a
	 * staging to live domain change. Anything that isn't an existing image
	 * attachment becomes 0 ("no image"). The White Label tab uses this too.
	 *
	 * @param mixed $value Raw attachment ID from the media picker.
	 * @return int
	 */
	public static function sanitize_image_id( $value ) {
		$id = absint( $value );
		return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}

	/**
	 * Logo width in px, clamped so a typo can't blow the panel out.
	 *
	 * @param mixed $value Raw number.
	 * @return int
	 */
	private static function sanitize_logo_width( $value ) {
		$width = absint( $value );
		if ( ! $width ) {
			return self::DEFAULT_LOGO_WIDTH;
		}
		return max( self::MIN_LOGO_WIDTH, min( self::MAX_LOGO_WIDTH, $width ) );
	}

	/**
	 * Custom cards repeater. Rows missing a label or URL are dropped here, so
	 * the option never holds a dead card.
	 *
	 * @param mixed $raw $_POST['settings']['custom_cards'], already unslashed.
	 * @return array[] Each: label, url, icon (Dashicons class), external (bool).
	 */
	private static function sanitize_custom_cards( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$cards = array();

		foreach ( $raw as $card ) {
			if ( ! is_array( $card ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( isset( $card['label'] ) ? $card['label'] : '' ) );
			$url   = esc_url_raw( (string) ( isset( $card['url'] ) ? $card['url'] : '' ) );

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$icon = sanitize_html_class( (string) ( isset( $card['icon'] ) ? $card['icon'] : '' ) );

			$cards[] = array(
				'label'    => $label,
				'url'      => $url,
				'icon'     => '' !== $icon ? $icon : self::DEFAULT_CARD_ICON,
				'external' => ! empty( $card['external'] ),
			);
		}

		return $cards;
	}
}
