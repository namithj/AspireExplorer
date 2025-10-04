<?php
/**
 * Class Playground
 *
 * The Controller for WordPress Playground Blueprint API endpoint.
 * Handles REST API endpoints for generating WordPress Playground blueprints.
 */

namespace AspireExplorer\Controller;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Exception;

class Playground extends \AspireExplorer\Model\Singleton {

	/**
	 * REST API namespace
	 */
	const API_NAMESPACE = 'aspireexplorer/v1';

	/**
	 * REST API endpoint
	 */
	const API_ENDPOINT = 'playground/blueprint';

	/**
	 * Constructor.
	 */
	protected function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::API_NAMESPACE,
			self::API_ENDPOINT,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'generate_blueprint' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => $this->get_endpoint_args(),
			]
		);
	}

	/**
	 * Define REST endpoint arguments with validation
	 *
	 * @return array
	 */
	private function get_endpoint_args() {
		return [
			'theme'                  => [
				'description'       => 'Theme download URL for WordPress Playground',
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => [ $this, 'validate_theme_url' ],
				'sanitize_callback' => [ $this, 'sanitize_theme_url' ],
			],
			'plugin'                 => [
				'description'       => 'Plugin download URL for WordPress Playground',
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => [ $this, 'validate_plugin_url' ],
				'sanitize_callback' => [ $this, 'sanitize_plugin_url' ],
			],
			'landing_page'           => [
				'description'       => 'Landing page for the playground',
				'type'              => 'string',
				'required'          => false,
				'default'           => '/',
				'validate_callback' => [ $this, 'validate_landing_page' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'activate'               => [
				'description' => 'Whether to activate the theme/plugin',
				'type'        => 'boolean',
				'required'    => false,
				'default'     => true,
			],
			'import_starter_content' => [
				'description' => 'Whether to import starter content for themes',
				'type'        => 'boolean',
				'required'    => false,
				'default'     => true,
			],
		];
	}

	/**
	 * Check permissions for the REST endpoint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// Allow public access for blueprint generation
		// Add additional permission checks if needed
		return true;
	}

	/**
	 * Generate WordPress Playground blueprint
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_blueprint( $request ) {
		try {
			$theme_url              = $request->get_param( 'theme' );
			$plugin_url             = $request->get_param( 'plugin' );
			$landing_page           = $request->get_param( 'landing_page' );
			$landing_page           = ! empty( $landing_page ) ? $landing_page : '/';
			$activate               = $request->get_param( 'activate' ) !== false;
			$import_starter_content = $request->get_param( 'import_starter_content' ) !== false;

			// Validate that at least one asset URL is provided
			if ( empty( $theme_url ) && empty( $plugin_url ) ) {
				return new WP_Error(
					'missing_asset_url',
					'Either theme or plugin URL must be provided',
					[ 'status' => 400 ]
				);
			}

			$blueprint = $this->build_blueprint_json(
				[
					'theme_url'              => $theme_url,
					'plugin_url'             => $plugin_url,
					'landing_page'           => $landing_page,
					'activate'               => $activate,
					'import_starter_content' => $import_starter_content,
				]
			);

			$response = new WP_REST_Response( $blueprint );
			$response->set_headers(
				[
					'Content-Type'                 => 'application/json',
					'Access-Control-Allow-Origin'  => '*',
					'Access-Control-Allow-Methods' => 'GET',
					'Access-Control-Allow-Headers' => 'Content-Type',
				]
			);

			return $response;

		} catch ( Exception $e ) {
			return new WP_Error(
				'blueprint_generation_failed',
				'Failed to generate blueprint: ' . $e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Build the blueprint JSON structure
	 *
	 * @param array $params Blueprint parameters.
	 * @return array
	 */
	private function build_blueprint_json( $params ) {
		$blueprint = [
			'$schema'     => 'https://playground.wordpress.net/blueprint-schema.json',
			'landingPage' => $params['landing_page'],
			'features'    => [
				'networking' => true,
			],
			'steps'       => [],
		];

		// Add theme installation step
		if ( ! empty( $params['theme_url'] ) ) {
			$blueprint['steps'][] = [
				'step'      => 'installTheme',
				'themeData' => [
					'resource' => 'url',
					'url'      => $params['theme_url'],
				],
				'options'   => [
					'activate'             => $params['activate'],
					'importStarterContent' => $params['import_starter_content'],
				],
			];
		}

		// Add plugin installation step
		if ( ! empty( $params['plugin_url'] ) ) {
			$blueprint['steps'][] = [
				'step'       => 'installPlugin',
				'pluginData' => [
					'resource' => 'url',
					'url'      => $params['plugin_url'],
				],
				'options'    => [
					'activate' => $params['activate'],
				],
			];
		}

		return $blueprint;
	}

	/**
	 * Validate theme URL
	 *
	 * @param string $value URL to validate.
	 * @return bool
	 */
	public function validate_theme_url( $value ) {
		if ( empty( $value ) ) {
			return true; // Allow empty for optional parameter
		}

		return $this->validate_download_url( $value, 'theme' );
	}

	/**
	 * Validate plugin URL
	 *
	 * @param string $value URL to validate.
	 * @return bool
	 */
	public function validate_plugin_url( $value ) {
		if ( empty( $value ) ) {
			return true; // Allow empty for optional parameter
		}

		return $this->validate_download_url( $value, 'plugin' );
	}

	/**
	 * Validate download URL for themes or plugins
	 *
	 * @param string $url URL to validate.
	 * @param string $type Type of asset (theme|plugin).
	 * @return bool
	 */
	private function validate_download_url( $url, $type ) {
		// Basic URL validation
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Check if URL uses HTTPS
		if ( strpos( $url, 'https://' ) !== 0 ) {
			return false;
		}

		// Check for valid file extension
		$valid_extensions = [ '.zip' ];
		$url_path         = wp_parse_url( $url, PHP_URL_PATH );
		$extension        = strtolower( pathinfo( $url_path, PATHINFO_EXTENSION ) );

		if ( ! in_array( '.' . $extension, $valid_extensions, true ) ) {
			return false;
		}

		// Additional security checks
		$blocked_domains = [
			'localhost',
			'127.0.0.1',
			'0.0.0.0',
		];

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( in_array( $host, $blocked_domains, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate landing page URL
	 *
	 * @param string $value Landing page to validate.
	 * @return bool
	 */
	public function validate_landing_page( $value ) {
		if ( empty( $value ) ) {
			return true;
		}

		// Must start with /
		if ( strpos( $value, '/' ) !== 0 ) {
			return false;
		}

		// Basic path validation
		return preg_match( '/^\/[a-zA-Z0-9\/_-]*$/', $value );
	}

	/**
	 * Sanitize theme URL
	 *
	 * @param string $value URL to sanitize.
	 * @return string
	 */
	public function sanitize_theme_url( $value ) {
		return $this->sanitize_download_url( $value );
	}

	/**
	 * Sanitize plugin URL
	 *
	 * @param string $value URL to sanitize.
	 * @return string
	 */
	public function sanitize_plugin_url( $value ) {
		return $this->sanitize_download_url( $value );
	}

	/**
	 * Sanitize download URL
	 *
	 * @param string $url URL to sanitize.
	 * @return string
	 */
	private function sanitize_download_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		// Remove any HTML entities and trim
		$url = html_entity_decode( $url, ENT_QUOTES, 'UTF-8' );
		$url = trim( $url );

		// Basic URL sanitization
		return esc_url_raw( $url );
	}

	/**
	 * Get the REST API endpoint URL
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	public static function get_endpoint_url( $params = [] ) {
		$base_url = rest_url( self::API_NAMESPACE . '/' . self::API_ENDPOINT );

		if ( ! empty( $params ) ) {
			$base_url = add_query_arg( $params, $base_url );
		}

		return $base_url;
	}

	/**
	 * Generate blueprint URL for WordPress Playground
	 *
	 * @param array $params Blueprint parameters.
	 * @return string
	 */
	public static function get_playground_url( $params = [] ) {
		$blueprint_url = self::get_endpoint_url( $params );
		return 'https://playground.wordpress.net/?blueprint-url=' . rawurlencode( $blueprint_url ) . '&random=' . time();
	}
}
