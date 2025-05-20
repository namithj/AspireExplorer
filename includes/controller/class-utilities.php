<?php
/**
 * The Class for Miscellaneous Helper Functions.
 *
 * @package aspire-explorer
 */

namespace AspireExplorer\Controller;

/**
 * The Class for Miscellaneous Helper Functions.
 */
class Utilities {
	/**
	 * Return the content of the File after processing.
	 *
	 * @param string $file File name.
	 * @param array  $args Data to pass to the file.
	 */
	public static function include_file( $file, $args = [] ) {
		$file_path = AE_DIR_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $file;
		if ( ( '' !== $file ) && file_exists( $file_path ) ) {
			//phpcs:disable
			// Usage of extract() is necessary in this content to simulate templating functionality.
			extract( $args );
			//phpcs:enable
			include $file_path;
		}
	}
}
