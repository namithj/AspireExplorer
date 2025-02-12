<?php
/**
 * AspireExplorer - AspirePress Repository browser.
 *
 * @package     aspire-explorer
 * @author      AspireExplorer
 * @copyright   AspireExplorer
 * @license     GPLv2
 *
 * Plugin Name:       AspireExplorer
 * Plugin URI:        https://aspirepress.org/
 * Description:       AspirePress Repository browser.
 * Version:           0.1
 * Author:            AspirePress
 * Author URI:        https://docs.aspirepress.org/aspireexplorer/
 * Requires at least: 5.3
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Text Domain:       aspireexplorer
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/aspirepress/aspireexplorer
 * Primary Branch:    main
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'AE_VERSION' ) ) {
	define( 'AE_VERSION', '0.1' );
}


add_action( 'plugins_loaded', 'define_constant' );
function define_constant() {
	if ( ! defined( 'AE_PATH' ) ) {
		define( 'AE_PATH', dirname( plugin_basename( __FILE__ ) ) );
	}
}

require_once __DIR__ . '/includes/autoload.php';
