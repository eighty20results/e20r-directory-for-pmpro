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
use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;

/**
 * Class YesNo
 * @package E20R\Member_Directory\Settings\Inputs\Components
 */
class YesNo extends Select {
	
	/**
	 * Uses a Select dropdown to create a simple yes/no input
	 *
	 * @param array $settings
	 *
	 * @return string|void
	 */
	public static function render( $settings ) {
		
		$utils = Utilities::get_instance();
		
		$settings['placeholder'] = ! empty( $settings['placeholder'] ) ? $settings['placeholder'] : __( 'Yes or No?', Controller::plugin_slug );
		
		// Replace any supplied options (there are only two allowed for a Yes/No select
		$settings['select_options'] = array(
			0 => __( 'No', Controller::plugin_slug ),
			1 => __( 'Yes', Controller::plugin_slug ),
		);
		$settings['default_value']  = isset( $settings['default_value'] ) ? $settings['default_value'] : 0;
		
		parent::render( $settings );
	}
}