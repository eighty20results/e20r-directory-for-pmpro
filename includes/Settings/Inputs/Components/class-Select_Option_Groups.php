<?php
/**
 *  Copyright (c) 2018-2019. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Member_Directory\Settings\Inputs\Components;

use E20R\Utilities\Utilities;
use E20R\Member_Directory\Settings\Options;
use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;
/**
 * Class Select_Option_Groups
 * @package E20R\Sales_Integration\\Views\Settings\Inputs
 */
class Select_Option_Groups {
	
	/**
	 * Filter handler for '' - Loads JavaScript file paths for admin_enqueue_scripts action
	 *
	 * @param string[] $paths - Path to JavaScript file to load in admin
	 *
	 * @return string[]
	 */
	public static function jsPath( $paths ) {
		
		$utils = Utilities::get_instance();
		$utils->log( "Loading Select2 Libraries & sources" );
		
		wp_enqueue_script( 'select2', Controller::$LIBRARY_URL . "/select2/select2/dist/js/select2.js", array( 'jquery' ), Controller::$Version );
		wp_enqueue_style( 'select2', Controller::$LIBRARY_URL . "/select2/select2/dist/css/select2.css", null, Controller::$Version );
		
		$paths['e20r-input-select'] = plugins_url( 'js/input-select.js', __FILE__ );
		
		return $paths;
	}
	
	/**
	 * Generate the Select_Option_Groups HTML
	 *
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render( $settings ) {
		
		$utils          = Utilities::get_instance();
		$saved_value    = Options::get( $settings['option_name'] );
		$category       = esc_attr( $settings['setting_category'] );
		$setting_name   = esc_attr( $settings['option_name'] );
		$id_label       = ! empty( $settings['id'] ) ? sprintf( 'id="%1$s"', $settings['id'] ) : null;
		$is_optiongroup = ( isset( $settings['type'] ) && 'select_groups' === $settings['type'] );
		$placeholder    = ! empty( $settings['placeholder'] ) ? $settings['placeholder'] : null;
		
		if ( empty( $saved_value ) ) {
			$saved_value = $settings['default_value'];;
		}
		
		$input_classes = Options::fixClasses( ( isset( $settings['input_css_classes'] ) ? $settings['input_css_classes'] : null ) );
		
		$multiple      = isset( $settings['multi_select'] ) && true === $settings['multi_select'] ? 'multiple="multiple"' : null;
		$option_groups = ! empty( $settings['select_options'] ) ? $settings['select_options'] : array( array() );
		$name_field    = ! empty( $multiple ) ?
			sprintf( 'name="%1$s[%2$s][]"', $category, $setting_name ) :
			sprintf( 'name="%1$s[%2$s]"', $category, $setting_name );
		
		if ( true === $is_optiongroup ) {
			$input_classes[] = 'select2';
			$input_classes[] = 'e20r-select2';
			$input_classes[] = 'e20r-optiongroup';
		}
		
		$html = sprintf(
			'<select %1$s %2$s %3$s %4$s %5$s style="min-width: 300px; max-width: 600px;">',
			$id_label,
			$name_field,
			$multiple,
			$placeholder,
			( ! empty( $input_classes ) ? sprintf( 'class="%1$s"', implode( ' ', $input_classes ) ) : null )
		);
		
		if ( ! isset( $options[-1] ) ) {
			
			$not_selected = sprintf( '<option value="-1" %1$s></option>', ( ! is_array( $saved_value ) ? selected( $saved_value, "-1", false ) : $utils->selected( "-1", $saved_value, false ) ) );
		}
		
		/**
		 * The $settings['select_options'] => array( 'group_title' => option_group( array( 'option_value' => 'option_label' ), array( 'id' => 'label' ) )
		 */
		foreach ( $option_groups as $group_title => $options ) {
			
			if ( empty( $options ) ) {
				$options     = $not_selected;
			}
			
			$group_title = !empty( $group_title ) ? $group_title : __( 'Unspecified', Controller::plugin_slug );
			
			$html .= sprintf( '<optgroup label="%1$s">', $group_title );
			
			if ( empty( $options ) ) {
				// Skip...
				continue;
			}
			
			foreach ( $options as $option_value => $option_label ) {
				
				// $option_label = isset( $option_config['title'] ) ? $option_config['title'] : __( 'Unknown', Controller::plugin_slug );
				// $option_fields = $option_config['fields'];
				
				$utils->log( "Option value ({$option_value}) vs saved ({$saved_value})");
				$selected = ! is_array( $saved_value ) ? selected( $saved_value, $option_value, false ) : $utils->selected( $option_value, $saved_value, false );
				
				$html .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $option_value ),
					$selected,
					esc_html( $option_label )
				);
			}
			
			$html .= sprintf( '</optgroup>' );
		}
		$html .= sprintf( '</select>' );
		
		echo $html;
	}
}