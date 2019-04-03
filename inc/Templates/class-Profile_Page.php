<?php
/**
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

namespace E20R\Member_Directory;

use E20R\Member_Directory\Settings\Options;
use E20R\Utilities\Utilities;

/**
 * This short code will display the profile for the user ID specified in the URL and
 *
 * Additional content based on the specified short code attributes.
 *
 * @credit https://www.paidmembershipspro.com
 */
global $e20rmd_show_billing_address;
global $e20rmd_show_shipping_address;
global $e20rmd_has_billing_fields;
global $e20rmd_has_shipping_fields;

class Profile_Page {
	
	/**
	 * Current instance of the Profile class
	 * @var null|Profile_Page
	 */
	private static $instance = null;
	
	/**
	 * List of URLs by post ID for the Profile page(s) (key: paired directory page ID)
	 *
	 * @var string[int] $profile_url
	 */
	private $profile_url = array();
	
	/**
	 * List of URLs by post ID for the Directory page(s) (key: paired profile page ID)
	 *
	 * @var string[int] $this->directory_url
	 */
	private $directory_url = array();
	
	private $limit = null;
	
	private $avatar_size = '128';
	private $fields = null;
	private $show_avatar = null;
	private $billing_address = null;
	private $shipping_address = null;
	private $show_bio = null;
	private $show_billing = null;
	private $show_email = null;
	private $show_level = null;
	private $show_name = null;
	private $show_phone = null;
	private $show_search = null;
	private $show_startdate = null;
	private $user_id = null;
	private $directory_page_slug = null;
	
	private $fields_array = array();
	
	/**
	 * Add the Profile page URL for the specific page (if applicable)
	 *
	 * @param int    $page_id
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function addURL( $page_id, $url ) {
		
		$class = self::getInstance();
		$utils = Utilities::get_instance();
		
		if ( false === wp_http_validate_url( $url ) ) {
			return false;
		}
		
		// Get the base URL for the site (used to find the page slug)
		$base_url = preg_quote( get_site_url() );
		
		// Get rid of the base URL so we're left with the page slug
		$page_slug    = preg_replace( "/{$base_url}/", '', $url );
		$profile_page = get_page_by_path( $page_slug );
		
		if ( empty( $profile_page ) ) {
			$utils->log( "Page {$page_slug} not found?!?!" );
			
			return false;
		}
		
		if ( false === self::hasShortcode( $profile_page ) ) {
			$utils->log( "Not processing a page with a profile short code on it" );
			
			return false;
		}
		
		$class->profile_url[ $page_id ] = $url;
		
		return true;
	}
	
	/**
	 * Return or instantiate and return the E20R_Profile class instance
	 *
	 * @return Profile_Page|null
	 */
	public static function getInstance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance                = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Does the supplied WP_Post (page) have one of the directory short codes embedded?
	 *
	 * @param \WP_Post $page
	 *
	 * @return bool
	 */
	public static function hasShortcode( $page ) {
		
		$utils = Utilities::get_instance();
		
		if ( ! isset( $page->post_content ) ) {
			$utils->log( "Post argument is empty!" );
			
			return false;
		}
		
		// Make sure we're processing a WP_Post object
		if ( ! is_a( $page, '\WP_Post' ) ) {
			$utils->log( "The supplied page argument isn't a WordPress Post object!" );
			
			return false;
		}
		
		if ( has_shortcode( $page->post_content, 'e20r_member_profile' ) ) {
			$utils->log( "Found the 'e20r_member_profile' short code" );
			
			return true;
		}
		
		if ( has_shortcode( $page->post_content, 'e20r-member-profile' ) ) {
			$utils->log( "Found the 'e20r-member-profile' short code" );
			
			return true;
		}
		
		if ( has_shortcode( $page->post_content, 'pmpro_member_profile' ) ) {
			$utils->log( "Found the 'pmpro_member_profile' short code" );
			
			return true;
		}
		
		$utils->log( "Could not locate a profile short code!" );
		
		return false;
	}
	
	/**
	 * Load short code/action/filter handlers for the plugin
	 */
	public function loadHooks() {
		
		add_shortcode( 'e20r-member-profile', array( $this, 'profileShortCode' ) );
		add_shortcode( 'e20r_member_profile', array( $this, 'profileShortCode' ) );
		add_shortcode( 'pmpro_member_profile', array( $this, 'profileShortCode' ) );
		
		add_action( 'wp', array( $this, 'profilePreHeader' ), 1 );
		
		add_filter( 'the_title', array( $this, 'theTitle' ), 10, 2 );
		add_filter( 'wp_title', array( $this, 'WPTitle' ), 10, 2 );
	}
	
	/**
	 * Update the head title and H1 value for the profile page
	 *
	 * @param string   $title
	 * @param null|int $post_id
	 *
	 * @return string
	 */
	public function theTitle( $title, $post_id = null ) {
		
		global $main_post_id;
		global $current_user;
		global $wpdb;
		
		$utils = Utilities::get_instance();
		
		if ( (int) $post_id !== (int) $main_post_id ) {
			return $title;
		}
		
		$user_nicename = $utils->get_variable( 'pu', null );
		
		if ( empty( $user_nicename ) ) {
			return $title;
		}
		
		if ( is_user_logged_in() ) {
			$display_name = $current_user->display_name;
			
		} else {
			
			$display_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT u.display_name
                              FROM {$wpdb->users} AS u
                              WHERE u.user_nicename = %s
                              LIMIT 1",
					$user_nicename
				)
			);
		}
		
		if ( empty( $display_name ) ) {
			return $title;
		}
		
		$title = $display_name;
		
		return $title;
	}
	
	/**
	 * Update the Title for the browser
	 *
	 * @param string $title
	 * @param string $sep
	 *
	 * @return string
	 */
	public function WPTitle( $title, $sep ) {
		
		$utils = Utilities::get_instance();
		
		global $wpdb;
		global $main_post_id;
		global $post;
		global $current_user;
		
		if ( (int) $post->ID !== (int) $main_post_id ) {
			return $title;
		}
		
		$user_nicename = $utils->get_variable( 'pu', null );
		
		if ( empty( $user_nicename ) && ! is_user_logged_in() ) {
			return $title;
		}
		
		$display_name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT u.display_name
                  FROM {$wpdb->users} AS u
                  WHERE u.user_nicename = %s
                  LIMIT 1",
				$user_nicename
			)
		);
		
		
		if ( ! empty( $display_name ) ) {
			$title = $display_name . ' ' . $sep . ' ';
		}
		
		$title .= get_bloginfo( 'name' );
		
		return $title;
	}
	
	/**
	 * Configure profile page (pre header)
	 *
	 * @since v3.0 - Using Directory / Profile page pair settings to identify the correct directory page to return to
	 */
	public function profilePreHeader() {
		
		global $post;
		global $current_user;
		
		$user = null;
		
		if ( ! isset( $post->ID ) ) {
			return;
		}
		
		if ( empty( $this->profile_url ) ) {
		    $this->setProfilePageVariables();
		}
		
		if ( get_permalink( $post ) != $this->profile_url ) {
			return;
		}
		
		$utils = Utilities::get_instance();
		/*
			Pre-header operations here.
		*/
		global $main_post_id;
		
		$main_post_id = $post->ID;
		
		//Get the user request variable (pu)
		$profile_user = $utils->get_variable( 'pu', '' );
		
		if ( empty( $profile_user ) && ! is_user_logged_in() ) {
			return;
		}
		
		if ( empty( $profile_user ) ) {
			$profile_user = $current_user->ID;
		}
		
		if ( is_numeric( $profile_user ) ) {
			$user = get_user_by( 'ID', $profile_user );
		}
		
		if ( empty( $user ) ) {
			$user = get_user_by( 'slug', $profile_user );
		}
		
		if ( empty( $user ) ) {
			$user = $current_user;
		}
		
		if ( ! self::hasShortcode( $post ) ) {
		    return;
        }
        
		$directory_url = get_permalink( Options::getDirectoryIDFromProfile( $post->ID ) );
		
		// If no profile user found, go to directory (or home, if no directory is specified)
		if ( ! isset( $user->ID ) ) {
			
			if ( ! empty( $directory_url ) ) {
				wp_redirect( $directory_url );
			} else {
				wp_redirect( home_url() );
			}
			exit;
		}
		
		/*
			If a level is required for the profile page, make sure the profile user has it.
		*/
		$levels = pmpro_getMatches( "/ levels?=[\"']([^\"^']*)[\"']/", $post->post_content, true );
		
		if ( ! empty( $levels ) && function_exists( 'pmpro_hasMembershipLevel' ) &&
		     ! pmpro_hasMembershipLevel( explode( ",", $levels ), $profile_user->ID )
		) {
			
			if ( ! empty( $directory_url ) ) {
				wp_redirect( $directory_url );
			} else {
				wp_redirect( home_url() );
			}
			exit;
		}
	}
	
	/**
	 * Configure the URLs for the directory as it relates to the current page (profile)
     *
     * @return bool
	 */
	private function setProfilePageVariables() {
	    
	    global $post;
	    $utils = Utilities::get_instance();
	    
		// Page variables
		$directory_page_id = Options::getDirectoryIDFromProfile( $post->ID );
		$profile_page_id = Options::getProfileIDFromDirectory( $directory_page_id );
		
		$utils->log("Got directory ID of {$directory_page_id} for profile ID {$post->ID}");
		$utils->log("Got profile ID of {$profile_page_id} for directory ID {$directory_page_id}");
		// Grab the default directory page if none is specified
  
		if ( empty( $directory_page_id ) ) {
			// Get the default directory page ID
			$directory_page_id = Options::getDirectoryIDFromProfile();
		}
		
		// Grab the default profile page if none is specified
		if ( empty( $profile_page_id ) ) {
			// Get the default Profile page ID
			$profile_page_id = Options::getProfileIDFromDirectory();
		}
		
		// Generate URLs as needed
		//$this->directory_url = E20R_Directory_For_PMPro::getURL( 'directory', $directory_page_id );
		//$this->profile_url   = E20R_Directory_For_PMPro::getURL( 'profile', $profile_page_id );
		
        $this->directory_url = get_permalink( $directory_page_id );
		$this->profile_url = get_permalink( $profile_page_id );
		
		
		$utils->log("Profile id: {$profile_page_id} -> {$this->profile_url}");
		$utils->log("Directory id: {$directory_page_id} -> {$this->directory_url}");
		
		if ( empty( $this->directory_url ) ) {
		    $utils->log("Error: Could not generate the URL for the 'directory' page (ID: {$directory_page_id})!");
			return false;
        }
		
		if ( empty( $this->profile_url ) ) {
			$utils->log("Error: Could not generate the URL for the 'profile' page (ID: {$profile_page_id})!");
			return false;
		}
		
		return true;
    }
    
	/**
	 * Process the Profile short code
	 *
	 * @param        $atts
	 * @param null   $content
	 * @param string $code
	 *
	 * @return false|string
	 */
	function profileShortCode( $atts, $content = null, $code = "" ) {
		
		$utils = Utilities::get_instance();
		
		// $atts    ::= array of attributes
		// $content ::= text within enclosing form of shortcode element
		// $code    ::= the shortcode found, when == callback name
		// examples: [e20r-member-profile avatar="false" email="false"]
		
		$shortcode_attributes = shortcode_atts( array(
			'avatar_size'         => '128',
			'fields'              => null,
			'billing_address'     => 'false',
			'shipping_address'    => 'false',
			'show_avatar'         => 'true',
			'show_bio'            => 'true',
			'show_billing'        => 'true',
			'show_email'          => 'true',
			'show_level'          => 'true',
			'show_name'           => 'true',
			'show_phone'          => 'true',
			'show_search'         => 'true',
			'show_startdate'      => 'true',
			'user_id'             => null,
			'directory_page_slug' => null,
		), $atts );
		
		// Save all attribute values for the
		foreach ( $shortcode_attributes as $attribute => $value ) {
			$this->{$attribute} = $value;
		}
		
		global $current_user;
		global $pmpro_pages;
		global $pmprorh_registration_fields;
		global $post;
		
		global $e20rmd_show_billing_address;
		global $e20rmd_show_shipping_address;
		
		// Configure the page variables for the Profile page
		if ( false === $this->setProfilePageVariables() ) {
		    $utils->add_message(
		            sprintf(
		                    __( 'Error loading the "%s" profile page (ID: %d)', E20R_Directory_For_PMPro::plugin_slug ),
                            $post->post_title,
                            $post->ID
                    ),
                    'error',
                    'backend'
            );
		    return null;
        }
		
		/**
		 * Use the supplied page slug as the profile page instead (if available)
		 */
		if ( ! empty( $this->directory_page_slug ) ) {
			
			$directory_page = get_page_by_path( $this->directory_page_slug );
			
			if ( empty( $directory_page ) ) {
				$utils->add_message(
					__(
						'Invalid path given for the E20R Member Directory page! Please change the \'directory_page_slug=""\' attribute on the PMPro Profile page',
						E20R_Directory_For_PMPro::plugin_slug
					),
					'error',
					'backend'
				);
				
				return null;
			}
			
			$this->directory_url = get_permalink( $directory_page->ID );
		}
		
		if ( empty( $this->directory_url ) ) {
			$utils->add_message(
				__(
					'Invalid path given for the E20R Member Directory page! Please update the Profile page settings on the "Memberships" -> "Settings" -> "Pages" page',
					E20R_Directory_For_PMPro::plugin_slug
				),
				'error',
				'backend'
			);
		}
		
		//turn 0's into falses
		$this->show_avatar      = E20R_Directory_For_PMPro::trueFalse( $this->show_avatar );
		$this->show_billing     = E20R_Directory_For_PMPro::trueFalse( $this->show_billing );
		$this->show_bio         = E20R_Directory_For_PMPro::trueFalse( $this->show_bio );
		$this->show_email       = E20R_Directory_For_PMPro::trueFalse( $this->show_email );
		$this->show_level       = E20R_Directory_For_PMPro::trueFalse( $this->show_level );
		$this->show_name        = E20R_Directory_For_PMPro::trueFalse( $this->show_name );
		$this->show_phone       = E20R_Directory_For_PMPro::trueFalse( $this->show_phone );
		$this->show_search      = E20R_Directory_For_PMPro::trueFalse( $this->show_search );
		$this->show_startdate   = E20R_Directory_For_PMPro::trueFalse( $this->show_startdate );
		$this->billing_address  = E20R_Directory_For_PMPro::trueFalse( $this->billing_address );
		$this->shipping_address = E20R_Directory_For_PMPro::trueFalse( $this->shipping_address );
		
		$e20rmd_show_billing_address  = $this->billing_address;
		$e20rmd_show_shipping_address = $this->shipping_address;
		
		$utils->log( "Show billing address in own section? " . ( $e20rmd_show_billing_address ? 'Yes' : 'No' ) );
		
		$this->limit = $utils->get_variable( 'page_size', 15 );
		
		$pu = $utils->get_variable( 'pu', null );
		
		if ( empty( $user_id ) && ! empty( $pu ) ) {
			
			// Retrieve the user
			if ( is_numeric( $pu ) ) {
				$profile_user = get_user_by( 'id', $pu );
			} else if ( is_email( $pu ) ) {
				$profile_user = get_user_by( 'email', $pu );
			} else {
				$profile_user = get_user_by( 'slug', $pu );
			}
			
			if ( ! empty( $profile_user ) ) {
				$user_id = $profile_user->ID;
			}
		}
		
		// Load the specified user ID
		if ( ! empty( $user_id ) ) {
			$profile_user = get_userdata( $user_id );
		} else if ( empty( $pu ) ) {
			// Defaulting to current user...
			$profile_user = get_userdata( $current_user->ID );
		}
		
		if ( ! empty( $profile_user ) ) {
			$profile_user->membership_level = pmpro_getMembershipLevelForUser( $profile_user->ID );
		}
		
		$ps = $utils->get_variable( 'ps', null );
		
		ob_start(); ?>
		<?php if ( ! empty( $this->show_search ) ) { ?>
            <form action="<?php echo esc_url_raw( $this->directory_url ); ?>" method="post" role="search"
                  class="e20r-directory-for-pmpro_search search-form">
                <label>
                    <span class="screen-reader-text"><?php _e( 'Search for:', 'label' ); ?></span>
                    <input type="search" class="search-field"
                           placeholder="<?php _e( "Search Members", "e20r-directory-for-pmpro" ); ?>" name="ps"
                           value="<?php esc_attr_e( $ps ); ?>"
                           title="<?php _e( "Search Members", "e20r-directory-for-pmpro" ); ?>"/>
                    <input type="hidden" name="limit" value="<?php esc_attr_e( $this->limit ); ?>"/>
                </label>
				<?php
				$search_fields = apply_filters( 'e20r-directory-for-pmpro_extra_search_input', array() );
				
				if ( ! empty( $search_fields ) ) {
					
					if ( ! empty( $search_fields ) && is_array( $search_fields ) ) {
						foreach ( $search_fields as $search_field ) {
							printf( '%s', $search_field );
						}
					}
				}
				do_action( 'e20r-directory-for-pmpro_extra_search_output' ); ?>
                <input type="submit" class="search-submit"
                       value="<?php _e( "Search Members", "e20r-directory-for-pmpro" ); ?>">
            </form>
            <span class="clear"></span>
		<?php } ?>
		<?php
		if ( ! empty( $profile_user ) ) {
			if ( ! empty( $this->fields ) ) {
				
				$this->fields_array = explode( ";", $this->fields );
				
				if ( ! empty( $this->fields_array ) ) {
					for ( $i = 0; $i < count( $this->fields_array ); $i ++ ) {
						$this->fields_array[ $i ] = explode( ",", $this->fields_array[ $i ] );
					}
				}
			} else {
				$this->fields_array = array();
			}
			
			// Get Register Helper field options
			$rh_fields = array();
			if ( ! empty( $pmprorh_registration_fields ) ) {
				foreach ( $pmprorh_registration_fields as $location ) {
					foreach ( $location as $field ) {
						if ( ! empty( $field->options ) ) {
							$rh_fields[ $field->name ] = $field->options;
						}
					}
				}
			} ?>
            <div id="e20r-member-profile-<?php esc_attr_e( $profile_user->ID ); ?>" class="e20r-member-profile">
				<?php if ( true === $this->show_avatar ) { ?>
                    <p class="e20r-directory-for-pmpro_avatar">
						<?php echo get_avatar( $profile_user->ID, $this->avatar_size, null, $profile_user->display_name, array( "class" => "alignright" ) ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_name && ! empty( $profile_user->display_name ) ) { ?>
                    <h2 class="e20r-directory-for-pmpro_name">
						<?php esc_html_e( $profile_user->display_name ); ?>
                    </h2>
				<?php } ?>
				<?php if ( true === $this->show_bio && ! empty( $profile_user->description ) ) { ?>
                    <p class="e20r-directory-for-pmpro_bio">
                        <strong><?php _e( 'Biographical Info', 'wp' ); ?></strong>
						<?php esc_html_e( $profile_user->description ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_email ) { ?>
                    <p class="e20r-directory-for-pmpro_email">
                        <strong><?php _e( 'Email Address', 'pmpro' ); ?></strong>
						<?php esc_html_e( $profile_user->user_email ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_level && ! empty( $profile_user->membership_level->name ) ) { ?>
                    <p class="e20r-directory-for-pmpro_level">
                        <strong><?php _e( 'Level', 'pmpro' ); ?></strong>
						<?php esc_html_e( $profile_user->membership_level->name ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_startdate && ! empty( $profile_user->membership_level->startdate ) ) { ?>
                    <p class="e20r-directory-for-pmpro_date">
                        <strong><?php _e( 'Start Date', 'pmpro' ); ?></strong>
						<?php echo date_i18n( get_option( "date_format" ), $profile_user->membership_level->startdate ); ?>
                    </p>
				<?php } ?>
				<?php if ( ( true === $this->show_billing && ( false === $e20rmd_show_billing_address && false === $e20rmd_show_shipping_address ) ) && ! empty( $profile_user->pmpro_baddress1 ) ) { ?>
                    <p class="e20r-directory-for-pmpro_baddress">
                        <strong><?php _e( 'Address', 'pmpro' ); ?></strong>
						<?php esc_html_e( $profile_user->pmpro_baddress1 ); ?><br/>
						<?php
						if ( ! empty( $profile_user->pmpro_baddress2 ) ) {
							esc_html_e( "{$profile_user->pmpro_baddress2})<br />" );
						}
						?>
						<?php if ( $profile_user->pmpro_bcity && $profile_user->pmpro_bstate ) { ?>
							<?php esc_html_e( $profile_user->pmpro_bcity ); ?>, <?php esc_html_e( $profile_user->pmpro_bstate ); ?><?php esc_html_e( $profile_user->pmpro_bzipcode ); ?>
                            <br/>
							<?php esc_html_e( $profile_user->pmpro_bcountry ); ?><br/>
						<?php } ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_phone && ! empty( $profile_user->pmpro_bphone ) ) { ?>
                    <p class="e20r-directory-for-pmpro_phone">
                        <strong><?php _e( 'Phone Number', 'pmpro' ); ?></strong>
						<?php echo formatPhone( $profile_user->pmpro_bphone ); ?>
                    </p>
				<?php } ?>
				<?php
				// Save a copy of the extracted fields (for the e20rmd_add_extra_profile_output action)
				$real_fields_array = $this->fields_array;
				
				//filter the fields
				$this->fields_array = apply_filters( 'e20r-member-profile_fields', $this->fields_array, $profile_user );
				
				if ( is_array( $this->fields_array ) && ! empty( $this->fields_array ) ) {
					foreach ( $this->fields_array as $field ) {
						if ( empty( $field[0] ) ) {
							break;
						}
						error_log( "Field info: " . print_r( $field, true ) );
						
						$meta_field = wp_unslash( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $profile_user->{$field[1]}, $field[1], $profile_user ) );
						if ( ! empty( $meta_field ) ) {
							?>
                            <p class="e20r-directory-for-pmpro_<?php echo esc_attr( $field[1] ); ?>">
								<?php
								if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
									//this is a file field
									?>
                                    <strong><?php esc_html_e( $field[0] ); ?></strong>
									<?php echo E20R_Directory_For_PMPro::displayFileField( $meta_field ); ?>
									<?php
								} else if ( is_array( $meta_field ) ) {
									//this is a general array, check for Register Helper options first
									if ( ! empty( $rh_fields[ $field[1] ] ) ) {
										foreach ( $meta_field as $key => $value ) {
											$meta_field[ $key ] = $rh_fields[ $field[1] ][ $value ];
										}
									}
									?>
                                    <strong><?php esc_attr_e( $field[0] ); ?></strong>
									<?php echo apply_filters( 'e20r-directory-for-pmpro_metafield_value', implode( ", ", $meta_field ), $field[1], $profile_user ); ?>
									<?php
								} else {
									if ( false !== stripos( $field[1], 'url' ) ) {
										?>
                                        <a href="<?php echo esc_url( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $meta_field, $field[1], $profile_user ) ); ?>"
                                           target="_blank"><?php esc_html_e( $field[0] ); ?></a>
										<?php
									} else {
										?>
                                        <strong><?php esc_html_e( $field[0] ); ?></strong>
										<?php
										$meta_field_embed = wp_oembed_get( $meta_field );
										if ( ! empty( $meta_field_embed ) ) {
											echo apply_filters( 'e20r-directory-for-pmpro_metafield_value', $meta_field_embed, $field[1], $profile_user );
										} else {
											echo make_clickable( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $meta_field, $field[1], $profile_user ) );
										}
										?>
										<?php
									}
								}
								?>
                            </p>
							<?php
						}
					}
				}
				
				do_action( 'e20rmd_add_extra_profile_output', $real_fields_array, $profile_user );
				?>

                <div class="pmpro_clear"></div>
            </div>
            <hr/>
			<?php if ( apply_filters( 'e20r-directory-for-pmpro_profile_show_return_link', true ) && ! empty( $this->directory_url ) ) { ?>
                <div align="center">
                    <a class="more-link" href="<?php echo esc_url_raw( $this->directory_url ); ?>">
						<?php _e( 'View All Members', 'e20r-directory-for-pmpro' ); ?>
                    </a>
                </div>
			<?php } ?>
			<?php
		}
		?>
		<?php
		$temp_content = ob_get_contents();
		ob_end_clean();
		
		return $temp_content;
	}
}