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


use E20R\Utilities\Licensing\License_Client;
use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;
use E20R\Utilities\Utilities;

/**
 * Class Licensing
 * @package E20R\Member_Directory\Settings
 */
class Licensing extends License_Client {
	
	private static $instance = null;
	
	private function __clone() {
		// TODO: Implement __clone() method.
	}
	
	private function __construct() {
	}
	
	public static function get_instance() {
		
		if ( true === is_null( self::$instance )) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load action and filter hook handlers
	 */
	public function load_hooks() {
		
		add_filter( 'e20r-license-add-new-licenses', array( $this, 'add_new_license_info', ), 10, 2 );
	}
	
	public function check_licenses() {
	
	}
	
	/**
	 * Filter Handler: Add the 'add bbPress add-on license' settings entry
	 *
	 * @filter e20r-license-add-new-licenses
	 *
	 * @param array $license_settings
	 * @param array $plugin_settings
	 *
	 * @return array
	 */
	public function add_new_license_info( $license_settings, $plugin_settings ) {
		
		$utils = Utilities::get_instance();
		
		if ( ! isset( $license_settings['new_licenses'] ) ) {
			$license_settings['new_licenses'] = array();
			$utils->log( "Init array of licenses entry" );
		}
		
		$utils->log( "Have " . count( $license_settings['new_licenses'] ) . " new licenses to process already. Adding {$stub}... " );
		
		$license_settings['new_licenses'][ 'e20r_directory' ] = array(
			'label_for'     => 'e20r_directory',
			'fulltext_name' => __('E20R Member Directory for PMPro',Controller::plugin_slug ),
			'new_product'   => 'e20r_directory',
			'option_name'   => "e20r_license_settings",
			'name'          => 'license_key',
			'input_type'    => 'password',
			'value'         => null,
			'email_field'   => "license_email",
			'email_value'   => null,
			'placeholder'   => __( "Paste E20R Directory For PMPro license key here", "e20r-licensing" ),
		);
		
		return $license_settings;
	}
}