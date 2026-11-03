<?php
/**
 * Settings → Drift: Welcome. One tab per page load (?tab=…), each with its
 * own form:
 *   - Welcome     logo, heading and text at the top of the dashboard
 *   - Quick links Analytics/Support URLs, Team post type, custom cards
 *   - Colours     welcome panel and card colour overrides
 *   - White Label admin branding, login screen, admin menus
 *                 (class-drift-welcome-white-label.php and
 *                 class-drift-welcome-admin-access.php). Only listed for
 *                 users who pass Drift_Welcome_Admin_Access::can_manage().
 *
 * The first three save through handle_save(), which only updates the posted
 * tab's keys (Drift_Welcome_Settings::SECTIONS). White Label has its own
 * option and handler.
 *
 * @package Drift_Welcome
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings screen.
 */
final class Drift_Welcome_Admin {

	const SLUG = 'drift-welcome';

	const CAPABILITY = 'manage_options';

	/**
	 * Hook suffix of the settings page, set when the menu is added.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_drift_welcome_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'conflict_notice' ) );
		// After the White Label footer (99), so the company details stay on this screen.
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ), 100 );
		add_filter( 'plugin_action_links_' . plugin_basename( DRIFT_WELCOME_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add Settings → Drift: Welcome.
	 *
	 * @return void
	 */
	public static function add_menu() {
		self::$hook_suffix = (string) add_options_page(
			__( 'Drift: Welcome', 'drift-welcome' ),
			__( 'Drift: Welcome', 'drift-welcome' ),
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * URL of the screen, optionally on a tab.
	 *
	 * @param string $tab   Tab key, or '' for the first tab.
	 * @param array  $extra Extra query args.
	 * @return string
	 */
	public static function url( $tab = '', array $extra = array() ) {
		$args = array( 'page' => self::SLUG );
		if ( '' !== $tab ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'options-general.php' ) );
	}

	/**
	 * Tabs the current user can see, in display order. Each: label, lead
	 * paragraph and render callback.
	 *
	 * @return array<string, array{label: string, description: string, render: callable}>
	 */
	private static function tabs() {
		$tabs = array(
			'welcome'     => array(
				'label'       => __( 'Welcome', 'drift-welcome' ),
				'description' => __( 'The logo, heading and message at the top of the dashboard.', 'drift-welcome' ),
				'render'      => array( __CLASS__, 'render_welcome_tab' ),
			),
			'quick-links' => array(
				'label'       => __( 'Quick links', 'drift-welcome' ),
				'description' => __( 'Pages and Posts always link to this site\'s own admin screens. Everything else is set here.', 'drift-welcome' ),
				'render'      => array( __CLASS__, 'render_quick_links_tab' ),
			),
			'colours'     => array(
				'label'       => __( 'Colours', 'drift-welcome' ),
				'description' => __( 'Optional. Leave any of these blank to use the built-in colours.', 'drift-welcome' ),
				'render'      => array( __CLASS__, 'render_colours_tab' ),
			),
		);

		// Clients shouldn't be able to rebrand the admin or change which menus
		// they can see. See class-drift-welcome-admin-access.php.
		if ( Drift_Welcome_Admin_Access::can_manage() ) {
			$tabs['white-label'] = array(
				'label'       => __( 'White Label', 'drift-welcome' ),
				'description' => __( 'Admin branding, the login screen, and which menus non-agency users can see. Anything left blank keeps WordPress\'s own default.', 'drift-welcome' ),
				'render'      => array( 'Drift_Welcome_White_Label', 'render_tab' ),
			);
		}

		return $tabs;
	}

	/**
	 * Whether the current admin screen is this one.
	 *
	 * @return bool
	 */
	private static function is_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && '' !== self::$hook_suffix && self::$hook_suffix === $screen->id;
	}

	/**
	 * "Settings" link on the plugin's row on the Plugins screen.
	 *
	 * @param array $links Action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		if ( current_user_can( self::CAPABILITY ) ) {
			array_unshift(
				$links,
				'<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'drift-welcome' ) . '</a>'
			);
		}

		return $links;
	}

	/**
	 * Media library, colour pickers and the settings JS/CSS, on this screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function assets( $hook_suffix ) {
		if ( '' === self::$hook_suffix || self::$hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'drift-welcome-admin', DRIFT_WELCOME_URL . 'assets/admin.css', array( 'wp-color-picker' ), DRIFT_WELCOME_VERSION );
		wp_enqueue_script( 'drift-welcome-admin', DRIFT_WELCOME_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), DRIFT_WELCOME_VERSION, true );
	}

	/**
	 * Bonsai Dashboard does the same job. With both active, each one's sweep
	 * removes the other's widget and the dashboard ends up empty.
	 *
	 * @return void
	 */
	public static function conflict_notice() {
		if ( ! defined( 'BONSAI_DASHBOARD_VERSION' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) && ! self::is_screen() ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Drift: Welcome and Bonsai Dashboard are both active. They both replace the dashboard, so neither panel will show. Deactivate one of them.', 'drift-welcome' ) . '</p></div>';
	}

	/**
	 * Copyright and company details in the admin footer, on this screen only.
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( ! self::is_screen() ) {
			return $text;
		}

		return 'Copyright &copy; ' . esc_html( wp_date( 'Y' ) ) . ' Drift Creative Systems Ltd &middot; Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF';
	}

	/**
	 * Drift monogram. Fixed markup; only the fill is variable.
	 *
	 * @param string $fill Fill colour.
	 * @param int    $size Width and height in px.
	 * @return string
	 */
	private static function mark_svg( $fill = 'currentColor', $size = 32 ) {
		$paths = array(
			'M17 12H54C77 12 91 29 91 50C91 72 76 88 54 88H45L55 76H54C70 76 79 64 79 50C79 35 69 24 54 24H29V44H17Z',
			'M17 55H29L17 67Z',
			'M41 55H54L25 88H12Z',
			'M55 76H70L59 88H43Z',
		);

		$svg = sprintf( '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 100 100" aria-hidden="true" focusable="false">', (int) $size );
		foreach ( $paths as $d ) {
			$svg .= '<path fill="' . esc_attr( $fill ) . '" d="' . esc_attr( $d ) . '"/>';
		}

		return $svg . '</svg>';
	}

	/**
	 * Print the screen: title, tabs, lead paragraph, then the current tab.
	 * The "Settings saved." notice comes from core (options-head.php), as
	 * this page sits under Settings.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$tabs    = self::tabs();
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab switch only.
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'welcome';
		}
		?>
		<div class="wrap drift-welcome">
			<h1 class="drift-welcome__title">
				<span class="drift-welcome__mark"><?php echo self::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed markup, attributes escaped in mark_svg(). ?></span>
				<?php echo esc_html( get_admin_page_title() ); ?>
				<a class="page-title-action" href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>"><?php esc_html_e( 'View dashboard', 'drift-welcome' ); ?></a>
			</h1>

			<nav class="nav-tab-wrapper drift-welcome__tabs" aria-label="<?php esc_attr_e( 'Drift: Welcome sections', 'drift-welcome' ); ?>">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<a href="<?php echo esc_url( self::url( 'welcome' === $key ? '' : $key ) ); ?>" class="nav-tab<?php echo $key === $current ? ' nav-tab-active' : ''; ?>"<?php echo $key === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tab['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<p class="drift-welcome__lead"><?php echo esc_html( $tabs[ $current ]['description'] ); ?></p>

			<?php call_user_func( $tabs[ $current ]['render'] ); ?>
		</div>
		<?php
	}

	/**
	 * Opening of a Welcome / Quick links / Colours form. All three post to
	 * handle_save() with their tab key and a per-tab nonce.
	 *
	 * @param string $tab Tab key.
	 * @return void
	 */
	private static function open_form( $tab ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'drift_welcome_save_' . $tab, 'drift_welcome_nonce' ); ?>
			<input type="hidden" name="action" value="drift_welcome_save">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
		<?php
	}

	/**
	 * Welcome tab.
	 *
	 * @return void
	 */
	public static function render_welcome_tab() {
		$settings = Drift_Welcome_Settings::get();
		self::open_form( 'welcome' );
		?>
			<section class="drift-welcome__card" aria-label="<?php esc_attr_e( 'Welcome panel', 'drift-welcome' ); ?>">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo', 'drift-welcome' ); ?></th>
						<td>
							<?php self::media_field( 'settings[logo_id]', 'drift-welcome-logo-id', absint( $settings['logo_id'] ) ); ?>
							<p class="drift-welcome-inline-fields">
								<label for="drift-welcome-logo-width"><?php esc_html_e( 'Max width (px)', 'drift-welcome' ); ?></label>
								<input type="number" id="drift-welcome-logo-width" name="settings[logo_width]" class="small-text" min="<?php echo esc_attr( (string) Drift_Welcome_Settings::MIN_LOGO_WIDTH ); ?>" max="<?php echo esc_attr( (string) Drift_Welcome_Settings::MAX_LOGO_WIDTH ); ?>" step="1" value="<?php echo esc_attr( (string) $settings['logo_width'] ); ?>">
							</p>
							<p class="description"><?php esc_html_e( 'Optional. Shown above the welcome heading, with the image\'s alt text from the media library (or the site name if it has none). Shrinks on narrow screens.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="drift-welcome-welcome-heading"><?php esc_html_e( 'Welcome heading', 'drift-welcome' ); ?></label></th>
						<td>
							<input type="text" id="drift-welcome-welcome-heading" name="settings[welcome_heading]" class="regular-text" value="<?php echo esc_attr( $settings['welcome_heading'] ); ?>" placeholder="<?php esc_attr_e( 'Welcome back', 'drift-welcome' ); ?>">
							<p class="description"><?php esc_html_e( 'Left blank, it says "Welcome back" and the user\'s name.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="drift_welcome_welcome_text"><?php esc_html_e( 'Welcome text', 'drift-welcome' ); ?></label></th>
						<td>
							<?php
							// Teeny toolbar (bold, italic, links, lists). Falls back
							// to a plain textarea if the user has the visual editor
							// turned off; wpautop() on output covers both.
							wp_editor(
								$settings['welcome_text'],
								'drift_welcome_welcome_text',
								array(
									'textarea_name' => 'settings[welcome_text]',
									'textarea_rows' => 5,
									'teeny'         => true,
									'media_buttons' => false,
									'quicktags'     => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown under the heading. Paragraphs, bold, italic, links and lists all work.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<?php submit_button( __( 'Save welcome settings', 'drift-welcome' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Quick links tab.
	 *
	 * @return void
	 */
	public static function render_quick_links_tab() {
		$settings = Drift_Welcome_Settings::get();
		self::open_form( 'quick-links' );
		?>
			<section class="drift-welcome__card" aria-labelledby="drift-welcome-links-title">
				<h2 class="drift-welcome__card-title" id="drift-welcome-links-title"><?php esc_html_e( 'Built-in links', 'drift-welcome' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="drift-welcome-analytics-url"><?php esc_html_e( 'Analytics URL', 'drift-welcome' ); ?></label></th>
						<td>
							<input type="url" id="drift-welcome-analytics-url" name="settings[analytics_url]" class="regular-text" value="<?php echo esc_attr( $settings['analytics_url'] ); ?>" placeholder="https://lookerstudio.google.com/...">
							<p class="description"><?php esc_html_e( 'This site\'s analytics dashboard, e.g. GA4 or Looker Studio. The Analytics card is hidden while this is blank.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="drift-welcome-support-url"><?php esc_html_e( 'Support URL', 'drift-welcome' ); ?></label></th>
						<td>
							<input type="url" id="drift-welcome-support-url" name="settings[support_url]" class="regular-text" value="<?php echo esc_attr( $settings['support_url'] ); ?>" placeholder="https://">
							<p class="description"><?php esc_html_e( 'Where editors go for help: a help desk, a contact form or a mailto: link. The Support card is hidden while this is blank.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="drift-welcome-team-cpt-slug"><?php esc_html_e( 'Team post type', 'drift-welcome' ); ?></label></th>
						<td>
							<input type="text" id="drift-welcome-team-cpt-slug" name="settings[team_cpt_slug]" class="regular-text" value="<?php echo esc_attr( $settings['team_cpt_slug'] ); ?>" placeholder="<?php echo esc_attr( Drift_Welcome_Settings::DEFAULT_TEAM_CPT_SLUG ); ?>">
							<p class="description"><?php esc_html_e( 'The post type slug for team members, if it isn\'t "team" (e.g. "staff"). The Team card only shows when that post type exists.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="drift-welcome__card" aria-labelledby="drift-welcome-cards-title">
				<h2 class="drift-welcome__card-title" id="drift-welcome-cards-title"><?php esc_html_e( 'Custom cards', 'drift-welcome' ); ?></h2>
				<p class="drift-welcome__intro">
					<?php
					printf(
						/* translators: %s: link to the Dashicons reference. */
						esc_html__( 'Extra cards shown after the built-in ones: a client portal, a booking system, a shared drive. Icon is a %s class name (e.g. "dashicons-star-filled"); left blank, a link icon is used. A card with no label or URL is dropped when you save.', 'drift-welcome' ),
						'<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Dashicons', 'drift-welcome' ) . '</a>'
					);
					?>
				</p>

				<div id="drift-welcome-custom-cards" data-next-index="<?php echo esc_attr( (string) count( $settings['custom_cards'] ) ); ?>">
					<?php foreach ( $settings['custom_cards'] as $index => $card ) : ?>
						<?php self::render_card_row( (int) $index, $card ); ?>
					<?php endforeach; ?>
				</div>

				<p>
					<button type="button" class="button" id="drift-welcome-add-card"><?php esc_html_e( '+ Add card', 'drift-welcome' ); ?></button>
				</p>

				<script type="text/template" id="drift-welcome-card-template">
					<?php self::render_card_row( '__INDEX__', array() ); ?>
				</script>
			</section>

			<?php submit_button( __( 'Save quick links', 'drift-welcome' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Colours tab.
	 *
	 * @return void
	 */
	public static function render_colours_tab() {
		$settings = Drift_Welcome_Settings::get();
		self::open_form( 'colours' );
		?>
			<section class="drift-welcome__card" aria-label="<?php esc_attr_e( 'Colour overrides', 'drift-welcome' ); ?>">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Welcome panel', 'drift-welcome' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[welcome_bg_color]', 'drift-welcome-welcome_bg_color', __( 'Background', 'drift-welcome' ), $settings['welcome_bg_color'] );
							self::color_field( 'settings[welcome_text_color]', 'drift-welcome-welcome_text_color', __( 'Text', 'drift-welcome' ), $settings['welcome_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and text colour of the welcome heading and message.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards', 'drift-welcome' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[icon_color]', 'drift-welcome-icon_color', __( 'Icon', 'drift-welcome' ), $settings['icon_color'] );
							self::color_field( 'settings[text_color]', 'drift-welcome-text_color', __( 'Text', 'drift-welcome' ), $settings['text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Icon and label colour on the cards.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards: hover', 'drift-welcome' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[hover_bg_color]', 'drift-welcome-hover_bg_color', __( 'Background', 'drift-welcome' ), $settings['hover_bg_color'] );
							self::color_field( 'settings[hover_text_color]', 'drift-welcome-hover_text_color', __( 'Icon / text', 'drift-welcome' ), $settings['hover_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and icon/text colour when a card is hovered or focused.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<?php submit_button( __( 'Save colours', 'drift-welcome' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Media library image picker, storing an attachment ID. Shared by the
	 * Welcome tab's logo and the White Label tab's images; assets/admin.js
	 * binds to the class names, so every instance works the same.
	 *
	 * @param string $name          Input name, e.g. "settings[logo_id]".
	 * @param string $id            Hidden input ID.
	 * @param int    $attachment_id Saved attachment ID, or 0.
	 * @return void
	 */
	public static function media_field( $name, $id, $attachment_id ) {
		$preview = $attachment_id ? wp_get_attachment_image( $attachment_id, 'medium' ) : '';
		?>
		<div class="drift-welcome-media-field">
			<div class="drift-welcome-media-field__preview"<?php echo $preview ? '' : ' hidden'; ?>>
				<?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by wp_get_attachment_image(), which escapes its own attributes. ?>
			</div>
			<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
			<p>
				<button type="button" class="button drift-welcome-media-field__select"><?php esc_html_e( 'Select image', 'drift-welcome' ); ?></button>
				<button type="button" class="button-link drift-welcome-media-field__remove"<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'drift-welcome' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * One labelled colour picker (wpColorPicker() in assets/admin.js). An
	 * empty value is valid and means "use the default".
	 *
	 * @param string $name  Input name, e.g. "settings[icon_color]".
	 * @param string $id    Input ID.
	 * @param string $label Visible label.
	 * @param string $value Saved hex colour, or ''.
	 * @return void
	 */
	public static function color_field( $name, $id, $label, $value ) {
		?>
		<span class="drift-welcome-color-field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="drift-welcome-color-picker" value="<?php echo esc_attr( $value ); ?>" data-default-color="">
		</span>
		<?php
	}

	/**
	 * One custom card row. Used for saved rows and for the "Add card"
	 * template, so the two can't drift apart. `$index` is a real key or the
	 * literal `__INDEX__`, which assets/admin.js replaces on clone.
	 *
	 * @param int|string $index Row index in `settings[custom_cards][{index}][…]`.
	 * @param array      $card  label/url/icon/external, or array() for a blank row.
	 * @return void
	 */
	private static function render_card_row( $index, array $card ) {
		$label    = isset( $card['label'] ) ? $card['label'] : '';
		$url      = isset( $card['url'] ) ? $card['url'] : '';
		$icon     = isset( $card['icon'] ) ? $card['icon'] : '';
		$external = ! empty( $card['external'] );
		$name     = 'settings[custom_cards][' . $index . ']';
		?>
		<div class="drift-welcome-card-row">
			<input type="text" name="<?php echo esc_attr( $name . '[label]' ); ?>" value="<?php echo esc_attr( $label ); ?>" aria-label="<?php esc_attr_e( 'Card label', 'drift-welcome' ); ?>" placeholder="<?php esc_attr_e( 'Label', 'drift-welcome' ); ?>">
			<input type="url" name="<?php echo esc_attr( $name . '[url]' ); ?>" value="<?php echo esc_attr( $url ); ?>" aria-label="<?php esc_attr_e( 'Card URL', 'drift-welcome' ); ?>" placeholder="https://example.com">
			<input type="text" name="<?php echo esc_attr( $name . '[icon]' ); ?>" value="<?php echo esc_attr( $icon ); ?>" aria-label="<?php esc_attr_e( 'Card icon (Dashicons class)', 'drift-welcome' ); ?>" placeholder="dashicons-admin-links">
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name . '[external]' ); ?>" value="1" <?php checked( $external ); ?>>
				<?php esc_html_e( 'Open in new tab', 'drift-welcome' ); ?>
			</label>
			<button type="button" class="button-link drift-welcome-remove-card"><?php esc_html_e( 'Remove', 'drift-welcome' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Saves the Welcome, Quick links or Colours tab, whichever was posted.
	 *
	 * @return void
	 */
	public static function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'drift-welcome' ) );
		}

		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce action depends on the tab; checked below.
		if ( ! isset( Drift_Welcome_Settings::SECTIONS[ $tab ] ) ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'drift-welcome' ) );
		}

		check_admin_referer( 'drift_welcome_save_' . $tab, 'drift_welcome_nonce' );

		$input = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field in Drift_Welcome_Settings::save().
		Drift_Welcome_Settings::save( is_array( $input ) ? $input : array(), $tab );

		wp_safe_redirect( self::url( 'welcome' === $tab ? '' : $tab, array( 'settings-updated' => 'true' ) ) );
		exit;
	}
}
