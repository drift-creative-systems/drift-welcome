<?php
/**
 * Admin branding and login screen: the White Label tab on
 * Settings → Drift: Welcome.
 *
 *   Branding     hide WordPress branding (admin bar logo/links, footer
 *                credit, version), a custom admin bar logo, custom admin
 *                footer text.
 *   Login screen logo (size, links to the site), page background
 *                colour/image, form/label/button/link colours.
 *   Admin menus  Drift_Welcome_Admin_Access, rendered and saved as part of
 *                this tab's form.
 *
 * Every field starts blank and blank means "leave WordPress as it is", so
 * nothing changes until it's set up.
 *
 * Images are stored as attachment IDs, like the dashboard logo, so they
 * survive a staging to live domain change.
 *
 * White Label CMS should be deactivated once this is set up; both running
 * at once fight over the same login and admin bar output. The tab shows a
 * warning while it's active.
 *
 * @package Drift_Welcome
 */

defined( 'ABSPATH' ) || exit;

/**
 * White label settings and output.
 */
final class Drift_Welcome_White_Label {

	const OPTION = 'drift_welcome_white_label';

	/** Login form content width in core: the logo's default width cap. */
	const LOGIN_LOGO_MAX_DEFAULT = 320;

	/** Core's own login logo size, used when an image has no dimensions (e.g. SVG). */
	const LOGIN_LOGO_FALLBACK = 84;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_drift_welcome_save_white_label', array( __CLASS__, 'handle_save' ) );

		// Branding. Logo added early so it sits at the far left; the
		// WordPress logo (added by core at priority 10) removed late.
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_logo' ), 5 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_remove_wp_logo' ), 999 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_bar_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'admin_bar_styles' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( __CLASS__, 'footer_version' ), 99 );

		// Login screen.
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'login_styles' ) );
		add_filter( 'login_headerurl', array( __CLASS__, 'login_logo_url' ) );
		add_filter( 'login_headertext', array( __CLASS__, 'login_logo_text' ) );
	}

	/**
	 * Every field, blank. Blank always means "WordPress default".
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Branding.
			'hide_wp_branding'    => '0',
			'admin_bar_logo_id'   => 0,
			'admin_bar_logo_url'  => '',
			'footer_text'         => '',
			'footer_url'          => '',

			// Login screen.
			'login_logo_id'       => 0,
			'login_logo_width'    => '',
			'login_logo_height'   => '',
			'login_bg_color'      => '',
			'login_bg_image_id'   => 0,
			'login_form_bg_color' => '',
			'login_label_color'   => '',
			'login_button_color'  => '',
			'login_button_text'   => '',
			'login_button_hover'  => '',
			'login_link_color'    => '',
		);
	}

	/**
	 * Colour fields, in the order they're shown on the tab.
	 *
	 * @return array<string, string>
	 */
	private static function colour_fields() {
		return array(
			'login_bg_color'      => __( 'Page background', 'drift-welcome' ),
			'login_form_bg_color' => __( 'Login box background', 'drift-welcome' ),
			'login_label_color'   => __( 'Form labels', 'drift-welcome' ),
			'login_button_color'  => __( 'Button', 'drift-welcome' ),
			'login_button_text'   => __( 'Button text', 'drift-welcome' ),
			'login_button_hover'  => __( 'Button hover', 'drift-welcome' ),
			'login_link_color'    => __( 'Links', 'drift-welcome' ),
		);
	}

	/**
	 * Saved settings over the defaults.
	 *
	 * @return array
	 */
	public static function settings() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/* ── White Label tab ─────────────────────────────────────────────── */

	/**
	 * Tab content. Only listed for users who pass
	 * Drift_Welcome_Admin_Access::can_manage().
	 *
	 * @return void
	 */
	public static function render_tab() {
		$s = self::settings();
		?>
		<?php if ( defined( 'WLCMS_VERSION' ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'White Label CMS is still active. Deactivate it once you\'re happy with these settings: both plugins change the login screen and admin bar, and they\'ll conflict.', 'drift-welcome' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'drift_welcome_save_white_label', 'drift_welcome_white_label_nonce' ); ?>
			<input type="hidden" name="action" value="drift_welcome_save_white_label">

			<section class="drift-welcome__card" aria-labelledby="drift-welcome-branding-title">
				<h2 class="drift-welcome__card-title" id="drift-welcome-branding-title"><?php esc_html_e( 'Branding', 'drift-welcome' ); ?></h2>
				<p class="drift-welcome__intro"><?php esc_html_e( 'Applies to everyone, agency users included.', 'drift-welcome' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'WordPress branding', 'drift-welcome' ); ?></th>
						<td>
							<input type="hidden" name="white_label[hide_wp_branding]" value="0">
							<label for="drift-welcome-wl-hide_wp_branding">
								<input type="checkbox" id="drift-welcome-wl-hide_wp_branding" name="white_label[hide_wp_branding]" value="1" <?php checked( $s['hide_wp_branding'], '1' ); ?>>
								<?php esc_html_e( 'Hide the WordPress logo and links in the admin bar, the "Thank you for creating with WordPress" footer and the version number.', 'drift-welcome' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Admin bar logo', 'drift-welcome' ); ?></th>
						<td>
							<?php Drift_Welcome_Admin::media_field( 'white_label[admin_bar_logo_id]', 'drift-welcome-wl-admin_bar_logo_id', (int) $s['admin_bar_logo_id'] ); ?>
							<p class="description"><?php esc_html_e( 'Optional. Shown at the top left of the admin bar, 20px tall.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<?php
					self::text_row( 'admin_bar_logo_url', __( 'Admin bar logo link', 'drift-welcome' ), $s, 'url', __( 'Leave blank to link to the dashboard.', 'drift-welcome' ) );
					self::text_row( 'footer_text', __( 'Admin footer text', 'drift-welcome' ), $s, 'text', __( 'Replaces "Thank you for creating with WordPress". Leave blank to keep it (or hide it, if WordPress branding is hidden).', 'drift-welcome' ) );
					self::text_row( 'footer_url', __( 'Admin footer link', 'drift-welcome' ), $s, 'url', __( 'Optional. Makes the footer text a link.', 'drift-welcome' ) );
					?>
				</table>
			</section>

			<section class="drift-welcome__card" aria-labelledby="drift-welcome-login-title">
				<h2 class="drift-welcome__card-title" id="drift-welcome-login-title"><?php esc_html_e( 'Login screen', 'drift-welcome' ); ?></h2>
				<p class="drift-welcome__intro"><?php esc_html_e( 'Anything left blank keeps the standard WordPress login screen.', 'drift-welcome' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo', 'drift-welcome' ); ?></th>
						<td>
							<?php Drift_Welcome_Admin::media_field( 'white_label[login_logo_id]', 'drift-welcome-wl-login_logo_id', (int) $s['login_logo_id'] ); ?>
							<p class="drift-welcome-inline-fields">
								<label for="drift-welcome-wl-login_logo_width"><?php esc_html_e( 'Width (px)', 'drift-welcome' ); ?></label>
								<input type="number" min="1" max="1000" step="1" id="drift-welcome-wl-login_logo_width" class="small-text" name="white_label[login_logo_width]" value="<?php echo esc_attr( (string) $s['login_logo_width'] ); ?>">
								<label for="drift-welcome-wl-login_logo_height"><?php esc_html_e( 'Height (px)', 'drift-welcome' ); ?></label>
								<input type="number" min="1" max="1000" step="1" id="drift-welcome-wl-login_logo_height" class="small-text" name="white_label[login_logo_height]" value="<?php echo esc_attr( (string) $s['login_logo_height'] ); ?>">
							</p>
							<p class="description"><?php esc_html_e( 'Links to this site\'s home page. Size is optional: blank width uses the image\'s own width up to 320px, and blank height keeps its proportions.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Background image', 'drift-welcome' ); ?></th>
						<td>
							<?php Drift_Welcome_Admin::media_field( 'white_label[login_bg_image_id]', 'drift-welcome-wl-login_bg_image_id', (int) $s['login_bg_image_id'] ); ?>
							<p class="description"><?php esc_html_e( 'Optional. Covers the whole page, over the background colour.', 'drift-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Colours', 'drift-welcome' ); ?></th>
						<td>
							<?php
							foreach ( self::colour_fields() as $key => $label ) {
								Drift_Welcome_Admin::color_field( 'white_label[' . $key . ']', 'drift-welcome-wl-' . $key, $label, (string) $s[ $key ] );
							}
							?>
						</td>
					</tr>
				</table>
			</section>

			<?php Drift_Welcome_Admin_Access::render_settings_card(); ?>

			<?php submit_button( __( 'Save white label settings', 'drift-welcome' ) ); ?>
		</form>
		<?php
	}

	/**
	 * One text or URL row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Row label.
	 * @param array  $s     Settings.
	 * @param string $type  Input type.
	 * @param string $help  Description.
	 * @return void
	 */
	private static function text_row( $key, $label, array $s, $type, $help ) {
		$id = 'drift-welcome-wl-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" class="regular-text" name="white_label[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $s[ $key ] ); ?>">
				<p class="description"><?php echo esc_html( $help ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the White Label tab: branding and login here, the menu settings
	 * via Drift_Welcome_Admin_Access::save_settings().
	 *
	 * @return void
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! Drift_Welcome_Admin_Access::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'drift-welcome' ) );
		}

		check_admin_referer( 'drift_welcome_save_white_label', 'drift_welcome_white_label_nonce' );

		$input = isset( $_POST['white_label'] ) ? wp_unslash( $_POST['white_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field below.
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : '';

			switch ( $key ) {
				case 'hide_wp_branding':
					$out[ $key ] = ! empty( $raw ) ? '1' : '0';
					break;
				case 'admin_bar_logo_id':
				case 'login_logo_id':
				case 'login_bg_image_id':
					$out[ $key ] = Drift_Welcome_Settings::sanitize_image_id( $raw );
					break;
				case 'login_logo_width':
				case 'login_logo_height':
					$out[ $key ] = absint( $raw ) ? min( 1000, absint( $raw ) ) : '';
					break;
				case 'admin_bar_logo_url':
				case 'footer_url':
					$out[ $key ] = esc_url_raw( (string) $raw );
					break;
				case 'footer_text':
					$out[ $key ] = sanitize_text_field( (string) $raw );
					break;
				default:
					// Every remaining field is a colour.
					$out[ $key ] = (string) sanitize_hex_color( (string) $raw );
			}
		}

		update_option( self::OPTION, $out, false );
		Drift_Welcome_Admin_Access::save_settings( isset( $_POST['admin_access'] ) ? wp_unslash( $_POST['admin_access'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in save_settings().

		wp_safe_redirect( Drift_Welcome_Admin::url( 'white-label', array( 'settings-updated' => 'true' ) ) );
		exit;
	}

	/* ── Branding ────────────────────────────────────────────────────── */

	/**
	 * Removes the WordPress logo menu (About, WordPress.org, Support…).
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public static function admin_bar_remove_wp_logo( $bar ) {
		if ( '1' === self::settings()['hide_wp_branding'] ) {
			$bar->remove_node( 'wp-logo' );
		}
	}

	/**
	 * Adds the custom logo at the far left of the admin bar.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public static function admin_bar_logo( $bar ) {
		$s   = self::settings();
		$src = $s['admin_bar_logo_id'] ? wp_get_attachment_image_url( (int) $s['admin_bar_logo_id'], 'medium' ) : '';

		if ( ! $src ) {
			return;
		}

		$bar->add_node(
			array(
				'id'    => 'drift-welcome-admin-logo',
				'title' => sprintf( '<img src="%s" alt="%s">', esc_url( $src ), esc_attr( get_bloginfo( 'name' ) ) ),
				'href'  => $s['admin_bar_logo_url'] ? esc_url( $s['admin_bar_logo_url'] ) : admin_url(),
				'meta'  => array( 'class' => 'drift-welcome-admin-logo' ),
			)
		);
	}

	/**
	 * Sizes the admin bar logo, added to core's admin-bar stylesheet. Only
	 * when there's a logo and a bar.
	 *
	 * @return void
	 */
	public static function admin_bar_styles() {
		if ( ! is_admin_bar_showing() || ! self::settings()['admin_bar_logo_id'] ) {
			return;
		}
		wp_add_inline_style( 'admin-bar', '#wpadminbar .drift-welcome-admin-logo > .ab-item{display:flex;align-items:center;}#wpadminbar .drift-welcome-admin-logo img{display:block;height:20px;width:auto;max-width:160px;}' );
	}

	/**
	 * Admin footer credit, replacing "Thank you for creating with WordPress".
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		$s = self::settings();

		if ( '' === $s['footer_text'] ) {
			return '1' === $s['hide_wp_branding'] ? '' : $text;
		}

		return $s['footer_url']
			? sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $s['footer_url'] ), esc_html( $s['footer_text'] ) )
			: esc_html( $s['footer_text'] );
	}

	/**
	 * Hides the WordPress version in the admin footer.
	 *
	 * @param string $text Footer version text.
	 * @return string
	 */
	public static function footer_version( $text ) {
		return '1' === self::settings()['hide_wp_branding'] ? '' : $text;
	}

	/* ── Login screen ────────────────────────────────────────────────── */

	/**
	 * Logo links to the site rather than wordpress.org, once a logo is set.
	 *
	 * @param string $url Logo link.
	 * @return string
	 */
	public static function login_logo_url( $url ) {
		return self::settings()['login_logo_id'] ? home_url( '/' ) : $url;
	}

	/**
	 * Logo text is the site name, once a logo is set.
	 *
	 * @param string $text Logo text.
	 * @return string
	 */
	public static function login_logo_text( $text ) {
		return self::settings()['login_logo_id'] ? get_bloginfo( 'name' ) : $text;
	}

	/**
	 * Login screen CSS, added inline after core's `login` stylesheet. Every
	 * value is sanitised on save (hex colours, attachment IDs, integers) and
	 * escaped again here. Nothing is printed when every field is blank.
	 *
	 * @return void
	 */
	public static function login_styles() {
		$s   = self::settings();
		$css = '';

		$logo = $s['login_logo_id'] ? wp_get_attachment_image_src( (int) $s['login_logo_id'], 'full' ) : false;
		if ( $logo ) {
			list( $w, $h ) = self::login_logo_size( (int) $logo[1], (int) $logo[2], absint( $s['login_logo_width'] ), absint( $s['login_logo_height'] ) );

			$css .= sprintf(
				'.login h1 a{background-image:url("%1$s");background-size:contain;background-position:center;width:%2$dpx;height:%3$dpx;max-width:100%%;}',
				esc_url( $logo[0] ),
				$w,
				$h
			);
		}

		$bg_color = sanitize_hex_color( $s['login_bg_color'] );
		if ( $bg_color ) {
			$css .= 'body.login{background-color:' . $bg_color . ';}';
		}

		$bg_image = $s['login_bg_image_id'] ? wp_get_attachment_image_url( (int) $s['login_bg_image_id'], 'full' ) : '';
		if ( $bg_image ) {
			$css .= sprintf( 'body.login{background-image:url("%s");background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed;}', esc_url( $bg_image ) );
		}

		$colour_rules = array(
			'login_form_bg_color' => '.login form{background-color:%s;}',
			'login_label_color'   => '.login form label,.login form .forgetmenot label{color:%s;}',
			'login_button_color'  => '.login .button-primary{background-color:%1$s;border-color:%1$s;}',
			'login_button_text'   => '.login .button-primary{color:%s;}',
			'login_button_hover'  => '.login .button-primary:hover,.login .button-primary:focus{background-color:%1$s;border-color:%1$s;}',
			'login_link_color'    => '.login #nav a,.login #backtoblog a,.login .privacy-policy-link{color:%s;}',
		);

		foreach ( $colour_rules as $key => $rule ) {
			$colour = sanitize_hex_color( $s[ $key ] );
			if ( $colour ) {
				$css .= sprintf( $rule, $colour );
			}
		}

		if ( '' !== $css ) {
			wp_add_inline_style( 'login', $css );
		}
	}

	/**
	 * Login logo box size. Width: the saved value, else the image's own width
	 * capped at the login form's 320px. Height: the saved value, else
	 * whatever keeps the image's proportions at that width. Images with no
	 * stored dimensions (SVG) fall back to core's 84px square logo box.
	 *
	 * @param int $natural_w Image width.
	 * @param int $natural_h Image height.
	 * @param int $width     Saved width, or 0.
	 * @param int $height    Saved height, or 0.
	 * @return int[] [ width, height ]
	 */
	private static function login_logo_size( $natural_w, $natural_h, $width, $height ) {
		if ( ! $width ) {
			$width = $natural_w ? min( self::LOGIN_LOGO_MAX_DEFAULT, $natural_w ) : self::LOGIN_LOGO_FALLBACK;
		}
		if ( ! $height ) {
			$height = ( $natural_w && $natural_h ) ? (int) round( $width * $natural_h / $natural_w ) : self::LOGIN_LOGO_FALLBACK;
		}
		return array( $width, max( 1, $height ) );
	}
}
