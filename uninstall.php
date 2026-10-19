<?php
/**
 * Removes plugin options when the plugin is deleted.
 *
 * @package BulletGallery
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bullet_gallery_settings' );
