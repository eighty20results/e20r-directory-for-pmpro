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

namespace E20R\Member_Directory\Tools;


use E20R\Member_Directory\Template_Page;
use E20R\Utilities\Utilities;

class Deactivate_Page_Cache {
	
	/**
	 * Singleton instance of this class
	 *
	 * @var null|Deactivate_Page_Cache
	 */
	private static $instance = null;
	
	/**
	 * List of pragma to apply to the post/page
	 *
	 * @var string[]
	 */
	private $template = array();
	
	/**
	 * Get or instantiate and get the class instance
	 *
	 * @return Deactivate_Page_Cache|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->loadMetaTemplate();
		}
		
		return self::$instance;
	}
	
	/**
	 * Add the no-cache pragma, etc to apply to the page(s) as necessary
	 */
	private function loadMetaTemplate() {
		$this->template[] = '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">';
		$this->template[] = '<meta http-equiv="Pragma" content="no-cache">';
		$this->template[] = '<meta http-equiv="Expires" content="0">';
	}
	
	/**
	 * Load action and filter hooks
	 */
	public function loadHooks() {
		add_action( 'wp_head', array( $this, 'maybeDeactivatePageCache' ), - 1 );
	}
	
	/**
	 * Do we need to add meta tags to ATTEMPT to deactivate page cache functionality for the directory/profile
	 */
	public function maybeDeactivatePageCache() {
		
		global $post;
		
		if ( empty( $post ) ) {
			return;
		}
		
		$meta_string = null;
		
		// Add the no-cache pragma if we're using the profile shortcode
		if ( true === Template_Page::hasShortcode( $post, 'profile' ) ) {
			$meta_string = implode( "\n", $this->template );
		}
		
		// Add the no-cache pragma if we're using the directory shortcode
		if ( true === Template_Page::hasShortcode( $post, 'directory' ) ) {
			$meta_string = implode( "\n", $this->template );
		}
		
		// Only print to header when we have header info to add
		if ( ! empty( $meta_string ) ) {
			echo $meta_string;
		}
	}
	
}