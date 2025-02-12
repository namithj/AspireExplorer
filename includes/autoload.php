<?php
/**
 * The Autoloader.
 *
 * @package aspire-explorer
 */

spl_autoload_register( 'aspire_explorer_autoloader' );

/**
 * The Class Autoloader.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function aspire_explorer_autoloader( $class_name ) {
	if ( false !== strpos( $class_name, 'AspireExplorer\\' ) ) {
		$class_name = strtolower( str_replace( [ 'AspireExplorer\\', '_' ], [ '', '-' ], $class_name ) );
		$file       = __DIR__ . DIRECTORY_SEPARATOR . 'class-' . $class_name . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
