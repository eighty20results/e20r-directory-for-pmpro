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

namespace E20R\Member_Directory\Modules;


class TML_Support {
	
	/**
	 * @var null|TML_Support
	 */
	private static $instance = null;
	
	/**
	 * Get or instantiate and get the class instance
	 *
	 * @return TML_Support|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load action and filter handlers for the module
	 */
	public function loadHooks() {
		
		add_filter( 'e20r-directory-load-admin-css-on-page', array( $this, 'addCSSToFrontend' ), 99 );
	}
	
	/**
	 * If we're on the TML profile front-page, we'll also add the Admin CSS for this plugin
	 *
	 * @uses I used by the e20r-directory-load-admin-css-on-page filter
	 *
	 * @param bool $add_to_frontend
	 *
	 * @return bool
	 */
	public function addCSSToFrontend( $add_to_frontend ) {
		
		if ( true === $add_to_frontend ) {
			return $add_to_frontend;
		}
		
		if ( ! is_user_logged_in() ) {
			return $add_to_frontend;
		}
		
		if ( ! class_exists( 'Theme_My_Login' ) ) {
			return $add_to_frontend;
		}
		
		if ( ! function_exists( 'tml_profiles_user_has_themed_profile' ) ) {
			return $add_to_frontend;
		}
		
		global $current_user;
		
		if ( false === tml_profiles_user_has_themed_profile( $current_user->ID ) ) {
			return $add_to_frontend;
		}
		
		return true;
	}
}