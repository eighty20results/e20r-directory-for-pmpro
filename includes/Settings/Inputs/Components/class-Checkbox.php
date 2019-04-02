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

use E20R\Member_Directory\Settings\Settings;
use E20R\Sequences\Data\Options;


/**
 * Class Checkbox
 * @package E20R\Sales_Integration\\Views\Inputs
 */
class Checkbox {
	
	/**
	 * Render Checkbox HTML input
	 *
	 * @param array $settings
	 *
	 * @return $html
	 */
	public static function render( $settings ) {
		
		$value         = Options::get( $settings['option_name'] );
		
		$setting_name  = $settings['setting_category'];
		$id_label      = ! empty( $settings['id'] ) ? sprintf( 'id="%1$s"', $settings['id'] ) : null;
		$input_classes = Settings::fixClasses( ( isset( $settings['input_css_classes'] ) ? $settings['input_css_classes'] : null ) );
		$label_classes = Settings::fixClasses( ( isset( $settings['label_css_classes'] ) ? $settings['label_css_classes'] : null ) );
		
		$html = sprintf(
			'<input type="checkbox" %1$s name="%2$s[%3$s]" value="%4$s" %6$s %5$s />',
			$id_label,
			esc_attr( $setting_name ),
			esc_attr( $settings['option_name'] ),
			esc_attr( $settings['default_value'] ),
			checked( $settings['default_value'], $value, false ),
			( ! empty( $input_classes ) ? sprintf( 'class="%1$s"', implode( ' ', $input_classes ) ) : null )
		);
		
		if ( ! empty( $settings['label'] ) ) {
			$html .= "\n";
			$html .= sprintf(
				'<label for="%1$s" %3$s>%2$s</label>',
				esc_attr( $settings['id'] ),
				esc_attr( $settings['label'] ),
				( ! empty( $label_classes ) ? sprintf( 'class="%1$s"', implode( ' ', $label_classes ) ) : null )
			);
		}
		
		echo $html;
	}
}