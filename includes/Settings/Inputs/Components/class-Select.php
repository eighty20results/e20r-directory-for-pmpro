<?php
/**
 * Copyright (c) 2018-2019 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Member_Directory\Settings\Inputs\Components;

use E20R\Utilities\Utilities;
use E20R\Member_Directory\Settings\Options;
use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;

/**
 * Class Select
 * @package E20R\Sales_Integration\\Views\Settings\Inputs
 */
class Select {
	
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
	 * Generate the Select HTML
	 *
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function render( $settings ) {
		
		$utils         = Utilities::get_instance();
		$saved_value   = isset( $settings['value'] ) ? $settings['value'] : Options::get( $settings['option_name'] );
		$category      = esc_attr( $settings['setting_category'] );
		$setting_name  = esc_attr( $settings['option_name'] );
		$id_label      = ! empty( $settings['id'] ) ? sprintf( 'id="%1$s"', $settings['id'] ) : null;
		$is_select2    = ( isset( $settings['type'] ) && 'select2' === $settings['type'] ) ? true : false;
		$placeholder   = ! empty( $settings['placeholder'] ) ? $settings['placeholder'] : null;
		$option_has_image = isset( $settings['image_in_options'] ) ? (bool) $settings['image_in_options'] : false;
		
		if ( empty( $saved_value ) ) {
			$saved_value = $settings['default_value'];;
		}
		
		$input_classes = Options::fixClasses( ( isset( $settings['input_css_classes'] ) ? $settings['input_css_classes'] : null ) );
		
		$multiple   = isset( $settings['multi_select'] ) && true === $settings['multi_select'] ? 'multiple="multiple"' : null;
		$options    = ! empty( $settings['select_options'] ) ? $settings['select_options'] : array();
		$name_field = ! empty( $multiple ) ?
			sprintf( 'name="%1$s[%2$s][]"', $category, $setting_name ) :
			sprintf( 'name="%1$s[%2$s]"', $category, $setting_name );
		
		if ( true === $is_select2 ) {
			$input_classes[] = 'select2';
			$input_classes[] = 'e20r-select2';
		}
		
		$html = sprintf(
			'<select %1$s %2$s %3$s %4$s %5$s style="min-width: 300px; max-width: 600px;" %6$s>',
			$id_label,
			$name_field,
			$multiple,
			$placeholder,
			( ! empty( $input_classes ) ? sprintf( 'class="%1$s"', implode( ' ', $input_classes ) ) : null ),
			( $option_has_image ? 'data-imgoption="image"' : null )
		);
		
		if ( !isset( $options[-1] ) ) {
			$html .= sprintf( '<option value="-1" %1$s></option>', ( ! is_array( $saved_value ) ? selected( $saved_value, "-1", false ) : $utils->selected( "-1", $saved_value, false ) ) );
		}
		
		foreach ( $options as $option_value => $option_title ) {
			
			// $utils->log("Processing {$option_title} => " . print_r( $option_value, true ) );
			
			$selected = ! is_array( $saved_value ) ? selected( $saved_value, $option_value, false ) : $utils->selected( $option_value, $saved_value, false );
			
			$html .= sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $option_value ),
				$selected,
				esc_attr( $option_title )
			);
		}
		
		$html .= sprintf( '</select>' );
		
		$utils->log("Rendering select...");
		echo $html;
	}
}