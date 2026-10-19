<?php
/**
 * Global settings screen.
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings handler.
 */
class Bullet_Gallery_Settings {

	const OPTION = 'bullet_gallery_settings';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'accent'      => '#EFA31D',
			'heading'     => '#1F4D3A',
			'text'        => '#6B7F73',
			'background'  => '',
			'card_width'  => 320,
			'ratio'       => '4-3',
			'gap'         => 14,
			'show_count'  => 1,
			'lightbox'    => 1,
			'max_width'   => 1200,
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Add the settings submenu.
	 *
	 * @return void
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . BULLET_GALLERY_CPT,
			__( 'Bullet Gallery Settings', 'bullet-gallery' ),
			__( 'Settings', 'bullet-gallery' ),
			'manage_options',
			'bullet-gallery-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register the option.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'bullet_gallery_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$clean    = array();

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		foreach ( array( 'accent', 'heading', 'text', 'background' ) as $key ) {
			$value         = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$clean[ $key ] = $value ? $value : ( 'background' === $key ? '' : $defaults[ $key ] );
		}

		$clean['card_width'] = isset( $input['card_width'] ) ? absint( $input['card_width'] ) : $defaults['card_width'];
		$clean['card_width'] = max( 160, min( 720, $clean['card_width'] ) );

		$clean['gap'] = isset( $input['gap'] ) ? absint( $input['gap'] ) : $defaults['gap'];
		$clean['gap'] = max( 0, min( 64, $clean['gap'] ) );

		$clean['max_width'] = isset( $input['max_width'] ) ? absint( $input['max_width'] ) : $defaults['max_width'];
		$clean['max_width'] = max( 600, min( 2000, $clean['max_width'] ) );

		$ratio = isset( $input['ratio'] ) ? sanitize_key( $input['ratio'] ) : $defaults['ratio'];
		$clean['ratio'] = array_key_exists( $ratio, self::ratios() ) ? $ratio : $defaults['ratio'];

		$clean['show_count'] = empty( $input['show_count'] ) ? 0 : 1;
		$clean['lightbox']   = empty( $input['lightbox'] ) ? 0 : 1;

		return $clean;
	}

	/**
	 * Available card aspect ratios.
	 *
	 * @return array
	 */
	public static function ratios() {
		return array(
			'4-3'  => array( 'label' => '4 : 3', 'css' => '4 / 3' ),
			'3-2'  => array( 'label' => '3 : 2', 'css' => '3 / 2' ),
			'1-1'  => array( 'label' => '1 : 1', 'css' => '1 / 1' ),
			'16-9' => array( 'label' => '16 : 9', 'css' => '16 / 9' ),
			'3-4'  => array( 'label' => '3 : 4 (portrait)', 'css' => '3 / 4' ),
		);
	}

	/**
	 * Render the settings screen.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get();
		?>
		<div class="wrap bgal-settings">
			<h1><?php esc_html_e( 'Bullet Gallery Settings', 'bullet-gallery' ); ?></h1>
			<p><?php esc_html_e( 'These apply to every section. Individual sections can override the colours.', 'bullet-gallery' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'bullet_gallery_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bgal-accent"><?php esc_html_e( 'Accent colour', 'bullet-gallery' ); ?></label></th>
						<td>
							<input type="text" id="bgal-accent" class="bgal-color" name="<?php echo esc_attr( self::OPTION ); ?>[accent]" value="<?php echo esc_attr( $settings['accent'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Used for the arrows, scroll bar and photo count.', 'bullet-gallery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-heading"><?php esc_html_e( 'Section name colour', 'bullet-gallery' ); ?></label></th>
						<td><input type="text" id="bgal-heading" class="bgal-color" name="<?php echo esc_attr( self::OPTION ); ?>[heading]" value="<?php echo esc_attr( $settings['heading'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-text"><?php esc_html_e( 'Description colour', 'bullet-gallery' ); ?></label></th>
						<td><input type="text" id="bgal-text" class="bgal-color" name="<?php echo esc_attr( self::OPTION ); ?>[text]" value="<?php echo esc_attr( $settings['text'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-background"><?php esc_html_e( 'Default background', 'bullet-gallery' ); ?></label></th>
						<td>
							<input type="text" id="bgal-background" class="bgal-color" name="<?php echo esc_attr( self::OPTION ); ?>[background]" value="<?php echo esc_attr( $settings['background'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Leave empty to let the theme background show through.', 'bullet-gallery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-ratio"><?php esc_html_e( 'Photo shape', 'bullet-gallery' ); ?></label></th>
						<td>
							<select id="bgal-ratio" name="<?php echo esc_attr( self::OPTION ); ?>[ratio]">
								<?php foreach ( self::ratios() as $key => $ratio ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['ratio'], $key ); ?>><?php echo esc_html( $ratio['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Every photo is cropped to this shape, so each row stays perfectly even.', 'bullet-gallery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-card-width"><?php esc_html_e( 'Photo width on desktop', 'bullet-gallery' ); ?></label></th>
						<td>
							<input type="number" id="bgal-card-width" min="160" max="720" step="10" name="<?php echo esc_attr( self::OPTION ); ?>[card_width]" value="<?php echo esc_attr( (string) $settings['card_width'] ); ?>" /> px
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-gap"><?php esc_html_e( 'Space between photos', 'bullet-gallery' ); ?></label></th>
						<td>
							<input type="number" id="bgal-gap" min="0" max="64" name="<?php echo esc_attr( self::OPTION ); ?>[gap]" value="<?php echo esc_attr( (string) $settings['gap'] ); ?>" /> px
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bgal-max-width"><?php esc_html_e( 'Content width', 'bullet-gallery' ); ?></label></th>
						<td>
							<input type="number" id="bgal-max-width" min="600" max="2000" step="10" name="<?php echo esc_attr( self::OPTION ); ?>[max_width]" value="<?php echo esc_attr( (string) $settings['max_width'] ); ?>" /> px
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Extras', 'bullet-gallery' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_count]" value="1" <?php checked( $settings['show_count'], 1 ); ?> />
								<?php esc_html_e( 'Show the photo count next to each section name', 'bullet-gallery' ); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[lightbox]" value="1" <?php checked( $settings['lightbox'], 1 ); ?> />
								<?php esc_html_e( 'Open photos full screen when clicked', 'bullet-gallery' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
