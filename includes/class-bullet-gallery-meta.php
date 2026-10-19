<?php
/**
 * Meta boxes for a gallery section.
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta box handler.
 */
class Bullet_Gallery_Meta {

	const KEY_IMAGES   = '_bullet_gallery_images';
	const KEY_INTRO    = '_bullet_gallery_intro';
	const KEY_BG       = '_bullet_gallery_bg';
	const KEY_HEADING  = '_bullet_gallery_heading_color';
	const KEY_TEXT     = '_bullet_gallery_text_color';
	const KEY_OVERLAY  = '_bullet_gallery_caption_mode';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . BULLET_GALLERY_CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Enqueue admin assets on the edit screen.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || BULLET_GALLERY_CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'bullet-gallery-admin',
			BULLET_GALLERY_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			BULLET_GALLERY_VERSION
		);

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'bullet-gallery-admin',
			BULLET_GALLERY_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
			BULLET_GALLERY_VERSION,
			true
		);

		wp_localize_script(
			'bullet-gallery-admin',
			'bulletGalleryAdmin',
			array(
				'frameTitle'  => __( 'Select photos for this section', 'bullet-gallery' ),
				'frameButton' => __( 'Add to section', 'bullet-gallery' ),
				'removeLabel' => __( 'Remove this photo', 'bullet-gallery' ),
				'confirmAll'  => __( 'Remove every photo from this section?', 'bullet-gallery' ),
				'noAlt'       => __( 'No alt text', 'bullet-gallery' ),
				'countOne'    => __( '1 photo', 'bullet-gallery' ),
				/* translators: %s: number of photos. */
				'countMany'   => __( '%s photos', 'bullet-gallery' ),
			)
		);
	}

	/**
	 * Register the meta boxes.
	 *
	 * @return void
	 */
	public static function add_boxes() {
		add_meta_box(
			'bullet-gallery-images',
			__( 'Photos', 'bullet-gallery' ),
			array( __CLASS__, 'box_images' ),
			BULLET_GALLERY_CPT,
			'normal',
			'high'
		);

		add_meta_box(
			'bullet-gallery-intro',
			__( 'Short description (optional)', 'bullet-gallery' ),
			array( __CLASS__, 'box_intro' ),
			BULLET_GALLERY_CPT,
			'normal',
			'default'
		);

		add_meta_box(
			'bullet-gallery-style',
			__( 'Section colours', 'bullet-gallery' ),
			array( __CLASS__, 'box_style' ),
			BULLET_GALLERY_CPT,
			'side',
			'default'
		);

		add_meta_box(
			'bullet-gallery-howto',
			__( 'How to show this', 'bullet-gallery' ),
			array( __CLASS__, 'box_howto' ),
			BULLET_GALLERY_CPT,
			'side',
			'default'
		);
	}

	/**
	 * Photos meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function box_images( $post ) {
		wp_nonce_field( 'bullet_gallery_save', 'bullet_gallery_nonce' );
		$ids = self::get_image_ids( $post->ID );
		?>
		<div class="bgal-images" data-bgal-images>
			<p class="bgal-images__actions">
				<button type="button" class="button button-primary" data-bgal-add>
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add photos', 'bullet-gallery' ); ?>
				</button>
				<button type="button" class="button button-link-delete" data-bgal-clear>
					<?php esc_html_e( 'Remove all', 'bullet-gallery' ); ?>
				</button>
				<span class="bgal-images__count" data-bgal-count></span>
			</p>

			<p class="bgal-images__hint">
				<?php esc_html_e( 'Drag the thumbnails to change the order. The first photo is the one people see first in the row.', 'bullet-gallery' ); ?>
			</p>

			<ul class="bgal-images__list" data-bgal-list>
				<?php foreach ( $ids as $id ) : ?>
					<?php
					$thumb = wp_get_attachment_image( (int) $id, 'thumbnail' );

					if ( ! $thumb ) {
						continue;
					}

					$alt = get_post_meta( (int) $id, '_wp_attachment_image_alt', true );
					?>
					<li class="bgal-thumb" data-id="<?php echo esc_attr( (string) $id ); ?>">
						<?php echo $thumb; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns safe markup. */ ?>
						<?php if ( ! $alt ) : ?>
							<span class="bgal-thumb__warn" title="<?php esc_attr_e( 'No alt text set for this photo', 'bullet-gallery' ); ?>">!</span>
						<?php endif; ?>
						<button type="button" class="bgal-thumb__remove" aria-label="<?php esc_attr_e( 'Remove this photo', 'bullet-gallery' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="bgal-images__empty" data-bgal-empty<?php echo empty( $ids ) ? '' : ' style="display:none"'; ?>>
				<?php esc_html_e( 'No photos in this section yet.', 'bullet-gallery' ); ?>
			</p>

			<input type="hidden" name="bullet_gallery_images" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-bgal-input />
		</div>
		<?php
	}

	/**
	 * Intro text meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function box_intro( $post ) {
		$intro = get_post_meta( $post->ID, self::KEY_INTRO, true );
		?>
		<p class="bgal-images__hint">
			<?php esc_html_e( 'One or two lines shown under the section name. Leave empty to hide it.', 'bullet-gallery' ); ?>
		</p>
		<textarea name="bullet_gallery_intro" rows="3" class="widefat" id="bullet_gallery_intro"><?php echo esc_textarea( $intro ); ?></textarea>
		<?php
	}

	/**
	 * Colour meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function box_style( $post ) {
		$fields = array(
			'bullet_gallery_bg'            => array(
				'label' => __( 'Background', 'bullet-gallery' ),
				'value' => get_post_meta( $post->ID, self::KEY_BG, true ),
			),
			'bullet_gallery_heading_color' => array(
				'label' => __( 'Section name', 'bullet-gallery' ),
				'value' => get_post_meta( $post->ID, self::KEY_HEADING, true ),
			),
			'bullet_gallery_text_color'    => array(
				'label' => __( 'Description text', 'bullet-gallery' ),
				'value' => get_post_meta( $post->ID, self::KEY_TEXT, true ),
			),
		);

		foreach ( $fields as $name => $field ) {
			printf(
				'<p class="bgal-color-field"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%1$s" value="%3$s" class="bgal-color" data-default-color="" /></p>',
				esc_attr( $name ),
				esc_html( $field['label'] ),
				esc_attr( $field['value'] )
			);
		}

		$caption = get_post_meta( $post->ID, self::KEY_OVERLAY, true );
		?>
		<p class="bgal-color-field">
			<label for="bullet_gallery_caption_mode"><?php esc_html_e( 'Photo captions', 'bullet-gallery' ); ?></label>
			<select name="bullet_gallery_caption_mode" id="bullet_gallery_caption_mode" class="widefat">
				<option value="hover" <?php selected( $caption, 'hover' ); ?>><?php esc_html_e( 'Show on hover', 'bullet-gallery' ); ?></option>
				<option value="always" <?php selected( $caption, 'always' ); ?>><?php esc_html_e( 'Always visible', 'bullet-gallery' ); ?></option>
				<option value="none" <?php selected( $caption, 'none' ); ?>><?php esc_html_e( 'Hidden', 'bullet-gallery' ); ?></option>
			</select>
		</p>
		<p class="bgal-images__hint">
			<?php esc_html_e( 'Leave a colour empty to use the defaults from Bullet Gallery → Settings.', 'bullet-gallery' ); ?>
		</p>
		<?php
	}

	/**
	 * Shortcode helper meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function box_howto( $post ) {
		?>
		<p><?php esc_html_e( 'Put this shortcode on any page to show every section, in the order you set under Reorder:', 'bullet-gallery' ); ?></p>
		<p><input type="text" class="widefat code" readonly value="[bullet_gallery]" onclick="this.select();" /></p>
		<p><?php esc_html_e( 'Or show only this one section:', 'bullet-gallery' ); ?></p>
		<p><input type="text" class="widefat code" readonly value="[bullet_gallery ids=&quot;<?php echo esc_attr( (string) $post->ID ); ?>&quot;]" onclick="this.select();" /></p>
		<?php
	}

	/**
	 * Save the meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['bullet_gallery_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['bullet_gallery_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'bullet_gallery_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['bullet_gallery_images'] )
			? sanitize_text_field( wp_unslash( $_POST['bullet_gallery_images'] ) )
			: '';

		$ids = array_values(
			array_filter(
				array_map( 'absint', explode( ',', $raw ) )
			)
		);

		update_post_meta( $post_id, self::KEY_IMAGES, $ids );

		$intro = isset( $_POST['bullet_gallery_intro'] )
			? sanitize_textarea_field( wp_unslash( $_POST['bullet_gallery_intro'] ) )
			: '';
		update_post_meta( $post_id, self::KEY_INTRO, $intro );

		$colors = array(
			'bullet_gallery_bg'            => self::KEY_BG,
			'bullet_gallery_heading_color' => self::KEY_HEADING,
			'bullet_gallery_text_color'    => self::KEY_TEXT,
		);

		foreach ( $colors as $field => $meta_key ) {
			$value = isset( $_POST[ $field ] )
				? sanitize_hex_color( wp_unslash( $_POST[ $field ] ) )
				: '';
			update_post_meta( $post_id, $meta_key, $value ? $value : '' );
		}

		$caption = isset( $_POST['bullet_gallery_caption_mode'] )
			? sanitize_key( wp_unslash( $_POST['bullet_gallery_caption_mode'] ) )
			: 'hover';

		if ( ! in_array( $caption, array( 'hover', 'always', 'none' ), true ) ) {
			$caption = 'hover';
		}

		update_post_meta( $post_id, self::KEY_OVERLAY, $caption );

		unset( $post );
	}

	/**
	 * Get the stored attachment IDs for a section.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_image_ids( $post_id ) {
		$ids = get_post_meta( $post_id, self::KEY_IMAGES, true );

		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}
}
