<?php
/**
 *    Copyright (c) 2018-2019. - Eighty / 20 Results by Wicked Strong Chicks. ALL RIGHTS RESERVED
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

use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;
use E20R\Utilities\Utilities;

/**
 * Class Input_Setting
 * @package E20R\Sales_Integration\\Controllers\Settings
 */
class Input_Setting {
	
	private $setting_category = null;
	
	private $option_name = null;
	
	private $field_title = '';
	
	private $id = null;
	
	private $type = null;
	
	private $input_css_classes = array();
	
	private $label_css_classes = array();
	
	private $multi_select = false;
	
	private $render_callback = null;
	
	private $label = null;
	
	private $default_value = null;
	
	private $select_options = array();
	
	private $textarea_cols = 5;
	
	private $textarea_rows = 50;
	
	private $placeholder = null;
	
	private $callback = null;
	
	private $image_in_options = false;
	
	private $value = null;
	
	/**
	 * Does the settings belong to the specified category name?
	 *
	 * @param string $category_name
	 *
	 * @return bool
	 */
	public function inCategory( $category_name ) {
		
		return ( ! empty( $this->setting_category ) && $category_name === $this->setting_category );
	}
	
	/**
	 * Return the display callback function for the input setting
	 *
	 * @return array|string
	 */
	public function getCallback() {
		
		if ( empty( $this->callback ) ) {
			trigger_error(
				sprintf(
					__( 'No display callback found for the \'%s\' option!', Controller::plugin_slug ), $this->option_name
				),
				E_USER_ERROR
			);
		}
		
		return $this->callback;
	}
	
	/**
	 * Fetch the specified setting from the class
	 *
	 * @param string $setting_name
	 *
	 * @return mixed
	 */
	public function get( $setting_name ) {
		
		return ( property_exists( $this, $setting_name ) ? $this->{$setting_name} : null );
	}
	
	/**
	 * Save/Set the specified setting for the class
	 *
	 * @param string $setting_name
	 * @param mixed  $value
	 */
	public function set( $setting_name, $value ) {
		
		
		if ( property_exists( $this, $setting_name ) ) {
			$this->{$setting_name} = $value;
		} else {
			trigger_error( "Error attempting to set {$setting_name}. Not found in class!", E_USER_WARNING );
		}
	}
	
	/**
	 * Return all settings
	 *
	 * @return array
	 */
	public function getSettings() {
		
		$settings = array();
		
		foreach ( $this as $key => $value ) {
			
			$settings[ $key ] = $value;
		}
		
		return $settings;
	}
	
	/**
	 * Save all settings
	 *
	 * @param array $settings
	 */
	public function saveSettings( $settings ) {
		
		$utils = Utilities::get_instance();
		
		foreach ( $settings as $key => $value ) {
			
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			} else {
				$utils->log( sprintf( "Unable to save %s since it's not a %s member variable!", $key, get_class( __CLASS__ ) ) );
			}
		}
	}
	
}