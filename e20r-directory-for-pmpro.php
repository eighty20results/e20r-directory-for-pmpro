<?php
/*
Plugin Name: E20R Directory for PMPro
Plugin URI: https://eighty20results.com/wordpress-plugins/e20r-directory-for-pmpro
Description: Better member directory and profile pages for Paid Memberships Pro
Version: 3.0
Author: eighty20results, strangerstudios
Author URI: https://eighty20results.com/thomas-sjolshagen
Text Domain: e20r-directory-for-pmpro
Domain Path: /languages
License: GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

 *    Copyright (c) 2015-2019. - Eighty / 20 Results by Wicked Strong Chicks. ALL RIGHTS RESERVED
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


/**
 * @credit https://www.paidmembershipspro.com
 */

namespace E20R\Member_Directory;

global $e20rmd_options;

use E20R\Member_Directory\Settings\Licensing;
use E20R\Member_Directory\Settings\Options;
use E20R\Member_Directory\Settings\Page_Pairing;
use E20R\Member_Directory\Settings\PMPro_PageSettings;
use E20R\Member_Directory\Tools\Billing_Information;
use E20R\Utilities\Cache;
use E20R\Utilities\Utilities;

if ( ! defined( "E20R_DIRECTORY" ) ) {
	define( 'E20R_DIRECTORY', true );
}

if ( ! defined( "E20RED_VER" ) ) {
	define( 'E20RED_VER', "3.0" );
}

/**
 * Class E20R_Directory_For_PMPro
 * @package E20R\Member_Directory
 */
class E20R_Directory_For_PMPro {
	
	const plugin_slug = 'e20r-directory-for-pmpro';
	
	/**
	 * The URL to the JS library(/ies)
	 *
	 * @var null|string $LIBRARY_URL
	 */
	public static $LIBRARY_URL = null;
	
	/**
	 * The version number for this plugin
	 *
	 * @var float $Version
	 */
	public static $Version = 3.0;
	
	/**
	 * The only instance of this class
	 *
	 * @var null|E20R_Directory_For_PMPro $instance
	 */
	private static $instance = null;
	
	/**
	 * The key to use for the member list cache(s)
	 *
	 * @var string $cache_key
	 */
	private $cache_key = 'ml_';
	
	/**
	 * Check if the value is one of the valid responses for the boolean type
	 *
	 * @param int|string $value
	 * @param string     $type
	 *
	 * @return bool
	 */
	public static function trueFalse( $value, $type = 'false' ) {
		
		$utils = \E20R\Utilities\Utilities::get_instance();
		
		switch ( $type ) {
			case 'true':
				// Return true if we found one of the 'true' values
				$found = in_array( $value, array( 'yes', '1', 'true' ) );
				$utils->log( "Checking for true: {$value} -> " . ( $found ? 'Found' : 'Not Found' ) );
				
				return ( true === $found ? true : false );
				break;
			default:
				// Return false if we found one of the 'false' values
				$found = in_array( $value, array( 'no', 'false', '0' ) );
				$utils->log( "Checking for false: {$value} -> " . ( $found ? 'Found' : 'Not Found' ) );
				
				return ( true === $found ? false : true );
		}
	}
	
	/**
	 * Display the correct media type
	 *
	 * @param array $meta_field
	 *
	 * @return string
	 */
	public static function displayFileField( $meta_field ) {
		
		$meta_field_file_type = wp_check_filetype( $meta_field['fullurl'] );
		
		switch ( $meta_field_file_type['type'] ) {
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				return sprintf(
					'<a href="%s" title="%s" target="_blank"><img class="subtype-%s" src="%s"><br />%s</a>',
					$meta_field['fullurl'],
					$meta_field['filename'],
					$meta_field_file_type['ext'],
					$meta_field['fullurl'],
					$meta_field['filename']
				);
				break;
			case 'video/mpeg':
			case 'video/mp4':
				return do_shortcode( sprintf( '[video src="%s"]', $meta_field['fullurl'] ) );
				break;
			case 'audio/mpeg':
			case 'audio/wav':
				return do_shortcode( sprintf( '[audio src="%s"]', $meta_field['fullurl'] ) );
				break;
			default:
				return sprintf(
					'<a href="%s" title="%s" target="_blank"><img class="subtype-%s" src="%s"><br />%s</a>',
					$meta_field['fullurl'],
					$meta_field['filename'],
					$meta_field_file_type['ext'],
					wp_mime_type_icon( $meta_field_file_type['type'] ),
					$meta_field['filename']
				);
				break;
		}
	}
	
	/**
	 * Generate a Cache Key
	 *
	 * @param null|array  $levels
	 * @param null|string $search
	 *
	 * @return string
	 */
	public static function getCacheKey( $levels, $search ) {
		
		$class = self::getInstance();
		
		if ( ! empty( $levels ) ) {
			$class->cache_key .= implode( '_', $levels );
		}
		
		if ( ! empty( $search ) ) {
			$class->cache_key .= '_s_';
		}
		
		$class->cache_key .= 'dta';
		
		return $class->cache_key;
	}
	
	/**
	 * Load or instantiate the class
	 *
	 * @return E20R_Directory_For_PMPro|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			
			// Set the path to the libraries we need/use
			self::$LIBRARY_URL = plugin_dir_url( __FILE__ ) . "/includes";
		}
		
		return self::$instance;
	}
	
	/**
	 * Class auto-loader for the OEIS Application Management plugin
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @return string
	 *
	 * @since  1.0
	 * @access public static
	 */
	public static function autoLoader( $class_name ) {
		
		$is_e20r = ( false !== stripos( $class_name, 'utilities' ) );
		
		if ( false === stripos( $class_name, 'Member_Directory' ) && false === $is_e20r ) {
			return $class_name;
		}
		
		$parts  = explode( '\\', $class_name );
		$c_name = $is_e20r ? preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) : $parts[ ( count( $parts ) - 1 ) ];
		
		$base_path = plugin_dir_path( __FILE__ ) . 'includes/';
		
		if ( $is_e20r ) {
			$filename = strtolower( "class.{$c_name}.php" );
		} else {
			$filename = "class-{$c_name}.php";
		}
		
		$iterator = new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveIteratorIterator::SELF_FIRST | \RecursiveIteratorIterator::CATCH_GET_CHILD | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
		
		/**
		 * Loate class member files, recursively
		 */
		$filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) use ( $filename ) {
			
			$file_name = $current->getFilename();
			
			// Skip hidden files and directories.
			if ( $file_name[0] == '.' || $file_name == '..' ) {
				return false;
			}
			
			if ( $current->isDir() ) {
				// Only recurse into intended subdirectories.
				return $file_name() === $filename;
			} else {
				// Only consume files of interest.
				return strpos( $file_name, $filename ) === 0;
			}
		} );
		
		foreach ( new \ RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {
			
			$class_path = $f_file->getPath() . "/" . $f_file->getFilename();
			
			if ( $f_file->isFile() && false !== strpos( $class_path, $filename ) ) {
				require_once( $class_path );
			}
		}
	}
	
	/**
	 * Load page URLs for the Directory and Profile page(s)
	 */
	public static function setPageURLs() {
		
		$options = Options::get( 'page_pairs' );
		$class   = self::getInstance();
		$utils   = Utilities::get_instance();
		
		if ( empty( $options ) ) {
			$options            = array();
			$options['default'] = array( 'directory' => - 1, 'profile' => - 1 );
		}
		
		$directory_page_id = (int) $options['default']['directory'];
		$profile_page_id   = (int) $options['default']['profile'];
		
		if ( - 1 === $profile_page_id ) {
			$utils->log( "Have to create a Profile page for the site!" );
			$profile_page_id               = $class->createPage( 'profile' );
			$options['default']['profile'] = $profile_page_id;
		}
		
		if ( - 1 === $directory_page_id ) {
			$utils->log( "Have to create a Directory page for the site" );
			$directory_page_id               = $class->createPage( 'directory' );
			$options['default']['directory'] = $directory_page_id;
		}
		
		foreach ( $options as $page_id => $settings ) {
			
			if ( 'default' === $page_id ) {
				$page_id = $settings['directory'];
			}
			
			$urls = array(
				'directory' => get_permalink( $settings['directory'] ),
				'profile'   => get_permalink( $settings['profile'] ),
			);
			
			Directory_Page::addURL( $settings['directory'], get_permalink( $settings['directory'] ) );
			Profile_Page::addURL( $settings['profile'], $urls );
		}
		
		$directory_page = get_post( $directory_page_id );
		
		
		$profile_page = get_post( $profile_page_id );
		Profile_Page::setURL( get_permalink( $profile_page ) );
		
		if ( false === Options::set( 'page_pairs', $options ) ) {
			$utils->log( "Unable to (re) save the Option settings for the page pairs" );
		}
	}
	
	/**
	 * Create default Profile and Directory page(s)
	 *
	 * @param string $type
	 *
	 * @return bool|int
	 */
	private function createPage( $type ) {
		
		global $pmpro_pages;
		global $current_user;
		
		$utils  = Utilities::get_instance();
		$parent = 0; // Default is "no parent page"
		
		// Place the page(s) under the PMPro account page if the PMPro account page exists;
		if ( ! empty( $pmpro_pages['account'] ) ) {
			$parent = $pmpro_pages['account'];
		}
		
		switch ( $type ) {
			case 'directory':
				$content = '[e20r-member-directory]';
				$title   = __( 'Member Directory', self::plugin_slug );
				break;
			
			case 'profile':
				$content = '[e20r-member-profile]';
				$title   = __( 'Member Profile', self::plugin_slug );
				break;
			default:
				$title   = null;
				$content = null;
		}
		
		if ( empty( $title ) ) {
			return false;
		}
		
		if ( empty( $content ) ) {
			return false;
		}
		
		$page_params = array(
			'post_title'     => $title,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => $content,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_parent'    => $parent,
			'post_author'    => $current_user->ID,
		);
		
		$page_id = wp_insert_post( $page_params, true );
		
		if ( is_wp_error( $page_id ) ) {
			$utils->log( "Error generating {$title} page: " . $page_id->get_error_message() );
			
			return false;
		}
		
		return $page_id;
	}
	
	/**
	 * Return the URL for the specified page ID
	 *
	 * @param string     $type - 'profile' or 'directory'
	 * @param int|string $page_id
	 *
	 * @return bool|string
	 */
	public static function getURL( $type, $page_id = 'default' ) {
		
		$utils         = Utilities::get_instance();
		$has_shortcode = false;
		
		if ( 'default' === $page_id ) {
			$page_ids = Options::get( 'page_ids' );
			$page_id  = $page_ids['default'][ $type ];
		}
		
		// Make sure the page has a valid/expected short code
		switch ( $type ) {
			case 'profile':
				$has_shortcode = Profile_Page::hasShortcode( get_post( $page_id ) );
				break;
			case 'directory':
				$has_shortcode = Directory_Page::hasShortcode( get_post( $page_id ) );
				break;
		}
		
		if ( false === $has_shortcode ) {
			$utils->log( "No short code found for {$type}" );
			
			return false;
		}
		
		return get_permalink( $page_id );
	}
	
	/**
	 * Add action/filter handler hooks
	 */
	public function loadHooks() {
		
	    // TODO: Activate licensing module once implemented
		// add_action( 'plugins_loaded', array( Licensing::get_instance(), 'load_hooks'), 18 );
		add_action( 'plugins_loaded', array( PMPro_PageSettings::getInstance(), 'loadHooks' ), 19 );
		add_action( 'plugins_loaded', array( Directory_Page::getInstance(), 'loadHooks' ), 20 );
		add_action( 'plugins_loaded', array( Profile_Page::getInstance(), 'loadHooks' ), 20 );
		add_action( 'plugins_loaded', array( Page_Pairing::getInstance(), 'loadHooks' ), 21 );
		
		add_action( 'plugins_loaded', array( $this, 'init' ), 99 );
		
		add_action( 'e20rmd_add_extra_profile_output', array(
			Billing_Information::getInstance(),
			'addAddressSection',
		), 5, 2 );
		
		add_filter( 'e20r-member-profile_fields', array(
			Billing_Information::getInstance(),
			'fixAddressInfo',
		), 99, 2 );
		
		add_action( 'init', 'E20R\\Member_Directory\\Tools\\I18N::loadTextDomain', 1 );
		
		/*
		add_action( 'template_redirect', array( Directory_Page::getInstance(), 'loadDirectoryURL' ), 5 );
		add_action( 'template_redirect', array( Profile_Page::getInstance(), 'loadProfileURL' ), 5 );
		*/
		
		add_action( 'admin_init', array( $this, 'adminNotice' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'loadAdminScriptsStyles' ), 10 );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'registerStyles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStyles' ), 20 );
		
		// add_action( 'pmpro_extra_page_settings', array( $this, 'extraPageSettings' ), 10 );
		
		// Get rid of the PMPro Directory/Profile pages settings (if needed)
		if ( true == has_action( 'pmpro_extra_page_settings', 'pmpromd_extra_page_settings' ) ) {
			remove_action( 'pmpro_extra_page_settings', 'pmpromd_extra_page_settings', 10 );
		}
		
		add_action( 'show_user_profile', array( $this, 'showExtraProfileFields' ), 10 );
		add_action( 'edit_user_profile', array( $this, 'showExtraProfileFields' ), 10 );
		
		add_action( 'personal_options_update', array( $this, 'saveExtraProfileFields' ), 10 );
		add_action( 'edit_user_profile_update', array( $this, 'saveExtraProfileFields' ), 10 );
		
		// Add billing info to User Profile page(s)
		add_action( 'show_user_profile', array( $this, 'showBillingInfoFields' ), 10 );
		add_action( 'edit_user_profile', array( $this, 'showBillingInfoFields' ), 10 );
		
		add_action( 'personal_options_update', array(
			Billing_Information::getInstance(),
			'maybeSaveBillingInfo',
		), 99 );
		add_action( 'edit_user_profile_update', array(
			Billing_Information::getInstance(),
			'maybeSaveBillingInfo',
		), 99 );
		
		// Clear the Member List cache whenever a user's membership level or a checkout completes
		add_action( 'pmpro_after_change_membership_level', array( $this, 'clearMemberCache' ), 99999 );
		add_action( 'pmpro_after_checkout', array( $this, 'clearMemberCache' ), 99999 );
		
		add_filter( 'plugin_row_meta', array( $this, 'pluginRowMeta' ), 10, 2 );
		
		// add_action( 'profile_update', array( Billing_Information::getInstance(), 'maybeSaveBillingInfo' ), 999 );
		add_action( 'edit_user_profile_update', array(
			Billing_Information::getInstance(),
			'maybeSaveBillingInfo',
		), 999 );
		add_action( 'personal_options_update', array(
			Billing_Information::getInstance(),
			'maybeSaveBillingInfo',
		), 999 );
		
		// add_filter( 'pmpro_page_custom_template_path', array( $this, 'directory_template_paths' ), 99, 5 );
		
		add_action( 'wp_ajax_e20r_directory_load_new_row', array( Page_Pairing::getInstance(), 'loadNewRow' ) );
		add_action( 'init', array( $this, 'areSettingsEmpty' ), 10 );
	}
	
	/**
	 * Post notice when Member Directory settings haven't been configured (are empty).
	 */
	public function areSettingsEmpty() {
	    
	    $utils      = Utilities::get_instance();
		
		if ( ! is_admin() ) {
			$utils->log( "Not in WordPress backend!" );
			return;
		}
		
		// Load page pair settings
		$page_pairs = Options::get( 'page_pairs' );
		
		if ( ! empty( $page_pairs ) ) {
			$utils->log("Page pair settings is not empty.. ");
			return;
		}
		
		$utils->add_message(
			sprintf(
				__(
					'Please configure the PMPro %1$sPages settings%2$s for the Member Directory',
					self::plugin_slug
				),
				sprintf(
					'<a href="%1$s" title="%2$s">',
					add_query_arg( 'page', 'pmpro-pagesettings', admin_url( 'admin.php' ) ),
					__( 'Click for the PMPro -> Settings -> Pages settings page' )
				),
				'</a>'
			
			),
			'warning',
			'backend'
		);
	}
	
	/**
	 * Add Billing and Shipping Address field(s) to the WP User Profile page
	 *
	 * @param \WP_User $user
	 */
	public function showBillingInfoFields( $user ) {
		
		global $e20rmd_show_billing_address;
		global $e20rmd_show_shipping_address;
		global $e20rmd_has_billing_fields;
		global $e20rmd_has_shipping_fields;
		
		$billing_saved  = $e20rmd_show_billing_address;
		$shipping_saved = $e20rmd_show_shipping_address;
		
		$e20rmd_show_billing_address  = true;
		$e20rmd_show_shipping_address = true;
		
		$bi                         = Billing_Information::getInstance();
		$e20rmd_has_billing_fields  = Billing_Information::getBillingFields();
		$e20rmd_has_shipping_fields = Billing_Information::getShippingFields();
		
		$bi->addAddressSection( array(), $user, false );
		
		$e20rmd_show_billing_address  = $billing_saved;
		$e20rmd_show_shipping_address = $shipping_saved;
	}
	
	/**
	 * Load Admin side scripts & styles
	 */
	public function loadAdminScriptsStyles( $hook ) {
		
		$utils = Utilities::get_instance();
		
		if ( ! is_admin() ) {
		    $utils->log("Not in WP backend!");
		    return;
        }
        
		if ( 'admin_page_pmpro-pagesettings' !== $hook && 'user-edit.php' !== $hook ) {
			$utils->log( "Not on the profile or PMPro Settings page: {$hook}" );
			
			return;
		}
		
		$utils->log( "Loading admin scripts/styles for PMPro Page Settings" );
		wp_enqueue_style( 'e20r-directory-for-pmpro', plugins_url( 'css/e20r-directory-for-pmpro-admin.css', __FILE__ ), array( 'dashicons' ), self::$Version );
		
		wp_register_script( 'e20r-directory-for-pmpro', plugins_url( 'javascript/e20r-directory-for-pmpro-admin.js', __FILE__ ), array( 'jquery' ), self::$Version );
		wp_localize_script( 'e20r-directory-for-pmpro', 'e20rdir', array(
			'ajax' => array(
				'timeout' => apply_filters( 'e20r-directory-ajax-timeout', 15 ),
				'url'     => admin_url( 'admin-ajax.php' ),
			),
		) );
		
		wp_enqueue_script( 'e20r-directory-for-pmpro' );
	}
	
	/**
	 * Remove the member cache whenever a membership level change(s)
	 */
	public function clearMemberCache() {
		
		self::clearCache();
	}
	
	/**
	 * Clear cached data for member(s)
	 */
	public static function clearCache() {
		
		$utils = Utilities::get_instance();
		$class = self::getInstance();
		
		$utils->log( "Clear the cache" );
		
		Cache::delete( $class->cache_key, 'e20rmdp' );
	}
	
	/**
	 * Check if the PMPro Member Directory add-on is active?
	 */
	public function init() {
		
	    $utils = Utilities::get_instance();
		if ( function_exists( 'pmpromd_register_styles' ) && is_admin() ) {
			
			$utils->add_message(
			        __(
			                "The 'Member Directory & Profile Pages' add-on for Paid Memberships Pro is currently active. You should deactivate it before you activate this plugin.",
                            self::plugin_slug
                    ),
                    'error',
                    'backend'
            );
			
			return;
		}
	}
	
	/**
	 * Show error notice(s) if found
	 */
	public function adminNotice() {
		
		global $pmpro_msg;
		global $pmpro_msgt;
		
		if ( ! empty( $pmpro_msg ) ) { ?>
            <div class="notice notice-<?php esc_attr_e( $pmpro_msgt ); ?> is-dismissible">
                <p><?php esc_html_e( $pmpro_msg ); ?></p>
            </div>
			<?php
		}
	}
	
	/**
	 * Load directory location (list?) where we may find the PMPro Directory add-on templates
	 *
	 * @param array  $paths
	 * @param string $page
	 * @param string $type
	 * @param string $where
	 * @param string $ext
	 *
	 * @return array
	 */
	public function directoryTemplatePaths( $paths, $page, $type, $where, $ext ) {
		
		if ( 1 !== preg_match( '/directory|profile/i', $page ) ) {
			return $paths;
		}
		
		$list = array(
			'child_theme' => get_template_directory() . "/paid-memberships-pro/e20r-directory-for-pmpro/",
			'theme'       => get_stylesheet_directory() . "/paid-memberships-pro/e20r-directory-for-pmpro/",
			'plugin'      => dirname( __FILE__ ) . '/includes/templates/',
		);
		
		foreach ( $list as $location => $path ) {
			
			if ( ! file_exists( "{$path}{$page}.{$ext}" ) ) {
				unset( $list[ $location ] );
			}
		}
		
		return $paths;
	}
	
	/**
	 * Register/load Directory/Profile page styles as/when needed
	 */
	public function registerStyles() {
	 
		$css_list = apply_filters( 'e20r-directory-for-pmpro-css-file-paths', array(
				'child_theme'  => array(
					'file' => get_stylesheet_directory() . '/paid-memberships-pro/member-directory/css/e20r-directory-for-pmpro.css',
					'uri'  => get_stylesheet_directory_uri() . '/paid-memberships-pro/member-directory/css/e20r-directory-for-pmpro.css',
				),
				'parent_theme' => array(
					'file' => get_template_directory() . '/paid-memberships-pro/member-directory/css/e20r-directory-for-pmpro.css',
					'uri'  => get_template_directory_uri() . '/paid-memberships-pro/member-directory/css/e20r-directory-for-pmpro.css',
				),
				'plugin'       => array(
					'file' => plugin_dir_path( __FILE__ ) . 'css/e20r-directory-for-pmpro.css',
					'uri'  => plugins_url( 'css/e20r-directory-for-pmpro.css', __FILE__ ),
				),
			)
		);
		
		// Load the stylesheet when found (check child theme, then parent theme, then this plugin folder)
		foreach ( $css_list as $css_file ) {
			
			if ( ! file_exists( $css_file['file'] ) ) {
				continue;
			}
			
			// Load the plugin style sheet _after_ the PMPro plugin stylesheet
			wp_register_style( 'e20r-directory-for-pmpro-styles', $css_file['uri'], array( 'pmpro_frontend' ), E20RED_VER );
		}
	}
	
	/**
	 * Enqueue the style sheet (let a programmer de-queue it if they want)
	 */
	public function enqueueStyles() {
		
		if ( wp_style_is( 'e20r-directory-for-pmpro-styles', 'registered' ) ) {
			wp_enqueue_style( 'e20r-directory-for-pmpro-styles' );
		} else {
			trigger_error( __( 'Can\'t load the "E20R Directory for PMPro" plugin style sheet', 'e20r-directory-for-pmpro' ), E_USER_WARNING );
		}
	}
	
	/**
	 * Load pages to include on "Memberships" -> "Pages" settings page for Paid Memberships Pro
	 *
	 * @param array $pages
	 *
	 * @return array
	 */
	public function extraPageSettings( $pages ) {
		
		$pages['directory'] = array(
			'title'   => __( 'Directory', 'e20r-directory-for-pmpro' ),
			'content' => '[e20r-directory-for-pmpro]',
			'hint'    => __( 'Include the shortcode [e20r_directory_for_pmpro].', 'e20r-directory-for-pmpro' ),
		);
		$pages['profile']   = array(
			'title'   => __( 'Profile', 'e20r-directory-for-pmpro' ),
			'content' => '[e20r-member-profile]',
			'hint'    => __( 'Include the shortcode [e20r_directory_profile].', 'e20r-directory-for-pmpro' ),
		);
		
		return $pages;
	}
	
	/**
	 * Let user decide to show/hide their entry in the directory via their profile
	 *
	 * @param \WP_User $user
	 */
	public function showExtraProfileFields( $user ) {
	 
		$is_admin = current_user_can( 'manage_options' );
		$hide_directory = (bool) get_user_meta( $user->ID, 'e20red_hide_directory', true );
		$pmpro_hide_directory = (bool) get_user_meta( $user->ID, 'pmpro_hide_directory', true );
		
		// Compatibility with the PMPro add-on setting(s)
		if ( true === $pmpro_hide_directory && false === $hide_directory ) {
		    $hide_directory = $pmpro_hide_directory;
		    update_user_meta( $user->ID, 'e20red_hide_directory', $hide_directory );
        }
        
		if ( (
			     true === apply_filters( 'e20r-directory-for-pmpro_non_admin_profile_settings', true ) &&
			     false === $is_admin
		     ) || true === $is_admin
		) { ?>
            <h3><?php _e( 'Member Directory', self::plugin_slug ); ?></h3>
            <table class="form-table">
                <tbody>
                <tr class="user-hide-directory-wrap">
                    <th scope="row"></th>
                    <td>
                        <label for="e20red_hide_directory">
                            <input name="e20red_hide_directory" type="checkbox"
                                   id="e20red_hide_directory" <?php checked( $hide_directory, 1 ); ?>
                                   value="1"><?php _e( 'Hide my profile in the Member Directory?', self::plugin_slug ); ?>
                        </label>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
		}
	}
	
	/**
	 * Save the 'exclude from directory' setting for the user (default is "don't exclude the user")
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function saveExtraProfileFields( $user_id ) {
		
		$pmpro_hide_directory = (bool) get_user_meta( $user_id, 'pmpro_hide_directory', true );
		
		// Transition the user from the PMPro 'hide_directory' variable (if necessary)
		if ( $pmpro_hide_directory ) {
		    update_user_meta( $user_id, 'e20red_hide_directory', $pmpro_hide_directory );
        }
        
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		
		$utils = Utilities::get_instance();
		$hide_directory = (bool) $utils->get_variable( 'e20red_hide_directory', false );
		
		update_user_meta( $user_id, 'e20red_hide_directory', $hide_directory );
	}
	
	/**
	 * Include our plugin's links in the plugin row meta of the plugins.php page
	 *
	 * @param array  $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function pluginRowMeta( $links, $file ) {
		
		// Not processing for this plugin
		if ( false === strpos( $file, 'e20r-directory-for-pmpro.php' ) ) {
			return $links;
		}
		
		$new_links = array(
			sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( 'http://eighty20results.com/paid-memberships-pro/e20r-directory-for-pmpro/' ),
				__( 'View Documentation', 'e20r-directory-for-pmpro' ),
				__( 'Docs', 'e20r-directory-for-pmpro' )
			),
			sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( 'http://eighty20results.com/support/' ),
				__( 'Submit Customer Support Question', 'e20r-directory-for-pmpro' ),
				__( 'Support', 'e20r-directory-for-pmpro' )
			),
		);
		
		return array_merge( $links, $new_links );
	}
}

try {
	spl_autoload_register( array( E20R_Directory_For_PMPro::getInstance(), 'autoLoader' ) );
} catch ( \Exception $exception ) {
	trigger_error( __( 'Error in loader for E20R Directory for PMPro: ' . $exception->getMessage() ), E_USER_WARNING );
}

global $e20rmd_has_billing_fields;
global $e20rmd_has_shipping_fields;
global $e20rmd_show_billing_address;
global $e20rmd_show_shipping_address;

/*
$pmpromd_path = dirname( __FILE__ ) . "/includes";
$custom_dir   = get_stylesheet_directory() . "/e20r-member-directory/";

$custom_directory_file = $custom_dir . "directory.php";
$custom_profile_file   = $custom_dir . "profile.php";

if ( function_exists( 'pmpro_loadTemplate' ) ) {
    error_log("Loading templates for Directory");
    
	pmpro_loadTemplate( 'directory', 'local', 'pages', 'php' );
	pmpro_loadTemplate( 'profile', 'local', 'pages', 'php' );
}
*/

// Load this plugin
add_action( 'plugins_loaded', array( E20R_Directory_For_PMPro::getInstance(), 'loadHooks' ) );

require_once( plugin_dir_path( __FILE__ ) . 'includes/autoload.php' );

$plugin_updates = \Puc_v4_Factory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-directory-for-pmpro/metadata.json',
	__FILE__,
	'e20r-directory-for-pmpro'
);