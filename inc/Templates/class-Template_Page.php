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

namespace E20R\Member_Directory;

use E20R\Utilities\Utilities;

class Template_Page {
	
	/**
	 * @var null|Template_Page
	 */
	private static $instance = null;
	
	/**
	 * List of URLs by post ID for the Profile page(s) (key: paired directory page ID)
	 *
	 * @var string[int] $profile_urls
	 */
	protected $profile_urls = array();
	
	/**
	 * List of URLs by post ID for the Directory page(s) (key: paired profile page ID)
	 *
	 * @var string[int] $this->directory_urls
	 */
	protected $directory_urls = array();
	
	
	/**
	 * Size of the avatar image
	 *
	 * @var string $avatar_size
	 */
	protected $avatar_size = '128';
	
	/**
	 * Custom fields (user meta data and their labels) to display on the page (semi-colon separated list)
	 * Example: fields="Name,first_name;Surname,last_name"
	 *
	 * @var null|string
	 */
	protected $fields = null;
	
	/**
	 * Exploded list of custom fields (user meta data and their labels) to display on the page
	 *
	 * @var array
	 */
	protected $fields_array = array();
	
	/**
	 * Whether to display the avatar image or not
	 *
	 * @var bool
	 */
	protected $show_avatar = true;
	
	/**
	 * Whether to display the user's email on the page
	 *
	 * @var bool
	 */
	protected $show_email = true;
	
	/**
	 * Whether to display the user's membership level(s) on the page
	 *
	 * @var bool
	 */
	protected $show_level = true;
	
	/**
	 * Whether to display the search field(s) on the page
	 *
	 * @var bool
	 */
	protected $show_search = true;
	
	/**
	 * The number of items to display on the page (when paginated)
	 *
	 * @var null|int
	 */
	protected $page_size = null;
	
	/**
	 * Whether to display the user's membership start date(s) on the page
	 *
	 * @var bool
	 */
	protected $show_startdate = true;
	
	/**
	 * The slug to the linked directory page (when applicable)
	 * @var null|string
	 */
	protected $directory_page_slug = null;
	
	/**
	 * List of WP roles the user|member belongs to
	 *
	 * @var null|string[]
	 */
	protected $roles = null;
	
	/**
	 * The search string (if applicable)
	 *
	 * @var null|string
	 */
	protected $search = null;
	
	/**
	 * Is the specified type a valid page type for this plugin?
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	private static function isValidPageType( $type ) {
		if ( ! in_array(
			$type,
			apply_filters( 'e20r-directory-supported-page-types', array( 'profile', 'directory' ) )
			)
		) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Add the page URL for the specific page (if applicable)
	 *
	 * @param int    $page_id
	 * @param string $url
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function addURL( $page_id, $url, $type = 'profile' ) {
		
		$class = self::getInstance();
		$utils = Utilities::get_instance();
		
		if ( false === wp_http_validate_url( $url ) ) {
			return false;
		}
		
		if ( false === self::isValidPageType( $type ) ) {
			$utils->log("{$type} is not a valid page type in this plugin");
			return false;
		}
		
		// Get the base URL for the site (used to find the page slug)
		$base_url = preg_quote( get_site_url() );
		
		// Get rid of the base URL so we're left with the page slug
		$page_slug    = preg_replace( "/{$base_url}/", '', $url );
		$dir_page = get_page_by_path( $page_slug );
		
		if ( empty( $dir_page ) ) {
			$utils->log( "Page {$page_slug} not found?!?!" );
			
			return false;
		}
		
		if ( false === self::hasShortcode( $dir_page, $type ) ) {
			$utils->log( "Not processing a page with a profile short code on it" );
			
			return false;
		}
		
		$profile_type = sprintf( '%1$s_urls', $type );
		
		$class->{$profile_type}[ $page_id ] = $url;
		
		return true;
	}
	
	/**
	 * Get or instantiate and get the current class
	 *
	 * @return Template_Page|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Does the supplied WP_Post (page) have one of the directory short codes embedded?
	 *
	 * @param \WP_Post $page
	 * @param string   $type - 'profile' or 'directory'
	 *
	 * @return bool
	 */
	public static function hasShortcode( $page, $type = 'profile' ) {
		
		$utils = Utilities::get_instance();
		
		if ( false === self::isValidPageType( $type ) ) {
			$utils->log("{$type} is not a valid page type in this plugin");
			return false;
		}
		
		if ( ! isset( $page->post_content ) ) {
			$utils->log( "Post argument is empty!" );
			
			return false;
		}
		
		// Make sure we're processing a WP_Post object
		if ( ! is_a( $page, '\WP_Post' ) ) {
			$utils->log( "The supplied page argument isn't a WordPress Post object!" );
			
			return false;
		}
		
		$short_codes = apply_filters( 'e20r-directory-supported-shortcodes', array(
				sprintf( 'e20r_member_%1$s', $type ),
				sprintf( 'e20r-member-%1$s', $type ),
				sprintf( 'pmpro_member_%1$s', $type ),
			)
		);
		
		foreach ( $short_codes as $short_code ) {
			
			if ( has_shortcode( $page->post_content, $short_code ) ) {
				$utils->log( "Found the {$short_code} short code on page {$page->ID}" );
				
				return true;
			}
		}
		
		$utils->log( "Could not locate a {$type} short code on page {$page->ID}" );
		
		return false;
	}
	
	/**
	 * Return the profile URL for the specific directory page
	 *
	 * @param int $directory_page_id
	 *
	 * @return string|null
	 */
	public function getProfileURL( $directory_page_id ) {
		
		if ( isset( $this->profile_urls[ $directory_page_id ] ) ) {
			return $this->profile_urls[ $directory_page_id ];
		}
		
		return null;
	}
	
	/**
	 * Return the Directory page URL for the specified profile page
	 *
	 * @param int $profile_page_id
	 *
	 * @return string|null
	 */
	public function getDirectoryProfileURL( $profile_page_id ) {
		
		if ( isset( $this->directory_urls[ $profile_page_id ] ) ) {
			return $this->directory_urls[ $profile_page_id ];
		}
		
		return null;
	}
	
	/**
	 * Generate output for the [e20r_member_directory] short code
	 *
	 * @param array       $atts
	 * @param null|string $content
	 * @param string      $code
	 *
	 * @return false|string
	 */
	abstract function shortcode( $atts, $content, $code );
}