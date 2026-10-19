<?php
/**
 * Drag and drop ordering screen.
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reorder handler.
 */
class Bullet_Gallery_Reorder {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_bullet_gallery_reorder', array( __CLASS__, 'handle_ajax' ) );
	}

	/**
	 * Add the reorder submenu.
	 *
	 * @return void
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . BULLET_GALLERY_CPT,
			__( 'Reorder Sections', 'bullet-gallery' ),
			__( 'Reorder', 'bullet-gallery' ),
			'edit_posts',
			'bullet-gallery-reorder',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Enqueue the reorder script.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'bullet-gallery-reorder' ) ) {
			return;
		}

		wp_enqueue_script(
			'bullet-gallery-reorder',
			BULLET_GALLERY_URL . 'assets/js/reorder.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			BULLET_GALLERY_VERSION,
			true
		);

		wp_localize_script(
			'bullet-gallery-reorder',
			'bulletGalleryReorder',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'bullet_gallery_reorder' ),
				'saving'  => __( 'Saving order…', 'bullet-gallery' ),
				'saved'   => __( 'Order saved.', 'bullet-gallery' ),
				'failed'  => __( 'Could not save the order. Please reload and try again.', 'bullet-gallery' ),
			)
		);
	}

	/**
	 * Fetch all sections in their current order.
	 *
	 * @return WP_Post[]
	 */
	public static function get_sections() {
		return get_posts(
			array(
				'post_type'        => BULLET_GALLERY_CPT,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => 200,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'date'       => 'ASC',
				),
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * Render the reorder screen.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$sections = self::get_sections();
		?>
		<div class="wrap bgal-reorder">
			<h1><?php esc_html_e( 'Reorder Sections', 'bullet-gallery' ); ?></h1>

			<?php if ( empty( $sections ) ) : ?>
				<p>
					<?php esc_html_e( 'No sections yet.', 'bullet-gallery' ); ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . BULLET_GALLERY_CPT ) ); ?>"><?php esc_html_e( 'Add your first one.', 'bullet-gallery' ); ?></a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Drag the rows to set the order they appear on the page. Changes save automatically.', 'bullet-gallery' ); ?></p>

				<ul class="bgal-reorder__list" id="bgal-reorder-list">
					<?php foreach ( $sections as $section ) : ?>
						<?php
						$ids   = Bullet_Gallery_Meta::get_image_ids( $section->ID );
						$thumb = ! empty( $ids ) ? wp_get_attachment_image( (int) $ids[0], array( 60, 60 ) ) : '';
						?>
						<li class="bgal-reorder__item" data-id="<?php echo esc_attr( (string) $section->ID ); ?>">
							<span class="bgal-reorder__handle dashicons dashicons-menu" aria-hidden="true"></span>
							<span class="bgal-reorder__thumb"><?php echo $thumb; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns safe markup. */ ?></span>
							<span class="bgal-reorder__title">
								<strong><?php echo esc_html( get_the_title( $section ) ); ?></strong>
								<em>
								<?php
								printf(
									/* translators: %d: number of photos. */
									esc_html( _n( '%d photo', '%d photos', count( $ids ), 'bullet-gallery' ) ),
									(int) count( $ids )
								);
								?>
								</em>
							</span>
							<span class="bgal-reorder__actions">
								<a href="<?php echo esc_url( get_edit_post_link( $section->ID ) ); ?>"><?php esc_html_e( 'Edit', 'bullet-gallery' ); ?></a>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>

				<p class="bgal-reorder__status" id="bgal-reorder-status" aria-live="polite"></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Persist the new order.
	 *
	 * @return void
	 */
	public static function handle_ajax() {
		check_ajax_referer( 'bullet_gallery_reorder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'bullet-gallery' ) ), 403 );
		}

		$raw = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised on the next line.

		if ( ! is_array( $raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Unexpected data.', 'bullet-gallery' ) ), 400 );
		}

		$order    = array_values( array_filter( array_map( 'absint', $raw ) ) );
		$position = 0;

		foreach ( $order as $post_id ) {
			if ( BULLET_GALLERY_CPT !== get_post_type( $post_id ) ) {
				continue;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			++$position;

			wp_update_post(
				array(
					'ID'         => $post_id,
					'menu_order' => $position,
				)
			);
		}

		wp_send_json_success( array( 'count' => $position ) );
	}
}
