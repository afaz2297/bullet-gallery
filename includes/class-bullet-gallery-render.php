<?php
/**
 * Front end output.
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode and markup.
 */
class Bullet_Gallery_Render {

	/**
	 * Whether a gallery was rendered on this request.
	 *
	 * @var bool
	 */
	protected static $rendered = false;

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'bullet_gallery', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register (but do not enqueue) the front end assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style(
			'bullet-gallery',
			BULLET_GALLERY_URL . 'assets/css/bullet-gallery.css',
			array(),
			BULLET_GALLERY_VERSION
		);

		wp_register_script(
			'bullet-gallery',
			BULLET_GALLERY_URL . 'assets/js/bullet-gallery.js',
			array(),
			BULLET_GALLERY_VERSION,
			true
		);

		wp_localize_script(
			'bullet-gallery',
			'bulletGalleryL10n',
			array(
				'close'    => __( 'Close', 'bullet-gallery' ),
				'previous' => __( 'Previous photo', 'bullet-gallery' ),
				'next'     => __( 'Next photo', 'bullet-gallery' ),
				/* translators: 1: current photo number, 2: total photos. */
				'counter'  => __( '%1$s of %2$s', 'bullet-gallery' ),
			)
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'ids'      => '',
				'slug'     => '',
				'lightbox' => '',
				'count'    => '',
			),
			$atts,
			'bullet_gallery'
		);

		$sections = self::query_sections( $atts );

		if ( empty( $sections ) ) {
			return '';
		}

		wp_enqueue_style( 'bullet-gallery' );

		$settings = Bullet_Gallery_Settings::get();

		if ( '' !== $atts['lightbox'] ) {
			$settings['lightbox'] = in_array( strtolower( $atts['lightbox'] ), array( 'yes', 'true', '1', 'on' ), true ) ? 1 : 0;
		}

		if ( '' !== $atts['count'] ) {
			$settings['show_count'] = in_array( strtolower( $atts['count'] ), array( 'yes', 'true', '1', 'on' ), true ) ? 1 : 0;
		}

		wp_enqueue_script( 'bullet-gallery' );

		$html = self::render_sections( $sections, $settings );

		self::$rendered = true;

		return $html;
	}

	/**
	 * Resolve which sections to show.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return WP_Post[]
	 */
	protected static function query_sections( $atts ) {
		$args = array(
			'post_type'        => BULLET_GALLERY_CPT,
			'post_status'      => 'publish',
			'numberposts'      => 200,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
			),
			'suppress_filters' => false,
		);

		if ( '' !== $atts['ids'] ) {
			$ids = array_values( array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) ) );

			if ( empty( $ids ) ) {
				return array();
			}

			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
			unset( $args['order'] );
		}

		if ( '' !== $atts['slug'] ) {
			$slugs = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $atts['slug'] ) ) ) );

			if ( empty( $slugs ) ) {
				return array();
			}

			$args['post_name__in'] = $slugs;
		}

		return get_posts( $args );
	}

	/**
	 * Build the markup for a set of sections.
	 *
	 * @param WP_Post[] $sections Sections.
	 * @param array     $settings Settings.
	 * @return string
	 */
	protected static function render_sections( $sections, $settings ) {
		$ratios = Bullet_Gallery_Settings::ratios();
		$ratio  = isset( $ratios[ $settings['ratio'] ] ) ? $ratios[ $settings['ratio'] ]['css'] : '4 / 3';

		$root_vars = sprintf(
			'--bgal-accent:%1$s;--bgal-heading:%2$s;--bgal-text:%3$s;--bgal-card:%4$dpx;--bgal-gap:%5$dpx;--bgal-max:%6$dpx;--bgal-ratio:%7$s;',
			esc_attr( $settings['accent'] ),
			esc_attr( $settings['heading'] ),
			esc_attr( $settings['text'] ),
			(int) $settings['card_width'],
			(int) $settings['gap'],
			(int) $settings['max_width'],
			esc_attr( $ratio )
		);

		$schema_items = array();
		$out          = '<div class="bgal" style="' . esc_attr( $root_vars ) . '">';
		$image_index  = 0;

		foreach ( $sections as $section ) {
			$ids = Bullet_Gallery_Meta::get_image_ids( $section->ID );

			if ( empty( $ids ) ) {
				continue;
			}

			$out .= self::render_section( $section, $ids, $settings, $image_index, $schema_items );
		}

		$out .= '</div>';

		$out .= self::schema( $schema_items );

		return $out;
	}

	/**
	 * Build one section.
	 *
	 * @param WP_Post $section      Section post.
	 * @param int[]   $ids          Attachment IDs.
	 * @param array   $settings     Settings.
	 * @param int     $image_index  Running image counter, by reference.
	 * @param array   $schema_items Schema buffer, by reference.
	 * @return string
	 */
	protected static function render_section( $section, $ids, $settings, &$image_index, &$schema_items ) {
		$bg      = get_post_meta( $section->ID, Bullet_Gallery_Meta::KEY_BG, true );
		$heading = get_post_meta( $section->ID, Bullet_Gallery_Meta::KEY_HEADING, true );
		$text    = get_post_meta( $section->ID, Bullet_Gallery_Meta::KEY_TEXT, true );
		$caption = get_post_meta( $section->ID, Bullet_Gallery_Meta::KEY_OVERLAY, true );
		$intro   = get_post_meta( $section->ID, Bullet_Gallery_Meta::KEY_INTRO, true );

		$caption = in_array( $caption, array( 'hover', 'always', 'none' ), true ) ? $caption : 'hover';

		if ( ! $bg && $settings['background'] ) {
			$bg = $settings['background'];
		}

		$style = '';

		if ( $bg ) {
			$style .= '--bgal-bg:' . $bg . ';';
		}

		if ( $heading ) {
			$style .= '--bgal-heading:' . $heading . ';';
		}

		if ( $text ) {
			$style .= '--bgal-text:' . $text . ';';
		}

		$total = count( $ids );

		$out  = '<section class="bgal-section bgal-section--cap-' . esc_attr( $caption ) . '"';
		$out .= ' id="' . esc_attr( $section->post_name ) . '"';

		if ( $style ) {
			$out .= ' style="' . esc_attr( $style ) . '"';
		}

		$out .= ' aria-labelledby="bgal-title-' . esc_attr( (string) $section->ID ) . '">';
		$out .= '<div class="bgal-inner">';

		$out .= '<div class="bgal-head">';
		$out .= '<h2 class="bgal-title" id="bgal-title-' . esc_attr( (string) $section->ID ) . '">';
		$out .= '<span class="bgal-mark" aria-hidden="true"></span>';
		$out .= esc_html( get_the_title( $section ) );
		$out .= '</h2>';

		if ( $settings['show_count'] ) {
			$out .= '<span class="bgal-count">';
			$out .= esc_html(
				sprintf(
					/* translators: %d: number of photos. */
					_n( '%d photo', '%d photos', $total, 'bullet-gallery' ),
					$total
				)
			);
			$out .= '</span>';
		}

		$out .= '</div>';

		if ( $intro ) {
			$out .= '<p class="bgal-intro">' . esc_html( $intro ) . '</p>';
		}

		$out .= '<div class="bgal-track" data-bgal-track>';

		foreach ( $ids as $id ) {
			$out .= self::render_item( (int) $id, $settings, $caption, $image_index, $schema_items );
		}

		$out .= '</div>';

		$out .= '<div class="bgal-nav">';
		$out .= '<button type="button" class="bgal-arrow bgal-arrow--prev" data-bgal-prev aria-label="' . esc_attr__( 'Scroll left', 'bullet-gallery' ) . '"><span aria-hidden="true">&#8592;</span></button>';
		$out .= '<div class="bgal-bar" aria-hidden="true"><span class="bgal-bar__fill" data-bgal-fill></span></div>';
		$out .= '<button type="button" class="bgal-arrow bgal-arrow--next" data-bgal-next aria-label="' . esc_attr__( 'Scroll right', 'bullet-gallery' ) . '"><span aria-hidden="true">&#8594;</span></button>';
		$out .= '</div>';

		$out .= '</div></section>';

		return $out;
	}

	/**
	 * Build one photo.
	 *
	 * @param int    $id           Attachment ID.
	 * @param array  $settings     Settings.
	 * @param string $caption_mode How captions behave in this section.
	 * @param int    $image_index  Running image counter, by reference.
	 * @param array  $schema_items Schema buffer, by reference.
	 * @return string
	 */
	protected static function render_item( $id, $settings, $caption_mode, &$image_index, &$schema_items ) {
		$full = wp_get_attachment_image_src( $id, 'full' );

		if ( ! $full ) {
			return '';
		}

		$caption = wp_get_attachment_caption( $id );
		$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$label   = $caption ? $caption : $alt;

		$attr = array(
			'class'    => 'bgal-img',
			'sizes'    => sprintf( '(max-width: 767px) 78vw, %dpx', (int) $settings['card_width'] ),
			'decoding' => 'async',
		);

		if ( $image_index < 2 ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
		} else {
			$attr['loading'] = 'lazy';
		}

		$img = wp_get_attachment_image( $id, 'large', false, $attr );

		if ( ! $img ) {
			return '';
		}

		++$image_index;

		$schema_items[] = array(
			'url'     => $full[0],
			'caption' => $label,
			'width'   => isset( $full[1] ) ? (int) $full[1] : 0,
			'height'  => isset( $full[2] ) ? (int) $full[2] : 0,
		);

		$tag   = $settings['lightbox'] ? 'a' : 'figure';
		$open  = '<' . $tag . ' class="bgal-item"';

		if ( $settings['lightbox'] ) {
			$open .= ' href="' . esc_url( $full[0] ) . '"';
			$open .= ' data-bgal-full="' . esc_url( $full[0] ) . '"';
			$open .= ' data-bgal-caption="' . esc_attr( $label ) . '"';
		}

		$open .= '>';

		$out  = $open;
		$out .= $img;

		if ( $label && 'none' !== $caption_mode ) {
			$out .= '<span class="bgal-cap">' . esc_html( $label ) . '</span>';
		}

		$out .= '</' . $tag . '>';

		return $out;
	}

	/**
	 * Build the ImageGallery JSON-LD block.
	 *
	 * @param array $items Schema items.
	 * @return string
	 */
	protected static function schema( $items ) {
		if ( empty( $items ) ) {
			return '';
		}

		$images = array();

		foreach ( $items as $item ) {
			$image = array(
				'@type'      => 'ImageObject',
				'contentUrl' => $item['url'],
			);

			if ( $item['caption'] ) {
				$image['caption'] = $item['caption'];
			}

			if ( $item['width'] ) {
				$image['width'] = $item['width'];
			}

			if ( $item['height'] ) {
				$image['height'] = $item['height'];
			}

			$images[] = $image;
		}

		$data = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'ImageGallery',
			'name'         => wp_get_document_title(),
			'associatedMedia' => $images,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>';
	}
}
