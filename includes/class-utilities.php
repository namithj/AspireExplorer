<?php
/**
 * The Class for Miscellaneous Helper Functions.
 *
 * @package aspire-explorer
 */

namespace AspireExplorer;

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
		// First, check in the child theme (if active)
		$theme_file_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'aspireexplorer' . DIRECTORY_SEPARATOR . $file;

		// If not found in child theme, check parent theme
		if ( ! file_exists( $theme_file_path ) && get_template_directory() !== get_stylesheet_directory() ) {
			$theme_file_path = get_template_directory() . DIRECTORY_SEPARATOR . 'aspireexplorer' . DIRECTORY_SEPARATOR . $file;
		}

		// If found in theme, use that file
		if ( file_exists( $theme_file_path ) ) {
			$file_path = $theme_file_path;
		} else {
			// Fall back to plugin's view folder
			$file_path = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $file;
		}

		if ( ( '' !== $file ) && file_exists( $file_path ) ) {
			//phpcs:disable
			// Usage of extract() is necessary in this content to simulate templating functionality.
			extract( $args );
			//phpcs:enable
			include $file_path;
		}
	}
}
