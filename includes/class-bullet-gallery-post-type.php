<?php
/**
 * Registers the gallery section post type.
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post type handler.
 */
class Bullet_Gallery_Post_Type {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . BULLET_GALLERY_CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . BULLET_GALLERY_CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Bullet Gallery', 'bullet-gallery' ),
			'singular_name'      => __( 'Gallery Section', 'bullet-gallery' ),
			'menu_name'          => __( 'Bullet Gallery', 'bullet-gallery' ),
			'add_new'            => __( 'Add Section', 'bullet-gallery' ),
			'add_new_item'       => __( 'Add Gallery Section', 'bullet-gallery' ),
			'edit_item'          => __( 'Edit Gallery Section', 'bullet-gallery' ),
			'new_item'           => __( 'New Gallery Section', 'bullet-gallery' ),
			'view_item'          => __( 'View Gallery Section', 'bullet-gallery' ),
			'search_items'       => __( 'Search Gallery Sections', 'bullet-gallery' ),
			'not_found'          => __( 'No gallery sections yet.', 'bullet-gallery' ),
			'not_found_in_trash' => __( 'No gallery sections in Trash.', 'bullet-gallery' ),
			'all_items'          => __( 'All Sections', 'bullet-gallery' ),
		);

		register_post_type(
			BULLET_GALLERY_CPT,
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 26,
				'menu_icon'       => 'dashicons-format-gallery',
				'supports'        => array( 'title', 'page-attributes' ),
				'has_archive'     => false,
				'rewrite'         => false,
				'query_var'       => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Replace the default title placeholder.
	 *
	 * @param string  $text Placeholder text.
	 * @param WP_Post $post Current post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post instanceof WP_Post && BULLET_GALLERY_CPT === $post->post_type ) {
			return __( 'Section name, e.g. Food', 'bullet-gallery' );
		}
		return $text;
	}

	/**
	 * Admin list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['bgal_preview'] = __( 'Preview', 'bullet-gallery' );
				$new['bgal_photos']  = __( 'Photos', 'bullet-gallery' );
				$new['bgal_anchor']  = __( 'Anchor', 'bullet-gallery' );
			}
		}
		unset( $new['date'] );
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'bgal_photos' === $column ) {
			echo esc_html( (string) count( Bullet_Gallery_Meta::get_image_ids( $post_id ) ) );
			return;
		}

		if ( 'bgal_anchor' === $column ) {
			$post = get_post( $post_id );
			echo '<code>#' . esc_html( $post ? $post->post_name : '' ) . '</code>';
			return;
		}

		if ( 'bgal_preview' === $column ) {
			$shown = array_slice( Bullet_Gallery_Meta::get_image_ids( $post_id ), 0, 4 );
			if ( empty( $shown ) ) {
				echo '<span aria-hidden="true">&mdash;</span>';
				return;
			}
			echo '<span class="bgal-col-preview">';
			foreach ( $shown as $id ) {
				echo wp_get_attachment_image( (int) $id, array( 40, 40 ), false, array( 'class' => 'bgal-col-thumb' ) );
			}
			echo '</span>';
		}
	}
}
