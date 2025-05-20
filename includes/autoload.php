<?php
/**
 * The Autoloader.
 *
 * @package aspire-explorer
 */

/**
 * The Class Autoloader.
 *
 * @param string $class_identifier The name and path of the class to load.
 * @return void
 */
spl_autoload_register(
	function ( $class_identifier ) {
		$prefix   = 'AspireExplorer\\';
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		if ( strpos( $class_identifier, $prefix ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_identifier, strlen( $prefix ) );
		$parts          = explode( '\\', $relative_class );
		$class_name     = array_pop( $parts );
		$file_name      = 'class-' . strtolower( $class_name ) . '.php';
		$sub_path       = implode( DIRECTORY_SEPARATOR, array_map( 'strtolower', $parts ) );
		$file           = $base_dir . ( $sub_path ? $sub_path . DIRECTORY_SEPARATOR : '' ) . $file_name;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
