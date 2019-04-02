<?php
/**
 *    Copyright (c) 2019. - Eighty / 20 Results by Wicked Strong Chicks. ALL RIGHTS RESERVED
 *
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Member_Directory\Settings;


use E20R\Utilities\Utilities;

/**
 * Class Options
 * @package E20R\Member_Directory\Settings
 */
class Options {
	
	/**
	 * The option name in the wp_options table for this plugin
	 *
	 * @var string $option_name
	 */
	public static $option_name = 'e20r_directory';
	/**
	 * Instance of the current class (singleton)
	 *
	 * @var null|Options
	 */
	private static $instance = null;
	/**
	 * The list of Profile and Directory page settings
	 *
	 * @var array $page_pairs
	 */
	private $page_pairs = array();
	
	
	/**
	 * Options constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Update the Option (parameter) with the specified value
	 *
	 * @param string $parameter
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public static function set( $parameter, $value ) {
		
		$class = self::getInstance();
		
		if ( false === $class->isValidParameter( $parameter ) ) {
			return false;
		}
		
		$class->{$parameter} = $value;
		
		return true;
	}
	
	/**
	 * Get or instantiate and get the current class (singleton)
	 *
	 * @return Options
	 */
	public static function getInstance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->loadOptions();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load the options from persistent storage
	 *
	 * @return bool
	 */
	private function loadOptions() {
		
		$options = get_option( self::$option_name, false );
		
		if ( empty( $options ) ) {
			return false;
		}
		
		foreach ( $options as $parameter => $value ) {
			
			if ( false === $this->isValidParameter( $parameter ) ) {
				continue;
			}
			
			$this->{$parameter} = $value;
		}
		
		return true;
	}
	
	/**
	 * Validate the specified parameter as a class member
	 *
	 * @param string $parameter
	 *
	 * @return bool
	 */
	private function isValidParameter( $parameter ) {
		
		return (bool) property_exists( $this, $parameter );
	}
	
	/**
	 * Return the Directory Page ID that is linked to the profile page ID
	 *
	 * @param int|string $profile_page_id
	 *
	 * @return bool|mixed
	 */
	public static function getDirectoryIDFromProfile( $profile_page_id = 'default' ) {
		
		$class = self::getInstance();
		$utils = Utilities::get_instance();
		
		/**
		 * @var @var array( $directory_page_id => array( 'directory' => $directory_page_id, 'profile' => $profile_page_id ) );
		 */
		$page_pairs = $class->page_pairs;
		$location   = null;
		
		$utils->log( "Page pairs are: " . print_r( $page_pairs, true ) );

		if ( 'default' === $profile_page_id ) {
			$utils->log( "Looking for the default directory page..." );
			
			return $page_pairs['default']['directory'];
		}
		
		// Find the profile page ID in the settings (if possible)
		foreach ( $page_pairs as $directory_page_id => $settings ) {
			
			if ( 'default' === $directory_page_id ) {
				continue;
			}
			
			if ( (int) $profile_page_id === (int) $settings['profile'] ) {
				$utils->log("Found the matching profile ID for the supplied argument: {$profile_page_id}");
				$location = $settings['directory'];
				break;
			}
		}
		
		if ( empty( $location ) ) {
			$utils->log( "Directory page is not found for profile page ID {$profile_page_id}" );
			
			return false;
		}
		
		return $page_pairs[ $location ]['directory'];
		
	}
	
	/**
	 * Locate the profile page that is linked to the profile page ID
	 *
	 * @param int|string $directory_page_id
	 *
	 * @return bool
	 */
	public static function getProfileIDFromDirectory( $directory_page_id = 'default' ) {
		
		$class = self::getInstance();
		$utils = Utilities::get_instance();
		
		/**
		 * @var array( $directory_page_id => array( 'directory' => $directory_page_id, 'profile' => $profile_page_id ) );
		 */
		$page_pairs = $class->page_pairs;
		
		if ( 'default' === $directory_page_id ) {
			$utils->log( "Looking for the default profile page..." );
			
			return $page_pairs['default']['profile'];
		}
		
		if ( false === array_key_exists( $directory_page_id, $page_pairs ) ) {
			$utils->log( "Profile page ID is not found for directory page {$directory_page_id}" );
			
			return false;
		}
		
		return $page_pairs[ $directory_page_id ]['profile'];
	}
	
	/**
	 * Return the parameter value (if the parameter exists)
	 *
	 * @param string $parameter
	 *
	 * @return bool|mixed
	 */
	public static function get( $parameter ) {
		
		$class = self::getInstance();
		
		if ( false === $class->isValidParameter( $parameter ) ) {
			return false;
		}
		
		return $class->{$parameter};
	}
	
	/**
	 * CSS classes for the settings should always be a list (array)
	 *
	 * @param string|array $classes
	 *
	 * @return array
	 */
	public static function fixClasses( $classes ) {
		
		if ( empty( $classes ) ) {
			return array();
		}
		
		if ( is_array( $classes ) ) {
			return $classes;
		}
		
		if ( ! is_string( $classes ) ) {
			return array();
		}
		
		$classes = explode( ' ', trim( $classes ) );
		$classes = array_map( 'trim', $classes );
		
		return $classes;
	}
	
	/**
	 * Save option array to DB when class is being destroyed/goes out of scope (request ending)
	 */
	public function __destruct() {
		
		$exclude = array( 'instance', 'option_name' );
		$options = array();
		
		foreach ( get_object_vars( $this ) as $parameter => $value ) {
			
			// Skip parameters in the
			if ( in_array( $parameter, $exclude ) ) {
				continue;
			}
			
			$options[ $parameter ] = $value;
		}
		
		update_option( self::$option_name, $options, 'no' );
	}
	
	/**
	 * Hide the clone() magic method
	 *
	 * @access private
	 */
	private function __clone() {
	}
}