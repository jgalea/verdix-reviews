<?php
/**
 * Plugin Name: Verdix Reviews
 * Description: Review boxes with structured data (JSON-LD) for WordPress. Star ratings, pros/cons, and schema markup that Google understands.
 * Version:     2.0.2
 * Author:      Jean Galea
 * Author URI:  https://jeangalea.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: verdix-reviews
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'VERDIX_VERSION', '2.0.2' );
define( 'VERDIX_FILE', __FILE__ );
define( 'VERDIX_DIR', plugin_dir_path( __FILE__ ) );
define( 'VERDIX_URL', plugin_dir_url( __FILE__ ) );
define( 'VERDIX_BASENAME', plugin_basename( __FILE__ ) );

require_once VERDIX_DIR . 'includes/meta.php';
require_once VERDIX_DIR . 'includes/hooks.php';
require_once VERDIX_DIR . 'includes/schema.php';
require_once VERDIX_DIR . 'includes/schema-check.php';
require_once VERDIX_DIR . 'includes/rest-api.php';
require_once VERDIX_DIR . 'includes/block.php';
require_once VERDIX_DIR . 'includes/render.php';
require_once VERDIX_DIR . 'includes/shortcode.php';

if ( is_admin() ) {
	require_once VERDIX_DIR . 'admin/settings.php';
	require_once VERDIX_DIR . 'admin/meta-box.php';
	require_once VERDIX_DIR . 'admin/migration.php';
}
