<?php
/**
 * Plugin Name:          Bullet Gallery
 * Plugin URI:           https://github.com/afaz2297/bullet-gallery
 * Description:          Build a photo gallery page out of named sections. Each section is a horizontal, swipeable row of uniform images with its own background colour, drag-and-drop ordering, a built-in lightbox and clean SEO markup.
 * Version:              1.0.1
 * Requires at least:    6.5
 * Tested up to:         7.0
 * Requires PHP:         7.4
 * Author:               Afaz Alif
 * Author URI:           https://www.linkedin.com/in/afaz-alif/
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          bullet-gallery
 * Domain Path:          /languages
 *
 * @package BulletGallery
 */

defined( 'ABSPATH' ) || exit;

define( 'BULLET_GALLERY_VERSION', '1.0.1' );
define( 'BULLET_GALLERY_FILE', __FILE__ );
define( 'BULLET_GALLERY_DIR', plugin_dir_path( __FILE__ ) );
define( 'BULLET_GALLERY_URL', plugin_dir_url( __FILE__ ) );
define( 'BULLET_GALLERY_CPT', 'bullet_gallery' );

require_once BULLET_GALLERY_DIR . 'includes/class-bullet-gallery-post-type.php';
require_once BULLET_GALLERY_DIR . 'includes/class-bullet-gallery-meta.php';
require_once BULLET_GALLERY_DIR . 'includes/class-bullet-gallery-settings.php';
require_once BULLET_GALLERY_DIR . 'includes/class-bullet-gallery-reorder.php';
require_once BULLET_GALLERY_DIR . 'includes/class-bullet-gallery-render.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function bullet_gallery_init() {
	Bullet_Gallery_Post_Type::init();
	Bullet_Gallery_Meta::init();
	Bullet_Gallery_Settings::init();
	Bullet_Gallery_Reorder::init();
	Bullet_Gallery_Render::init();
}
add_action( 'plugins_loaded', 'bullet_gallery_init' );

/**
 * Register the post type on activation so permalinks are flushed once.
 *
 * @return void
 */
function bullet_gallery_activate() {
	Bullet_Gallery_Post_Type::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bullet_gallery_activate' );

/**
 * Clean up rewrite rules on deactivation.
 *
 * @return void
 */
function bullet_gallery_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bullet_gallery_deactivate' );
