<?php

class Two_Factor_Core {

	const PROVIDER_USER_META_KEY = '_two_factor_provider';

	static function get_instance() {
		static $instance;
		$class = __CLASS__;
		if ( ! is_a( $instance, $class ) ) {
			$instance = new $class;
		}
		return $instance;
	}

	/**
	 * Class constructor.  Sets up filters and actions.
	 */
	private function __construct() {
		add_action( 'init',              array( $this, 'get_providers' ) );
	}

	/**
	 * For each provider, include it and then instantiate it.
	 *
	 * @return array
	 */
	function get_providers() {
		$providers = array(
			'Two_Factor_Email'    => TWO_FACTOR_DIR . 'providers/class.two-factor-email.php',
			'Two_Factor_Totp'     => TWO_FACTOR_DIR . 'providers/class.two-factor-totp.php',
			'Two_Factor_Fido_U2f' => TWO_FACTOR_DIR . 'providers/class.two-factor-fido-u2f.php',
			'Two_Factor_Dummy'    => TWO_FACTOR_DIR . 'providers/class.two-factor-dummy.php',
		);

		/**
		 * Filter the supplied providers.
		 *
		 * This lets third-parties either remove providers (such as Email), or
		 * add their own providers (such as text message or Clef).
		 *
		 * @param array $providers A key-value array where the key is the class name, and
		 *                         the value is the path to the file containing the class.
		 */
		$providers = apply_filters( 'two_factor_providers', $providers );

		/**
		 * For each filtered provider,
		 */
		foreach ( $providers as $class => $path ) {
			include_once( $path );

			/**
			 * Confirm that it's been successfully included before instantiating.
			 */
			if ( class_exists( $class ) ) {
				$providers[ $class ] = call_user_func( array( $class, 'get_instance' ) );
			}
		}

		return $providers;
	}

	/**
	 * Gets the Two-Factor Auth provider for the specified|current user.
	 *
	 * @param $user_id optional
	 *
	 * $return object|null
	 */
	function get_provider_for_user( $user_id = null ) {
		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$provider = get_user_meta( $user_id, self::PROVIDER_USER_META_KEY, true );
		$providers = self::get_providers();

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		}

		return null;
	}
}
