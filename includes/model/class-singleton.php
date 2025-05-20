<?php
/**
 * Class Singleton
 *
 * Inherit the Singleton pattern to yuur classes seamlessly.
 */

namespace AspireExplorer\Model;

abstract class Singleton {

	/**
	 * Hold the single instance of the class.
	 *
	 * @var static|null
	 */
	private static $instances = [];

	/**
	 * Protected constructor to prevent direct object creation.
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * Prevent cloning.
	 */
	final public function __clone() {
		return new \WP_Error( 'singleton_clone_error', __( 'Cloning a singleton is not allowed.', 'aspireexplorer' ) );
	}

	/**
	 * Prevent unserialization.
	 */
	final public function __wakeup() {
		return new \WP_Error( 'singleton_unserialize_error', __( 'Unserializing a singleton is not allowed.', 'aspireexplorer' ) );
	}

	/**
	 * Main access method.
	 *
	 * @return static
	 */
	final public static function get_instance() {
		$class = static::class;

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Initialization method to be overridden by subclass.
	 */
	protected function init() {
		// Override this in subclass
	}
}
