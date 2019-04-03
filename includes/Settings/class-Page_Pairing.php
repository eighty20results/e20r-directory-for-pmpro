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

use E20R\Member_Directory\E20R_Directory_For_PMPro as Controller;
use E20R\Member_Directory\Settings\Inputs\Components\Select;
use E20R\Utilities\Utilities;

class Page_Pairing {
	
	/**
	 * The single instance of this class (singleton)
	 *
	 * @var null|Page_Pairing
	 */
	private static $instance = null;
	/**
	 * List of page(s) to use in settings drop-down (select 2)
	 *
	 * @var string[]
	 */
	private $page_list = array();
	
	/**
	 * Membership constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Load action and filter hook handlers
	 */
	public function loadHooks() {
		
		add_action( 'e20r-directory-process-extra-pages', array( $this, 'loadPairSettings' ), - 1, 1 );
		add_action( 'e20r-directory-save-pmpro-page-settings', array( $this, 'saveSettings' ), 10 );
	}
	
	public function loadPairSettings( $extra_pages ) {
		
		self::render();
	}
	
	public static function render() {
		
		$utils      = Utilities::get_instance();
		$page_pairs = Options::get( 'page_pairs' ); ?>
        <tr>
            <td class="e20r-directory-page-pair" colspan="2">
                <h2><?php _e( 'Member Directory', Controller::plugin_slug ); ?></h2>
            </td>
        </tr>
        <tr>
            <td class="e20r-directory-page-pair" colspan="2">
                <p>
					<?php _e( 'Use the following settings to configure how directory page(s) and profile page(s) relate to each other.', Controller::plugin_slug ); ?>
                    <br/>
					<?php _e( 'It is possible to configure more than one directory page and link it to an associated profile page.', Controller::plugin_slug ); ?>
                    <br/>
					<?php _e( '<strong>Note</strong>: You <em>cannot</em> configure one (single) directory page and link it to different profile pages.', Controller::plugin_slug ); ?>
                    <br/>
					<?php _e( '<strong>Note</strong>: You <em>cannot</em> configure multiple directory pages and link them to the same (single) profile page.', Controller::plugin_slug ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <td class="e20r-directory-page-pair" colspan="2">
                <div class="e20r-directory-page e20r-admin-header"><?php _e( 'Directory Page', Controller::plugin_slug ); ?></div>
                <div class="e20r-directory-arrow"><?php
					is_rtl() ?
						_e( '&#8592;', Controller::plugin_slug ) : // Left Arrow (for RTL)
						_e( '&#8594;', Controller::plugin_slug );  // Right Arrow (for LTR)
					?></div>
                <div class="e20r-profile-page e20r-admin-header"><?php _e( 'Profile Page', Controller::plugin_slug ); ?></div>
                <div class="e20r-directory-add-row">
                    <button class="e20r-directory-add-directory-pair button button-secondary"><?php _e( 'Add', Controller::plugin_slug ); ?></button>
                </div>
            </td>
        <tr>
            <td class="e20r-directory-page-pair-border" colspan="2">
                <hr/>
            </td>
        </tr>
		<?php
		// Have to create/render a new entry on the settings page
		if ( empty( $page_pairs ) ) {
			
			self::createPairing( - 1, - 1 );
			
			return;
		}
		
		$utils->log( "Page pair settings: " . print_r( $page_pairs, true ) );
		
		// Process all settings for the Directory plugin
		foreach ( $page_pairs as $directory_page_id => $page_info ) {
			
			if ( empty( $page_info['directory'] ) ) {
				$page_info['directory'] = - 1;
			}
			
			if ( empty( $page_info['profile'] ) ) {
				$page_info['profile'] = - 1;
			}
			
			$utils->log( "Adding pairing for directory page ID: {$page_info['directory']}" );
			
			// Generate HTML for the directory -> profile page ID pairing(s)
			self::createPairing(
				$page_info['directory'],
				$page_info['profile']
			);
		}
	}
	
	/**
	 * Generate HTML for the Directory -> Profile page (ID) setting on the settings page
	 *
	 * @param int $directory_page_id
	 * @param int $profile_page_id
	 *
	 * @return string
	 */
	public static function createPairing( $directory_page_id, $profile_page_id ) {
		
		// Create the Directory page setting field
		$directory_page = new Input_Setting();
		$directory_page->set( 'field_title', __( 'Directory Page:', Controller::plugin_slug ) );
		$directory_page->set( 'input_css_classes', array( 'e20r-directory-setting', 'e20r-select2' ) );
		$directory_page->set( 'option_name', sprintf( 'e20r_directory_page_id-%d', $directory_page_id ) );
		$directory_page->set( 'setting_category', 'e20r_directory_page_map' );
		$directory_page->set( 'type', 'select2' );
		$directory_page->set( 'value', $directory_page_id );
		$directory_page->set( 'select_options', self::getInstance()->getPageList() );
		$directory_page->set( 'default_value', - 1 );
		
		
		// Create the Profile page setting field
		$profile_page = new Input_Setting();
		$profile_page->set( 'field_title', __( 'Profile Page:', Controller::plugin_slug ) );
		$profile_page->set( 'input_css_classes', array( 'e20r-profile-setting', 'e20r-select2' ) );
		$profile_page->set( 'option_name', sprintf( 'e20r_profile_page_id-%d', $directory_page_id ) );
		$profile_page->set( 'setting_category', 'e20r_directory_page_map' );
		$profile_page->set( 'type', 'select2' );
		$profile_page->set( 'value', $profile_page_id );
		$profile_page->set( 'select_options', self::getInstance()->getPageList() );
		$profile_page->set( 'default_value', - 1 );
		
		$directory_edit_link = null;
		$directory_view_link = null;
		$profile_edit_link   = null;
		$profile_view_link   = null;
		
		if ( - 1 !== $directory_page_id ) {
			$directory_edit_link = add_query_arg( '', $directory_page_id, admin_url( 'edit.php' ) );
			$directory_view_link = get_permalink( $directory_page_id );
		}
		
		if ( - 1 !== $profile_page_id ) {
			$profile_edit_link = add_query_arg( '', $profile_page_id, admin_url( 'edit.php' ) );
			$profile_view_link = get_permalink( $profile_page_id );
		}
		
		ob_start(); ?>
        <tr class="e20r-directory-page-pair-setting">
            <td class="e20r-directory-page-pair" colspan="2">
                <div class="e20r-directory-page">
					<?php Select::render( $directory_page->getSettings() );
					if ( ! empty( $directory_view_link ) ) { ?>
                        <small class="e20r-directory-view-link">
						<?php
						printf(
							'%1$sView page%2$s',
							sprintf(
								'<a href="%1$s" target="_blank" title="%2$s">',
								$directory_view_link,
								__( 'View this Directory page', Controller::plugin_slug )
							), '</a>' );
						?>
                        </small><?php
					}
					if ( ! empty( $directory_edit_link ) ) { ?>
                        <small class="e20r-directory-edit-link">
							<?php
							printf(
								'%1$sEdit page%2$s',
								sprintf(
									'<a href="%1$s" target="_blank" title="%2$s">',
									$directory_edit_link,
									__( 'Edit this Directory page', Controller::plugin_slug )
								), '</a>' );
							?>
                        </small>
					<?php } ?>
                </div>
                <div class="e20r-directory-arrow"><?php
					is_rtl() ?
						_e( '&#8592;', Controller::plugin_slug ) : // Left Arrow (for RTL)
						_e( '&#8594;', Controller::plugin_slug );  // Right Arrow (for LTR)
					?></div>
                <div class="e20r-profile-page">
					<?php Select::render( $profile_page->getSettings() );
					if ( ! empty( $directory_view_link ) ) { ?>
                        <small class="e20r-profile-view-link">
						<?php
						printf(
							'%1$sView page%2$s',
							sprintf(
								'<a href="%1$s" target="_blank" title="%2$s">',
								$profile_view_link,
								__( 'View this Profile page', Controller::plugin_slug )
							), '</a>' );
						?>
                        </small><?php
					}
					if ( ! empty( $profile_edit_link ) ) { ?>
                        <small class="e20r-profile-edit-link">
							<?php
							printf(
								'%1$sEdit page%2$s',
								sprintf(
									'<a href="%1$s" target="_blank" title="%2$s">',
									$profile_edit_link,
									__( 'Edit this Profile page', Controller::plugin_slug )
								), '</a>' );
							?>
                        </small>
					<?php } ?>
                </div>
                <div class="e20r-directory-pair-delete">
                    <button class="e20r-directory-pair-delete button"><span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            </td>
        </tr>
		<?php
		echo ob_get_clean();
	}
	
	/**
	 * Array of pages on the site
	 *
	 * @return string[]
	 */
	private function getPageList() {
		
		if ( ! empty( $this->page_list ) ) {
			return $this->page_list;
		}
		
		$this->loadPageList();
		
		return $this->page_list;
	}
	
	/**
	 * Get and cache all pages from the site (with CPTs/posts if specified with the filter)
	 */
	private function loadPageList() {
		
		/**
		 * List of post types to include in the page list for the settings. Default: page
		 *
		 * @filter e20r-directory-page-types
		 *
		 * @param string[] $page_types - The Post Types (and custom post types) to include in the page list
		 *
		 * @return string[] - Array of post types (default: page)
		 */
		$page_types = apply_filters( 'e20r-directory-page-types', array( 'page' ) );
		
		/** For compatibility with PMPro Member Directory/PMPro itself */
		$page_types = apply_filters( 'pmpro_admin_pagesetting_post_type_array', $page_types );
		
		/**
		 * List of statuses to fetch (default: 'publish')
		 *
		 * @filter e20r-directory-page-statuses
		 *
		 * @param string[] - Array valid WP Post statuses
		 *
		 * @return string[] - List of statuses to use when fetching pages/posts/cpts
		 */
		$page_statuses = apply_filters( 'e20r-directory-page-statuses', array( 'publish' ) );
		
		/**
		 * @uses e20r-directory-page-statuses
		 * @uses e20r-directory-page-types
		 */
		$post_search = array(
			'posts_per_page' => - 1, // Load all page(s) on the site
			'order'          => 'ASC', // Sort alphabetically by page/post title
			'order_by'       => 'title',
			'post_type'      => $page_types,
			'post_status'    => $page_statuses,
		);
		
		/**
		 * @var \WP_Post[] $page_list
		 */
		$page_list = get_posts( $post_search );
		
		// Add the default page (not selected, aka -1
		$this->page_list[ - 1 ] = __( 'Not selected', Controller::plugin_slug );
		
		/**
		 * @var \WP_Post $found_page
		 */
		foreach ( $page_list as $found_page ) {
			$this->page_list[ $found_page->ID ] = $found_page->post_title;
		}
		
	}
	
	/**
	 * Get or instantiate and get the current class (singleton)
	 *
	 * @return Page_Pairing|null
	 */
	public static function getInstance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate and return new directory/profile pairing row (HTML) in AJAX call
	 */
	public function loadNewRow() {
		
		$utils = Utilities::get_instance();
		$utils->log( "Verify NONCE" );
		
		check_ajax_referer( 'savesettings', 'pmpro_pagesettings_nonce' );
		
		$utils->log( "Fetch the HTML for the row" );
		
		ob_clean();
		self::createPairing( - 1, - 1 );
		exit();
	}
	
	/**
	 * Save the custom Ssettings from the PMPro Page settings page
	 *
	 *
	 * @return bool
	 */
	public function saveSettings() {
		
		$utils = Utilities::get_instance();
		$utils->log( "Settings before sanitation: " . print_r( $_REQUEST, true ) );
		
		if ( ! isset( $_REQUEST['e20r_directory_page_map'] ) && ! is_array( $_REQUEST['e20r_directory_page_map'] ) ) {
			$utils->log( "Nothing to save for the page pairings" );
			
			return false;
		}
		
		$page_pairs = array();
		
		foreach ( $_REQUEST['e20r_directory_page_map'] as $parameter => $value ) {
			
			// Profile or directory page ID to process?
			if ( 1 !== preg_match( '/e20r_directory_page_id-/', $parameter ) ) {
				continue;
			}
			
			if ( 1 === preg_match( '/--1$/', $parameter ) ) {
				$page_id     = 'default';
				$dir_page_id = - 1;
			} else {
				// Extract the page ID (from the setting(s) name)
				$setting_info = explode( '-', $parameter );
				$page_id      = $setting_info[ ( count( $setting_info ) - 1 ) ]; // Get the last element (the page/post ID)
				$dir_page_id  = $page_id;
			}
			
			$page_pairs[ $page_id ] = array(
				'directory' => $utils->_sanitize(
					$_REQUEST['e20r_directory_page_map']["e20r_directory_page_id-{$dir_page_id}"]
				),
				'profile'   => $utils->_sanitize(
					$_REQUEST['e20r_directory_page_map']["e20r_profile_page_id-{$dir_page_id}"]
				),
			);
		}
		
		unset( $_REQUEST['e20r_directory_page_map'] );
		
		// Save to Options() class
		Options::set( 'page_pairs', $page_pairs );
		
		$utils->log( "Settings after save: " . print_r( $page_pairs, true ) );
	}
	
	/**
	 * Hiding the clone() magic method
	 *
	 * @access private
	 */
	private function __clone() {
	}
}