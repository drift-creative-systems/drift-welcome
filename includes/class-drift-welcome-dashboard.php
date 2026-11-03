<?php
/**
 * Replaces wp-admin's default dashboard with a single panel: a welcome
 * message plus a grid of quick links.
 *
 * Every default dashboard widget (At a Glance, Activity, Quick Draft,
 * WordPress Events and News, Site Health Status), and anything another plugin
 * has added to the dashboard, is removed rather than hidden, so this panel is
 * the only thing on the page. WordPress's own dismissible welcome panel is
 * removed the same way.
 *
 * @package Drift_Welcome
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard widget.
 */
final class Drift_Welcome_Dashboard {

	const WIDGET_ID = 'drift_welcome_panel';

	/**
	 * Admin page slug of the theme's options page (the ACF Options Page
	 * convention). Its card only shows when the page is in the user's menu.
	 */
	const THEME_SETUP_SLUG = 'theme-general-settings';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		// Priority 10: register our widget. Priority 999: remove every other
		// dashboard widget registered by then. Our widget must already exist
		// when the sweep runs, or it would remove itself too.
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_widget' ), 10 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'strip_other_widgets' ), 999 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Register the panel.
	 *
	 * @return void
	 */
	public static function add_widget() {
		wp_add_dashboard_widget(
			self::WIDGET_ID,
			__( 'Welcome', 'drift-welcome' ),
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Removes every dashboard widget except ours: explicit remove_meta_box()
	 * calls for the core widgets, then a sweep of $wp_meta_boxes for anything
	 * another plugin (Jetpack, Yoast, WooCommerce…) has added.
	 *
	 * @return void
	 */
	public static function strip_other_widgets() {
		global $wp_meta_boxes;

		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );        // WordPress Events and News.
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );      // Other WordPress News (older core).
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );    // At a Glance.
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );     // Activity.
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );    // Quick Draft.
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );  // Older core.
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );  // Site Health Status.

		if ( empty( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
			return;
		}

		foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
			if ( ! is_array( $priorities ) ) {
				continue;
			}
			foreach ( $priorities as $priority => $widgets ) {
				if ( ! is_array( $widgets ) ) {
					continue;
				}
				foreach ( array_keys( $widgets ) as $widget_id ) {
					if ( self::WIDGET_ID !== $widget_id ) {
						unset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $widget_id ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Removing widgets is the point.
					}
				}
			}
		}
	}

	/**
	 * Panel styles and the colour overrides, on the dashboard only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function assets( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'drift-welcome-dashboard', DRIFT_WELCOME_URL . 'assets/dashboard.css', array(), DRIFT_WELCOME_VERSION );

		$css = self::override_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'drift-welcome-dashboard', $css );
		}
	}

	/**
	 * The Colours tab and logo width as custom properties over the defaults
	 * in dashboard.css. Every value is a sanitised hex colour or an integer.
	 *
	 * @return string CSS, or '' when nothing is set.
	 */
	private static function override_css() {
		$settings  = Drift_Welcome_Settings::get();
		$overrides = array();
		$map       = array(
			'welcome_bg_color'   => '--drift-welcome-panel-bg',
			'welcome_text_color' => '--drift-welcome-panel-text',
			'icon_color'         => '--drift-welcome-icon',
			'text_color'         => '--drift-welcome-text',
			'hover_bg_color'     => '--drift-welcome-hover-bg',
			'hover_text_color'   => '--drift-welcome-hover-text',
		);

		foreach ( $map as $key => $property ) {
			$colour = sanitize_hex_color( (string) $settings[ $key ] );
			if ( $colour ) {
				$overrides[] = $property . ':' . $colour;
			}
		}

		if ( ! empty( $settings['logo_id'] ) ) {
			$overrides[] = '--drift-welcome-logo-width:' . absint( $settings['logo_width'] ) . 'px';
		}

		return $overrides ? '.drift-welcome-panel{' . implode( ';', $overrides ) . '}' : '';
	}

	/**
	 * Print the panel.
	 *
	 * @return void
	 */
	public static function render() {
		$settings = Drift_Welcome_Settings::get();
		$user     = wp_get_current_user();

		$heading = '' !== $settings['welcome_heading']
			? $settings['welcome_heading']
			/* translators: %s: user's display name. */
			: sprintf( __( 'Welcome back, %s', 'drift-welcome' ), $user->display_name );

		$links = self::get_quick_links( $settings );
		$logo  = self::get_logo_markup( $settings );
		?>
		<div class="drift-welcome-panel">
			<div class="drift-welcome-panel__welcome">
				<?php if ( $logo ) : ?>
					<div class="drift-welcome-panel__logo">
						<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by wp_get_attachment_image(), which escapes its own attributes. ?>
					</div>
				<?php endif; ?>
				<h2 class="drift-welcome-panel__heading"><?php echo esc_html( $heading ); ?></h2>
				<?php if ( $settings['welcome_text'] ) : ?>
					<div class="drift-welcome-panel__text">
						<?php echo wp_kses_post( wpautop( $settings['welcome_text'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $links ) ) : ?>
				<div class="drift-welcome-panel__grid">
					<?php foreach ( $links as $link ) : ?>
						<a class="drift-welcome-panel__card" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></span>
							<span class="drift-welcome-panel__card-label"><?php echo esc_html( $link['label'] ); ?></span>
							<?php if ( ! empty( $link['sub'] ) ) : ?>
								<span class="drift-welcome-panel__card-sub"><?php echo esc_html( $link['sub'] ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The logo <img>, or '' when none is set or the attachment has gone.
	 * Uses the attachment's alt text, falling back to the site name.
	 *
	 * @param array $settings Drift_Welcome_Settings::get().
	 * @return string
	 */
	private static function get_logo_markup( array $settings ) {
		$logo_id = absint( $settings['logo_id'] );
		if ( ! $logo_id ) {
			return '';
		}

		$alt = trim( (string) get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) );

		return (string) wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'   => 'drift-welcome-panel__logo-img',
				'alt'     => '' !== $alt ? $alt : get_bloginfo( 'name' ),
				'sizes'   => absint( $settings['logo_width'] ) . 'px',
				'loading' => 'eager',
			)
		);
	}

	/**
	 * URL of an admin page, only if it's in the current user's menu. The menu
	 * is already built (and trimmed to the user's capabilities) by the time
	 * the dashboard renders.
	 *
	 * @param string $slug Menu slug.
	 * @return string URL, or ''.
	 */
	private static function menu_url( $slug ) {
		global $menu, $submenu;

		$found = false;
		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && $slug === $item[2] ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			foreach ( (array) $submenu as $items ) {
				foreach ( (array) $items as $item ) {
					if ( isset( $item[2] ) && $slug === $item[2] ) {
						$found = true;
						break 2;
					}
				}
			}
		}

		return $found ? (string) menu_page_url( $slug, false ) : '';
	}

	/**
	 * Every card, in order: Pages, Posts, Theme Setup, Team, Analytics,
	 * custom cards, Support. Each only shows when it leads somewhere the user
	 * can go: Pages and Posts need the capability, Theme Setup needs the page
	 * in the user's menu, Team needs its post type, Analytics and Support
	 * need a URL.
	 *
	 * @param array $settings Drift_Welcome_Settings::get().
	 * @return array[] Each: label, url, icon (Dashicons class), optional
	 *                 external (bool) and sub (a second line of text).
	 */
	private static function get_quick_links( array $settings ) {
		$links = array();

		if ( current_user_can( 'edit_pages' ) ) {
			$links[] = array(
				'label' => __( 'Pages', 'drift-welcome' ),
				'url'   => admin_url( 'edit.php?post_type=page' ),
				'icon'  => 'dashicons-admin-page',
			);
		}

		if ( current_user_can( 'edit_posts' ) ) {
			$links[] = array(
				'label' => __( 'Posts', 'drift-welcome' ),
				'url'   => admin_url( 'edit.php' ),
				'icon'  => 'dashicons-admin-post',
			);
		}

		$theme_setup = self::menu_url( self::THEME_SETUP_SLUG );
		if ( '' !== $theme_setup ) {
			$links[] = array(
				'label' => __( 'Theme Setup', 'drift-welcome' ),
				'url'   => $theme_setup,
				'icon'  => 'dashicons-admin-tools',
			);
		}

		$team = get_post_type_object( $settings['team_cpt_slug'] ? $settings['team_cpt_slug'] : Drift_Welcome_Settings::DEFAULT_TEAM_CPT_SLUG );
		if ( $team && current_user_can( $team->cap->edit_posts ) ) {
			$links[] = array(
				'label' => __( 'Team', 'drift-welcome' ),
				'url'   => admin_url( 'edit.php?post_type=' . $team->name ),
				'icon'  => 'dashicons-groups',
			);
		}

		if ( $settings['analytics_url'] ) {
			$links[] = array(
				'label'    => __( 'Analytics', 'drift-welcome' ),
				'url'      => $settings['analytics_url'],
				'icon'     => 'dashicons-chart-bar',
				'external' => true,
			);
		}

		foreach ( $settings['custom_cards'] as $card ) {
			$links[] = array(
				'label'    => $card['label'],
				'url'      => $card['url'],
				'icon'     => $card['icon'] ? $card['icon'] : Drift_Welcome_Settings::DEFAULT_CARD_ICON,
				'external' => ! empty( $card['external'] ),
			);
		}

		if ( $settings['support_url'] ) {
			$links[] = array(
				'label'    => __( 'Support', 'drift-welcome' ),
				'url'      => $settings['support_url'],
				'icon'     => 'dashicons-sos',
				'external' => true,
			);
		}

		/**
		 * Filters the dashboard's quick-link cards.
		 *
		 * @param array[] $links    Each: label, url, icon (Dashicons class),
		 *                          optional external (bool) and sub (string).
		 * @param array   $settings The plugin's settings.
		 */
		$links = apply_filters( 'drift_welcome_quick_links', $links, $settings );

		return is_array( $links ) ? array_values(
			array_filter(
				$links,
				static function ( $link ) {
					return is_array( $link ) && ! empty( $link['label'] ) && ! empty( $link['url'] ) && ! empty( $link['icon'] );
				}
			)
		) : array();
	}
}
