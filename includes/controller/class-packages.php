<?php
/**
 * Class Packages
 *
 * Base controller class for package management (themes and plugins).
 * Contains common functionality shared between themes and plugins controllers.
 */

namespace AspireExplorer\Controller;

class Packages {
	/**
	 * The page slug where the packages archive page should appear.
	 */
	protected $target_page_slug;

	/**
	 * Store the API response for use in multiple methods
	 */
	protected $api_response;

	/**
	 * Number of search results to show per page
	 */
	protected $default_search_results_per_page;

	/**
	 * Package type (themes or plugins)
	 */
	protected $asset_type;

	/**
	 * Query variable name for single package pages
	 */
	protected $asset_slug_var;

	/**
	 * Constructor.
	 *
	 * @param string $asset_type The package type ('themes' or 'plugins').
	 */
	public function __construct( $asset_type = 'plugins' ) {
		$this->asset_type = $asset_type;

		$root = defined( 'AE_ROOT' ) ? trim( AE_ROOT, '/' ) : '';
		if ( '' !== $root ) {
			$root .= '/';
		}

		$this->target_page_slug                = $root . $this->asset_type;
		$this->default_search_results_per_page = 10;
		$this->asset_slug_var                  = 'themes' === $this->asset_type ? 'theme_slug' : 'plugin_slug';

		add_filter( 'init', [ $this, 'init_permalinks' ] );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
		add_action( 'wp', [ $this, 'wp' ] );
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		add_filter( 'pre_get_document_title', [ $this, 'pre_get_document_title' ], 9999, 1 );
		add_filter( 'document_title_parts', [ $this, 'document_title_parts' ] );
		add_filter( 'the_title', [ $this, 'the_title' ] );
		add_filter( 'the_content', [ $this, 'the_content' ] );
		add_filter( 'get_canonical_url', [ $this, 'get_canonical_url' ], 10, 1 );
		add_filter( 'wpseo_canonical', [ $this, 'get_canonical_url' ], 10, 1 );
		add_filter( 'wpseo_opengraph_title', [ $this, 'wpseo_opengraph_title' ] );
		add_filter( 'wpseo_opengraph_desc', [ $this, 'wpseo_opengraph_desc' ] );
		add_filter( 'wpseo_opengraph_url', [ $this, 'wpseo_opengraph_url' ] );

		add_action( 'init', [ $this, 'download_package' ] );
	}

	/**
	 * Add rewrite rule
	 */
	public function init_permalinks() {
		add_rewrite_rule(
			'^' . $this->target_page_slug . '/([^/]+)/?$',
			'index.php?' . $this->asset_slug_var . '=$matches[1]',
			'top'
		);
	}

	/**
	 * Register query var
	 */
	public function query_vars( $vars ) {
		$vars[] = $this->asset_slug_var;
		return $vars;
	}

	/**
	 * Modify the main query to load the assets page for asset slugs
	 */
	public function pre_get_posts( \WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( empty( $this->target_page_slug ) ) {
			return;
		}

		$page = get_page_by_path( $this->target_page_slug );
		if ( ! $page ) {
			return;
		}

		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) ) {
			$query->set( 'post_type', 'page' );
			$query->set( 'page_id', (int) $page->ID );
			$query->set( 'posts_per_page', 1 );

			$query->is_page     = true;
			$query->is_singular = true;
			$query->is_home     = false;
			$query->is_archive  = false;
			$query->is_feed     = false;

			// If WP might try to redirect to canonical URL for the target page, disable it for this request
			add_filter( 'redirect_canonical', '__return_false' );
		}
	}

	/**
	 * Fetch API data early in the WordPress lifecycle
	 */
	public function wp() {
		if ( is_admin() ) {
			return;
		}

		if ( empty( $this->target_page_slug ) ) {
			return;
		}

		$asset_slug = get_query_var( $this->asset_slug_var );
		if (
			empty( $asset_slug ) &&
			! is_page( $this->target_page_slug )
		) {
			return;
		}

		if ( 'themes' === $this->asset_type ) {
			if ( ! function_exists( 'themes_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/theme.php';
			}
		} elseif ( 'plugins' === $this->asset_type ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
		}

		/**
		 * The individual asset page.
		 */
		if ( ! empty( $asset_slug ) ) {
			global $post;

			$this->api_response = call_user_func(
				'themes' === $this->asset_type ? 'themes_api' : 'plugins_api',
				'themes' === $this->asset_type ? 'theme_information' : 'plugin_information',
				[
					'slug'   => $asset_slug,
					'fields' => 'all',
				]
			);
			if ( false != $this->api_response && ! is_wp_error( $this->api_response ) ) {
				$post->post_title   = $this->api_response->name ?? '';
				$post->post_content = wp_strip_all_tags( $this->api_response->description ?? '' );
				$post->post_excerpt = wp_strip_all_tags( $this->api_response->description ?? '' );
			}
			return;
		}

		/**
		 * The assets archive / search page.
		 */
		if ( is_page( $this->target_page_slug ) ) {
			$search_args = [
				'page'     => max( 1, get_query_var( 'paged' ) ),
				'per_page' => $this->default_search_results_per_page,
			];

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
			if ( '' !== $search_keyword ) {
				$search_args['search'] = $search_keyword;
			} else {
				$search_args['browse'] = 'popular';
			}

			$this->api_response = call_user_func(
				'themes' === $this->asset_type ? 'themes_api' : 'plugins_api',
				'themes' === $this->asset_type ? 'query_themes' : 'query_plugins',
				$search_args
			);

			return;
		}
	}

	/**
	 * Filter the_title for asset page.
	 */
	public function the_title( $title ) {
		if ( ! in_the_loop() ) {
			return $title;
		}

		if ( ! is_page( $this->target_page_slug ) ) {
			return $title;
		}

		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) && isset( $this->api_response->name ) ) {
			return $this->api_response->name;
		}

		return $title;
	}

	/**
	 * Filter the document title for asset page. (Yoast SEO compatibility)
	 */
	public function pre_get_document_title( $title ) {
		// Check if Yoast SEO is activated
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			// Yoast SEO is active, let it handle the title
			return '';
		}

		return $title;
	}

	/**
	 * Filter the document title for asset page.
	 */
	public function document_title_parts( $title_parts ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return $title_parts;
		}

		if ( ! is_page( $this->target_page_slug ) ) {
			return $title_parts;
		}

		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) && isset( $this->api_response->name ) ) {
			$title_parts['title'] = $this->api_response->name;
		}

		return $title_parts;
	}

	/**
	 * Get canonical URL for asset page.
	 */
	public function get_canonical_url( $canonical ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return $canonical;
		}

		if ( ! is_page( $this->target_page_slug ) ) {
			return $canonical;
		}

		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) ) {
			$page = get_page_by_path( $this->target_page_slug );
			if ( $page ) {
				return get_permalink( $page->ID ) . $asset_slug . '/';
			}
		}
		return $canonical;
	}

	/**
	 * Get OpenGraph title for asset page.
	 */
	public function wpseo_opengraph_title( $title ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return $title;
		}
		if ( ! is_page( $this->target_page_slug ) ) {
			return $title;
		}
		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) && isset( $this->api_response->name ) ) {
			return $this->api_response->name;
		}
		return $title;
	}

	/**
	 * Get OpenGraph description for asset page.
	 */
	public function wpseo_opengraph_desc( $desc ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return $desc;
		}
		if ( ! is_page( $this->target_page_slug ) ) {
			return $desc;
		}
		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) && isset( $this->api_response->description ) ) {
			return wp_strip_all_tags( $this->api_response->description );
		}
		return $desc;
	}

	/**
	 * Get OpenGraph URL for asset page.
	 */
	public function wpseo_opengraph_url( $url ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return $url;
		}
		if ( ! is_page( $this->target_page_slug ) ) {
			return $url;
		}
		$asset_slug = get_query_var( $this->asset_slug_var );
		if ( ! empty( $asset_slug ) ) {
			$page = get_page_by_path( $this->target_page_slug );
			if ( $page ) {
				return get_permalink( $page->ID ) . $asset_slug . '/';
			}
		}
		return $url;
	}

	/**
	 * Filter the_content for assets page.
	 */
	public function the_content( $content ) {
		if ( ! is_page( $this->target_page_slug ) ) {
			return $content;
		}

		$global_var = 'GLOBALS[' . $this->asset_slug_var . ']';
		if ( isset( $GLOBALS[ $this->asset_slug_var ] ) ) {
			return $this->single_the_content( $GLOBALS[ $this->asset_slug_var ] );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
			return $this->archive_the_content( $search_keyword );
		}
	}

	/**
	 * The Archive / Asset Search page.
	 *
	 * @param string $search_keyword The search keyword.
	 * @return string The content to be displayed.
	 */
	protected function archive_the_content( $search_keyword ) {
		ob_start();

		Utilities::include_file(
			$this->asset_type . DIRECTORY_SEPARATOR . $this->asset_type . '-search-form.php',
			[
				'target_page_slug' => $this->target_page_slug,
				'search_keyword'   => $search_keyword,
			]
		);

		if ( is_wp_error( $this->api_response ) ) {
			echo wp_kses_post( wpautop( 'Error fetching ' . $this->asset_type . '. Please try again later.' ) );
			return ob_get_clean();
		}

		$results_property = $this->asset_type;
		if ( empty( $this->api_response->$results_property ) ) {
			echo wp_kses_post( wpautop( 'No ' . $this->asset_type . ' found for your search.' ) );
			return ob_get_clean();
		}

		Utilities::include_file(
			$this->asset_type . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $this->asset_type . '.php',
			[
				'target_page_slug'            => $this->target_page_slug,
				$this->asset_type . '_result' => $this->api_response->$results_property,
				'current_page'                => max( 1, get_query_var( 'paged' ) ),
				'total_results'               => $this->api_response->info['results'],
				'total_pages'                 => ceil( $this->api_response->info['results'] / $this->default_search_results_per_page ),
			]
		);

		return ob_get_clean();
	}

	/**
	 * The individual asset page.
	 *
	 * @param string $asset_slug The asset slug.
	 * @return string The content to be displayed.
	 */
	protected function single_the_content( $asset_slug ) {
		if ( is_wp_error( $this->api_response ) ) {
			return wp_kses_post( wpautop( 'Error fetching ' . rtrim( $this->asset_type, 's' ) . ' information. Please try again later.' ) );
		}

		ob_start();

		$model_class = 'themes' === $this->asset_type ? 'AspireExplorer\Model\ThemeInfo' : 'AspireExplorer\Model\PluginInfo';
		$asset_info  = new $model_class( $this->api_response );

		Utilities::include_file(
			$this->asset_type . DIRECTORY_SEPARATOR . 'single' . DIRECTORY_SEPARATOR . rtrim( $this->asset_type, 's' ) . '.php',
			[
				rtrim( $this->asset_type, 's' ) . '_info' => $asset_info,
			]
		);

		return ob_get_clean();
	}

	/**
	 * Handle download link requests.
	 */
	public function download_package() {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if (
			( ! isset( $_GET['ae_download'] ) && isset( $_GET['ae_package'] ) ) ||
			( ! isset( $_GET['ae_package'] ) && isset( $_GET['ae_download'] ) )
		) {
			wp_die( esc_html( __( 'Aspire Explorer Error: Malformed download URL', 'aspireexplorer' ) ) );
		}

		if (
			isset( $_GET['ae_download'] ) &&
			( '' !== sanitize_text_field( wp_unslash( $_GET['ae_download'] ) ) ) &&
			isset( $_GET['ae_package'] ) &&
			( '' !== sanitize_text_field( wp_unslash( $_GET['ae_package'] ) ) )
		) {
			if (
				! isset( $_GET['ae_nonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ae_nonce'] ) ), 'ae_download_nonce' )
			) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Unidentified download URL', 'aspireexplorer' ) ) );
			}

			$asset_url = isset( $_GET['ae_download'] ) ? sanitize_text_field( wp_unslash( $_GET['ae_download'] ) ) : '';
			$filename  = isset( $_GET['ae_package'] ) ? sanitize_text_field( wp_unslash( $_GET['ae_package'] ) ) : '';
			if ( '' === $asset_url ) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Missing download URL', 'aspireexplorer' ) ) );
			}

			if ( ! filter_var( $asset_url, FILTER_VALIDATE_URL ) ) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Invalid download URL', 'aspireexplorer' ) ) );
			}

			$accept_header = 'application/octet-stream';
			if ( str_contains( $asset_url, 'zipball' ) ) {
				$accept_header = 'application/zip';
			}
			if ( str_contains( $asset_url, 'tarball' ) ) {
				$accept_header = 'application/x-tar';
			}
			$response = wp_remote_get(
				$asset_url,
				[
					'headers' => [
						'Accept' => $accept_header ,
					],
					'timeout' => 60,
				]
			);
			if ( is_wp_error( $response ) ) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Unable to download the file. Please try again later.', 'aspireexplorer' ) ) );
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Unable to download the file. Please try again later.', 'aspireexplorer' ) . ' (HTTP Code: ' . esc_html( $code ) . ')' ) );
			}
			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				wp_die( esc_html( __( 'Aspire Explorer Error: Downloaded file is empty.', 'aspireexplorer' ) ) );
			}

			// Get file extension from response headers
			$headers   = wp_remote_retrieve_headers( $response );
			$extension = '';

			// Try to get extension from Content-Disposition header
			if ( isset( $headers['content-disposition'] ) ) {
				if ( preg_match( '/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $headers['content-disposition'], $matches ) ) {
					$header_filename = trim( $matches[1], '"\'' );
					$extension       = pathinfo( $header_filename, PATHINFO_EXTENSION );
				}
			}

			// Fallback: try to get extension from Content-Type header
			if ( empty( $extension ) && isset( $headers['content-type'] ) ) {
				$content_type = $headers['content-type'];
				if ( strpos( $content_type, 'application/zip' ) !== false ) {
					$extension = 'zip';
				} elseif ( strpos( $content_type, 'application/x-tar' ) !== false ) {
					$extension = 'tar';
				} elseif ( strpos( $content_type, 'application/gzip' ) !== false ) {
					$extension = 'gz';
				}
			}

			// Fallback: try to get extension from URL
			if ( empty( $extension ) ) {
				$url_path = wp_parse_url( $asset_url, PHP_URL_PATH );
				if ( $url_path ) {
					$extension = pathinfo( $url_path, PATHINFO_EXTENSION );
				}
			}

			// Add extension to filename if not already present
			if ( ! empty( $extension ) && ! str_ends_with( strtolower( $filename ), '.' . strtolower( $extension ) ) ) {
				$filename .= '.' . $extension;
			}

			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . strlen( $body ) );
			//phpcs:disable
			// Returning Binary data. Necesary in this scenario
			echo $body;
			//phpcs:enable
			exit;
		}
	}
}
