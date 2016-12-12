<?php
// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'tribe_call_private_method' ) ) {
	/**
	 * Calls a private/protected method in any class
	 *
	 * Only use this as a last resort. Private methods are not intended to
	 * be accessed, and can change functionality on any update.
	 *
	 * To call Tribe__Class::instance()->set_something( $var1, $var2 ) do this:
	 * tribe_call_private_method(
	 *      Tribe__Class::instance(),
	 *      'set_something',
	 *      array( $var1, $var2 )
	 * );
	 *
	 * @param object $instance The class instance with the private method
	 * @param string $method   Name of the method
	 * @param array  $args     Any args to pass to this method
	 *
	 * @return mixed|exception Returns method or exception on PHP 5.2
	 */
	function tribe_call_private_method( $instance, $method, $args = array() ) {
		if ( version_compare( PHP_VERSION, '5.3.2', '<' ) ) {
			$exception = new Exception( 'This function requires PHP 5.3.2 or newer.' );
			_doing_it_wrong( __FUNCTION__, $exception->getMessage(), 'N/A' );
			return $exception;
		}

		if ( ! is_array( $args ) ) {
			$args = array( $args );
		}

		$reflection_method = new ReflectionMethod( get_class( $instance ), $method );
		$reflection_method->setAccessible( true );
		return $reflection_method->invokeArgs( $instance, $args );
	}
}