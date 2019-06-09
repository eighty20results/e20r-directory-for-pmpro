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

use E20R\Member_Directory\Database\Select;
use E20R\Member_Directory\Settings\Options;
use E20R\Utilities\Cache;
use E20R\Utilities\Utilities;

/**
 * This shortcode will display the members list and additional content based on the defined attributes.
 *
 * @credit https://www.paidmembershipspro.com
 */
class Directory_Page extends Template_Page {
	
	/**
	 * The current instance of this class
	 *
	 * @var null|Directory_Page $instance
	 */
	private static $instance = null;
	
	/**
     * Key for the member directory data cache
     *
	 * @var null|string
	 */
	public $cache_key = null;
	
	/**
	 * The default DB Column definitions to use in query
	 *
	 * @var array $columns
	 */
	private $columns = array();
	
	/**
     * Found record count (from the DB)
     *
	 * @var null|int
	 */
	private $total_in_db = null;
	
	/**
	 * List of configured portions of the SQL WHERE clause
	 *
	 * @var array $where_list
	 */
	private $where_list = array();
	
	/**
	 * The configured layout structure for the directory listing
	 * @var string $layout - div|table|2col|3col|4col
	 */
	private $layout = 'div';
	
	/**
	 * List of membership level(s) to display
	 *
	 * @var null|int[] $level |$levels
	 */
	private $level = null;
	private $levels = null;
	
	/**
	 * Whether to display a paginated directory page
	 *
	 * @var bool
	 */
	private $paginated = false;
	
	/**
	 * @var null
	 */
	private $link = null;
	
	/**
	 * WP_User or WP User meta data field to sort by. Default is: display_name
	 * Supported sort options: 'user_email', 'display_name', 'user_login', 'user_registered', 'membership_id',
	 * 'startdate', 'joindate', 'last_name', 'first_name'
	 *
	 * @var string $order_by
	 */
	private $order_by = 'display_name';
	
	/**
	 * Sort order for the directory (ASC|DESC)
	 *
	 * @var string
	 */
	private $order = 'ASC';
	
	/**
	 * Whether to display the WP Role(s) the member/user belongs to
	 *
	 * @var bool
	 */
	private $show_roles = false;
	
	/**
	 * Show/hide the link on the directory page to a logged in user's editable profile data (backend) page
	 * (front-end if a plugin like Theme My Login w/the Profile add-on is installed and active)
	 *
	 * @var bool
	 */
	private $members_only_link = true;
	
	/**
	 * Send the logged in user to their editable WP User profile when clicking their own link when true
	 *
	 * @var bool
	 */
	private $editable_profile = false;
	
	/**
	 * The slug of the Member Directory Profile page to send the user to (overrides the "Membership" -> "Settings" ->
	 * "Page Settings" configuration for this directory page)
     *
	 * @var null|string
	 */
	private $profile_page_slug = null;
	
	/**
     * Filter returned results in the directory based on WP User Meta key/value pair (this is the key)
     *
	 * @var null|string
	 */
	private $filter_key_name = null;
	
	/**
     * Filter returned results in the directory based on WP User Meta key/value pair (this is the value)
     *
	 * @var null|string
	 */
	private $filter_key_value = null;
	
	
	/**
     * URL to this directory page
     *
	 * @var null|string
	 */
	private $directory_url = null;
	
	/**
     * URL to the linked profile page
     *
	 * @var null|string
	 */
	private $profile_url = null;
	
	/**
     * Display the link to the user's profile page (show = 1|yes|true, hide = 0|no|false)
     *
	 * @var bool
	 */
	private $show_link = false;
	
	/**
     * Do not allow editing of profile data (regardless)
     *
	 * @var null|bool
	 */
	private $read_only_profile = null;
	
	/**
     * In metadata search, require a perfect match to the search string in order to return the record as a valid result
     *
	 * @var bool
	 */
	private $use_precise_values = false;
	
	/**
     * List of member statuses that can be displayed
     *
	 * @var null|array
	 */
	private $statuses = null;
	
	/**
     * List of search fields to add to the search query (additional fields to search + use_precise_values means more granularity in search)
     *
	 * @var array
	 */
	private $extra_search_fields = array();
	
	/**
     * Current page number for paginated results
     *
	 * @var int
	 */
	private $page_number = 1;
	
	/**
	 * @var int
	 */
	private $start = 0;
	
	/**
	 * @var int
	 */
	private $end = 0;
	
	/**
	 * Directory_Page constructor.
     *
     * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Load Short code, Action and Filter handlers for this class
	 */
	public function loadHooks() {
		
		$type = 'directory';
		
		$short_codes = apply_filters( 'e20r-directory-supported-shortcodes', array(
		        sprintf( 'e20r-%1$s-for-pmpro', $type ),
				sprintf( 'e20r_member_%1$s', $type ),
				sprintf( 'e20r-member-%1$s', $type ),
				sprintf( 'pmpro_member_%1$s', $type ),
			)
		);
		
		foreach( $short_codes as $short_code ) {
			add_shortcode( $short_code, array( $this, 'shortcode' ) );
		}
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
	public function shortcode( $atts, $content = null, $code = "" ) {
		
		$utils = Utilities::get_instance();
		
		// $atts    ::= array of attributes
		// $content ::= text within enclosing form of shortcode element
		// $code    ::= the shortcode found, when == callback name
		// examples: [e20r-member-directory show_avatar="false" show_email="false" levels="1,2"]
		
		$this->processAttributes( $atts );
		
		global $post;
		global $pmpro_pages;
		global $pmprorh_registration_fields;
		global $current_user;
		
		$this->search = $utils->get_variable( 'ps', null );
		unset( $_REQUEST['ps'] );
		
		if ( ! empty( $this->search ) ) {
			$utils->log( "The cache needs to be cleared when we search" );
			E20R_Directory_For_PMPro::clearCache();
		}
		
		$utils->log( "Search string: {$this->search}" );
		
		// Find level(s) to display (if configured)
		$levels = ! empty( $this->levels ) ? array_map( 'intval', array_map( 'trim', explode( ',', $this->levels ) ) ) : null;
		$level  = ! empty( $this->level ) ? intval( $this->level ) : null;
		
		$this->levels = empty( $levels ) && ! empty( $level ) ? $level : $levels;
		
		// Present a non-empty level ID list as an array
		if ( ! empty( $this->levels ) && ! is_array( $this->levels ) ) {
			$this->levels = array( $this->levels );
		}
		
		// Set & sanitize request values
		$this->page_number = $utils->get_variable( 'page_number', 1 );
		$page_size         = $utils->get_variable( 'page_size', null );
		
		if ( null !== $page_size ) {
			$utils->log( "Page size value specified in URL" );
			$this->page_size = $page_size;
		}
		
		if ( ! empty( $this->page_size ) ) {
			$utils->log( "Calculating pagination start/end" );
			$this->end   = $this->page_number * $this->page_size;
			$this->start = $this->end - $this->page_size;
		}
		
		/**
		 * Let developer set/update/override the levels to include
		 *
		 * @filter e20r-directory-for-pmpro_included_levels
		 *
		 * @param int[] $levels - The membership level IDs to include user(s) for
		 */
		$this->levels = apply_filters( 'e20r-directory-for-pmpro_included_levels', $this->levels );
		
		$this->link              = E20R_Directory_For_PMPro::trueFalse( $this->link );
		$this->show_avatar       = E20R_Directory_For_PMPro::trueFalse( $this->show_avatar );
		$this->show_email        = E20R_Directory_For_PMPro::trueFalse( $this->show_email );
		$this->show_level        = E20R_Directory_For_PMPro::trueFalse( $this->show_level );
		$this->show_search       = E20R_Directory_For_PMPro::trueFalse( $this->show_search );
		$this->show_startdate    = E20R_Directory_For_PMPro::trueFalse( $this->show_startdate );
		$this->members_only_link = E20R_Directory_For_PMPro::trueFalse( $this->members_only_link );
		$this->editable_profile  = E20R_Directory_For_PMPro::trueFalse( $this->editable_profile );
		$this->paginated         = E20R_Directory_For_PMPro::trueFalse( $this->paginated );
		
		// Configure the page variables for the Profile page
		if ( false === $this->setDirectoryPageVariables() ) {
			
			$utils->add_message(
				sprintf(
					__( 'Error loading the "%s" directory page (ID: %d)', E20R_Directory_For_PMPro::plugin_slug ),
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
		if ( ! empty( $this->profile_page_slug ) ) {
			
			$profile_page = get_page_by_path( $this->profile_page_slug );
			
			if ( empty( $profile_page ) ) {
				$utils->add_message(
					__(
						'Invalid path given for the E20R Member Directory Profile page! Please change the \'profile_page_slug=""\' attribute on the PMPro Profile page',
						E20R_Directory_For_PMPro::plugin_slug
					),
					'error',
					'backend'
				);
				
				return null;
			}
			
			$this->profile_url = get_permalink( $profile_page->ID );
		}
		
		if ( empty( $this->profile_url ) ) {
			$utils->add_message(
				__(
					'Invalid path given for the E20R Member Directory page! Please update the Profile page settings on the "Memberships" -> "Settings" -> "Pages" page',
					E20R_Directory_For_PMPro::plugin_slug
				),
				'error',
				'backend'
			);
		}
		
		/**
		 * Allow overriding of the URL to the profile page (i.e. override the $pmpro_pages['profile'] setting)
		 *
		 * @filter e20r-directory-for-pmpro-profile-url
		 *
		 * @param string $profile_url - The URL to the profile page (default is either the $pmpro_pages['profile'] permalink,
		 *                            or the page found at the specified 'profile_page_slug' attribute value
		 */
		$this->profile_url = apply_filters( 'e20r-directory-for-pmpro-profile-url', $this->profile_url, $post );
		
		/**
		 * Allow overriding of the URL to the directory page (i.e. override the $pmpro_pages['directory'] setting)
		 *
		 * @filter e20r-directory-for-pmpro-directory-url
		 *
		 * @param string $directory_url - The URL to the directory page (default is the $pmpro_pages['directory'] permalink)
		 */
		$this->directory_url = apply_filters( 'e20r-directory-for-pmpro-directory-url', $this->directory_url, $post );
		
		// Look up the members
		$members = $this->findMembers();
		// $total_found = count( $members );
		
		//update end to match totalrows if total rows is small
		if ( $this->total_in_db < $this->end ) {
			$this->end = $this->total_in_db;
		}
		
		// Grab the membership level for the current user
		$member_level    = function_exists( 'pmpro_hasMembershipLevel' ) ? pmpro_hasMembershipLevel( null, $current_user->ID ) : false;
		$this->show_link = false;
		
		if ( true === $this->link && false === $this->members_only_link ) {
			$this->show_link = true;
		} else if ( true === $this->link && true === $this->members_only_link && ! empty( $member_level ) ) {
			$this->show_link = true;
		}
		
		
		//$show_link = ( true === $link || ( ( $members_only_link && ( ! empty( $member_level ) ) ) ||
		//               ( true === $link && false === $members_only_link ) ) );
		
		ob_start();
		
		if ( true === $this->show_search ) {
			echo $this->addSearchForm();
		} ?>
        <h3 id="e20r-directory-for-pmpro_subheading">
			<?php if ( ! empty( $this->roles ) ) { ?>
				<?php printf( __( 'Viewing All %s Profiles.', E20R_Directory_For_PMPro::plugin_slug ), implode( ', ', array_map( 'ucwords', $this->roles ) ) ); ?>
			<?php } else if ( ! empty( $search ) ) { ?>
				<?php printf( __( 'Profiles Within <em>%s</em>.', E20R_Directory_For_PMPro::plugin_slug ), ucwords( esc_html( $search ) ) ); ?>
			<?php } else { ?>
				<?php _e( 'Viewing All Profiles.', E20R_Directory_For_PMPro::plugin_slug ); ?>
			<?php }
			self::addResultString(); ?>
        </h3> <?php
		if ( ! empty( $members ) ) {
			if ( ! empty( $this->fields ) ) {
				
				$this->fields_array = explode( ";", $this->fields );
				
				if ( ! empty( $this->fields_array ) ) {
					for ( $i = 0; $i < count( $this->fields_array ); $i ++ ) {
						$this->fields_array[ $i ] = explode( ",", trim( $this->fields_array[ $i ] ) );
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
            <div class="e20r-directory-for-pmpro">
                <hr class="clear"/> <?php
				
				if ( $this->layout == "table" ) {
					echo $this->generateTableLayout( $members );
				} else {
					echo $this->generateDivLayout( $members );
				} ?>
            </div> <!-- end e20r-directory-for-pmpro -->
		<?php } else { ?>
            <p class="e20r-directory-for-pmpro_message pmpro_message pmpro_error">
				<?php _e( 'No matching member profiles found', E20R_Directory_For_PMPro::plugin_slug ); ?>
				<?php
				if ( ! empty( $s ) ) {
					printf( __( 'within <em>%s</em>.', E20R_Directory_For_PMPro::plugin_slug ), ucwords( esc_html( $s ) ) );
					if ( ! empty( $directory_url ) ) {
						?>
                        <a class="more-link"
                           href="<?php echo esc_url_raw( $directory_url ); ?>"><?php _e( 'View All Members', E20R_Directory_For_PMPro::plugin_slug ); ?></a>
						<?php
					}
				} else {
					echo ".";
				} ?>
            </p>
		<?php } ?>
		<?php self::addResultString(); ?>
        <div class="pmpro_pagination">
			<?php $this->prevNextLinks( $this->page_number, $this->end ); ?>
        </div> <?php
		$temp_content = ob_get_contents();
		ob_end_clean();
		
		return $temp_content;
	}
	
	/**
	 * Iterate through all of the shorcode attributes and configure this class
	 *
	 * @param array $atts
	 */
	private function processAttributes( $atts ) {
		
		$shortcode_attributes = shortcode_atts( array(
			'avatar_size'       => '128',
			'fields'            => null,
			'layout'            => 'div',
			'level'             => null,
			'levels'            => null,
			'paginated'         => 'true',
			'link'              => 'true',
			'order_by'          => 'display_name',
			'order'             => 'ASC',
			'show_avatar'       => 'true',
			'show_email'        => 'true',
			'show_level'        => 'true',
			'show_search'       => 'true',
			'show_startdate'    => 'true',
			'page_size'         => 15,
			'show_roles'        => 'false',
			'members_only_link' => 'false',
			'editable_profile'  => 'false',
			'profile_page_slug' => null,
			'filter_key_name'   => null,
			'filter_key_value'  => null,
		), $atts );
		
		foreach ( $shortcode_attributes as $name => $value ) {
			$this->{$name} = $value;
		}
	}
	
	/**
	 * Configure the URLs for the directory as it relates to the current page (profile)
	 *
	 * @return bool
	 */
	private function setDirectoryPageVariables() {
		
		global $post;
		$utils = Utilities::get_instance();
		
		// Page variables
		$directory_page_id = isset( $post->ID ) ? $post->ID : null;
		$profile_page_id   = Options::getProfileIDFromDirectory( $directory_page_id );
		
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
		
		// Generate URLs
		$this->directory_url     = E20R_Directory_For_PMPro::getURL( 'directory', $directory_page_id );
		$this->read_only_profile = E20R_Directory_For_PMPro::getURL( 'profile', $profile_page_id );
//		$this->directory_url     = get_permalink( $directory_page_id );
//		$this->read_only_profile = get_permalink( $profile_page_id );
		$this->profile_url = $this->read_only_profile;
		
		if ( empty( $this->directory_url ) ) {
			$utils->log( "Error: Could not generate the URL for the 'directory' page (ID: {$directory_page_id})!" );
			
			return false;
		}
		
		if ( empty( $this->profile_url ) ) {
			$utils->log( "Error: Could not generate the URL for the 'profile' page (ID: {$profile_page_id})!" );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Load members from DB and filter by role if necessary
	 *
	 * @return array|bool
	 */
	public function findMembers() {
		
		$this->roles = ( true === $this->show_roles ?
			array_map( 'trim', explode( ',', strtolower( $this->show_roles ) ) ) :
			array()
		);
		
		$members = $this->readFromDB();
		$members = $this->maybeFilterByRole( $members );
		
		return $members;
	}
	
	/**
	 * Read data from Cache or from the DB itself (if needed)
	 **
	 * @return array|bool
	 *
	 * @uses e20r-directory-statuses
	 * @uses pmpromd_membership_statuses (backward compatibility with PMPro's Member Directory add-on)
	 * @uses e20r-directory-for-pmpro-exact-search-values
	 * @uses pmpromd_exact_search_values (backward compatibility with previous PMPro Extended Membership Directory
	 *       plugin)
	 * @uses e20r-directory-for-pmpro-sql-where-clause
	 * @uses e20r-directory-for-pmpro-set-order
	 * @uses e20r-directory-for-pmpro-sql-statement
	 */
	private function readFromDB() {
		
		$utils     = Utilities::get_instance();
		$cache_key = E20R_Directory_For_PMPro::getCacheKey( $this->levels, $this->search );
		
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$utils->log( "In DEBUG mode so emptying cache" );
			E20R_Directory_For_PMPro::clearCache();
		}
		
		if ( null !== ( $members = Cache::get( $cache_key, 'e20rmdp' ) ) ) {
			
			$utils->log( "Found member list in cache for key {$cache_key}!" );
			
			return $members;
		}
		
		$utils->log( "No members found in cache (key: {$cache_key})" );
		
		global $wpdb;
		
		$this->defaultColumns();
		
		$select = new Select();
		$joins  = $this->defaultJoins();
		
		/**
		 * Member statuses to include in the search (default is 'active' only)
		 *
		 * @filter e20r-directory-statuses
		 *
		 * @param array $statuses
		 */
		$this->statuses = apply_filters( 'e20r-directory-statuses', array( 'active' ) );
		
		// Backwards compatibility for the 'e20r-directory-statuses' filter
		$this->statuses = apply_filters( 'pmpromd_membership_statuses', $this->statuses );
		
		/**
		 * Whether to use the exact value specified (useful for drop-down based metavalues)
		 *
		 * @filter e20r-directory-for-pmpro-exact-search-values
		 *
		 * @param bool - False (uses 'LIKE' with wildcard comparisons for metadata
		 */
		$this->use_precise_values = apply_filters( 'e20r-directory-for-pmpro-exact-search-values', $this->use_precise_values );
		
		/**
		 * Backwards compatibility for the e20r-directory-for-pmpro-exact-search-values filter
		 */
		$this->use_precise_values = apply_filters( 'pmpromd_exact_search_values', $this->use_precise_values );
		
		$where_counter = 0;
		
		// Simplify record count
		$select->addAttribute( 'SQL_CALC_FOUND_ROWS' );
		
		// Add table to search FROM
		$from = array( 'name' => 'users', 'alias' => 'u' );
		$select->addFrom( $from );
		
		// Add all columns
		$select->addColumns( $this->columns );
		
		$utils->log( "Add filter for meta key/value" );
		$where_counter = $this->maybeAddMetaFilter( $select, $where_counter );
		
		// Include extra search field(s) with JOIN statements
		$joins = $this->processExtraSearchFields( $joins );
		
		// Add all SQL JOIN statements
		foreach ( $joins as $j_key => $join_clause ) {
			
			$utils->log( "Adding JOIN clause #{$j_key}..." );
			$select->addJoin( $join_clause );
		}
		
		$utils->log( "May add a level check (if levels specified in Short Code)" );
		$where_counter = $this->maybeAddLevelCheckToWhere( $select, $where_counter );
		
		$utils->log( "Add status clause to WHERE" );
		$where_counter = $this->addUserStatusCheck( $select, $where_counter );
		
		$utils->log( "Maybe add Hide User In Directory check" );
		$where_counter = $this->addHiddenUserCheck( $select, $where_counter );
		
		// User is searching for something, so add search...
		$utils->log( "Perhaps adding search parameters to the query" );
		$where_counter = $this->maybeAddSearch( $select, $where_counter );
		
		$utils->log( "Maybe add Extra search field info to WHERE clause" );
		
		// Add any extra Fields we're searching for to the WHERE clause when applicable
		$where_counter = $this->maybeAddExtraSearchFieldClauses(
			$select,
			$where_counter
		);
		
		// Maybe add a list of level IDs to limit the search by
		$where_counter = $this->maybeAddLevelWhere( $select, $where_counter );
		
		// Any GROUP BY statements (when using extra search field(s))
		if ( ! empty( $this->search ) || ! empty( $this->extra_search_fields ) ) {
			
		    $utils->log("Add GROUP BY clause");
		    
			$group_by_clause = array(
				'column'      => 'ID',
				'table_alias' => 'u',
			);
			
			$select->addGroupBy( $group_by_clause );
		}
		
		foreach ( $this->where_list as $w_key => $where_clause ) {
			
			$utils->log( "Adding WHERE clause # {$w_key}" );
			
			$select->addWhere(
				apply_filters(
					'e20r-directory-for-pmpro-sql-where-clause',
					$where_clause,
					$this->levels,
					$this->extra_search_fields,
					$this->statuses
				)
			);
		}
		
		$order_by_clause = apply_filters(
			'e20r-directory-for-pmpro-set-order',
			array( 'column' => $this->getOrderByCol( $this->order_by ) ),
			$this->order_by,
			$this->order
		);
		
		$select->addOrderBy( $order_by_clause, $this->order );
		
		// Add a LIMIT statement?
		if ( true === $this->paginated || 15 != $this->page_size ) {
			
			$limit_clause = array(
				'pagination' => $this->paginated,
				'start'      => $this->start,
				'end'        => $this->end,
				'results'    => null,
			);
			
			$utils->log( "Adding pagination/page limit(s): " . print_r( $limit_clause, true ) );
			
			$select->addLimit( $limit_clause );
		}
		
		/**
		 * Filter to update/change the SQL statement before it's executed (extracted form the Select() class)
		 *
		 * @filter e20r-directory-for-pmpro-sql-statement
		 *
		 * @param string $sql_statement
		 * @param int[]  $levels
		 * @param string $search_string
		 * @param int    $page_number
		 * @param bool   $paginated
		 * @param int    $page_size
		 * @param int    $start
		 * @param int    $end
		 * @param string $order_by
		 * @param string $order [ASC|DESC]
		 */
		$sqlQuery = apply_filters( "e20r-directory-for-pmpro-sql-statement",
			$select->getStatement(),
			$this->levels,
			$this->search,
			$this->page_number,
			$this->paginated,
			$this->page_size,
			$this->start,
			$this->end,
			$this->order_by,
			$this->order
		);
		
		$utils->log( "Query for Directory search: " . $sqlQuery );
		
		$members           = $wpdb->get_results( $sqlQuery );
		$this->total_in_db = $wpdb->get_var( "SELECT FOUND_ROWS() AS found_rows" );
		
		$utils->log( "Found {$this->total_in_db} records" );
		
		if ( ! empty( $members ) ) {
			Cache::set( $cache_key, $members, HOUR_IN_SECONDS, 'e20rmdp' );
		}
		
		return $members;
	}
	
	/**
	 * Generate columns to use for directory and save it to the $columns class variable
	 *
	 * @uses e20r-directory-default-column-defs
	 */
	private function defaultColumns() {
		
		$this->columns = array(
			array(
				'order'  => 0,
				'prefix' => 'DISTINCT u',
				'column' => 'ID',
				'alias'  => 'ID',
			),
			array(
				'order'  => 1,
				'prefix' => 'u',
				'column' => 'user_login',
				'alias'  => null,
			),
			array(
				'order'  => 2,
				'prefix' => 'u',
				'column' => 'user_email',
				'alias'  => null,
			),
			array(
				'order'  => 3,
				'prefix' => 'u',
				'column' => 'user_nicename',
				'alias'  => null,
			),
			array(
				'order'  => 4,
				'prefix' => 'u',
				'column' => 'display_name',
				'alias'  => null,
			),
			array(
				'order'  => 5,
				'prefix' => 'u',
				'column' => 'user_registered',
				'alias'  => 'joindate',
			),
			array(
				'order'  => 6,
				'prefix' => 'mu',
				'column' => 'membership_id',
				'alias'  => null,
			),
			array(
				'order'  => 7,
				'prefix' => 'mu',
				'column' => 'initial_payment',
				'alias'  => null,
			),
			array(
				'order'  => 8,
				'prefix' => 'mu',
				'column' => 'billing_amount',
				'alias'  => null,
			),
			array(
				'order'  => 9,
				'prefix' => 'mu',
				'column' => 'cycle_period',
				'alias'  => null,
			),
			array(
				'order'  => 10,
				'prefix' => 'mu',
				'column' => 'cycle_number',
				'alias'  => null,
			),
			array(
				'order'  => 11,
				'prefix' => 'mu',
				'column' => 'billing_limit',
				'alias'  => null,
			),
			array(
				'order'  => 12,
				'prefix' => 'mu',
				'column' => 'trial_amount',
				'alias'  => null,
			),
			array(
				'order'  => 13,
				'prefix' => 'mu',
				'column' => 'trial_limit',
				'alias'  => null,
			),
			array(
				'order'  => 14,
				'prefix' => 'mu',
				'column' => 'startdate',
				'alias'  => 'startdate',
			),
			array(
				'order'  => 15,
				'prefix' => 'mu',
				'column' => 'enddate',
				'alias'  => 'enddate',
			),
			array(
				'order'  => 16,
				'prefix' => 'm',
				'column' => 'name',
				'alias'  => 'membership',
			),
			array(
				'order'  => 17,
				'prefix' => 'umf',
				'column' => 'meta_value',
				'alias'  => 'first_name',
			),
			array(
				'order'  => 18,
				'prefix' => 'uml',
				'column' => 'meta_value',
				'alias'  => 'last_name',
			),
		);
		
		/**
		 * @filter e20r-directory-default-column-defs
		 *
		 * @param array $columns - List of column definition(s) to use
		 *
		 * $columns = array(
		 *      array(
		 *          'order' => int,
		 *          'prefix' => string,
		 *          'column' => string,
		 *          'alias' => string
		 *      ),
		 *      [...]
		 * )
		 */
		$this->columns = apply_filters( 'e20r-directory-default-column-defs', $this->columns );
	}
	
	/**
	 * Configure the default JOIN values for the directory
	 *
	 * @return array
	 */
	private function defaultJoins() {
		
		global $wpdb;
		
		$joins = array(
			array(
				'table_name'  => $wpdb->usermeta,
				'table_alias' => 'umhide',
				'type'        => 'LEFT',
				'on_clause'   => "( umhide.meta_key = 'pmpromd_hide_directory' OR umhide.meta_key = 'e20red_hide_directory' ) AND u.ID = umhide.user_id",
				'order'       => 0,
			),
			array(
				'table_name'  => $wpdb->usermeta,
				'table_alias' => 'umf',
				'type'        => 'LEFT',
				'on_clause'   => "umf.meta_key = 'first_name' AND u.ID = umf.user_id",
				'order'       => 1,
			),
			array(
				'table_name'  => $wpdb->usermeta,
				'table_alias' => 'uml',
				'type'        => 'LEFT',
				'on_clause'   => "uml.meta_key = 'last_name' AND u.ID = uml.user_id",
				'order'       => 1,
			),
			array(
				'table_name'  => $wpdb->usermeta,
				'table_alias' => 'um',
				'type'        => 'LEFT',
				'on_clause'   => "u.ID = um.user_id",
				'order'       => 3,
			),
			array(
				'table_name'  => $wpdb->pmpro_memberships_users,
				'table_alias' => 'mu',
				'type'        => 'LEFT',
				'on_clause'   => "u.ID = mu.user_id",
				'order'       => 4,
			),
			array(
				'table_name'  => $wpdb->pmpro_membership_levels,
				'table_alias' => 'm',
				'type'        => 'LEFT',
				'on_clause'   => "mu.membership_id = m.id",
				'order'       => 6,
			),
		);
		
		// Allow filtering results in directory short code based on specific meta key/value
		if ( ! empty( $this->filter_key_value ) && ! empty( $this->filter_key_name ) ) {
			$joins[] = array(
				'table_name'  => $wpdb->usermeta,
				'table_alias' => "umk",
				'type'        => 'LEFT',
				'on_clause'   => sprintf( 'umk.meta_key = %1$s AND u.ID = umk.user_id', $wpdb->_escape( $this->filter_key_name ) ),
				'order'       => 5,
			);
		}
		
		return $joins;
	}
	
	/**
	 * Add the meta key/value filter (from short code) to query when needed
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function maybeAddMetaFilter( $select, $where_counter ) {
		
		$utils = Utilities::get_instance();
		
		// Allow filtering results in directory short code based on specific meta key/value
		if ( empty( $this->filter_key_name ) || empty( $this->filter_key_value ) ) {
			$utils->log( "No filtering on meta key/value requested" );
			
			return $where_counter;
		}
		
		// Add filter for Meta Key/Value from shortcode
		$this->where_list[ ++ $where_counter ]                               = $select->whereSettings( 'standard' );
		$this->where_list[ $where_counter ]['column']                        = 'meta_key';
		$this->where_list[ $where_counter ]['order']                         = $where_counter;
		$this->where_list[ $where_counter ]['multi_clause']                  = true;
		$this->where_list[ $where_counter ]['prefix']                        = 'umk';
		$this->where_list[ $where_counter ]['comparison']                    = '=';
		$this->where_list[ $where_counter ]['value']                         = $this->filter_key_name;
		$this->where_list[ $where_counter ]['variable_type']                 = 'string';
		$this->where_list[ $where_counter ]['sub_query']                     = array();
		$this->where_list[ $where_counter ]['sub_query'][0]                  = $select->whereSettings( 'standard' );
		$this->where_list[ $where_counter ]['sub_query'][0]['column']        = 'meta_value';
		$this->where_list[ $where_counter ]['sub_query'][0]['order']         = 0;
		$this->where_list[ $where_counter ]['sub_query'][0]['prefix']        = 'umk';
		$this->where_list[ $where_counter ]['sub_query'][0]['comparison']    = '=';
		$this->where_list[ $where_counter ]['sub_query'][0]['variable_type'] = 'string';
		$this->where_list[ $where_counter ]['sub_query'][0]['multi_clause']  = false;
		$this->where_list[ $where_counter ]['sub_query'][0]['value']         = $this->filter_key_value;
		
		return $where_counter;
	}
	
	/**
	 * Process any defined search fields (from filter)
	 *
	 * @param array $joins
	 *
	 * @return array
	 *
	 * @uses pmpromd_extra_search_fields
	 * @uses e20r-directory-extra-search-fields
	 */
	private function processExtraSearchFields( $joins ) {
		
		/**
		 * Add support for user defined search fields & tables (array value = usermeta field name)
		 * Can be array of field names (usermeta fields)
		 *
		 * @filter e20r-directory-extra-search-fields
		 *
		 * @param array
		 */
		$this->extra_search_fields = apply_filters( 'e20r-directory-extra-search-fields', array() );
		
		/**
		 * Backwards compatibility for the 'e20r-directory-extra-search-fields' filter
		 *
		 * @param array
		 */
		$this->extra_search_fields = apply_filters( 'pmpromd_extra_search_fields', $this->extra_search_fields );
		
		if ( ! empty( $this->extra_search_fields ) && ! is_array( $this->extra_search_fields ) ) {
			$this->extra_search_fields = array( $this->extra_search_fields );
		}
		
		if ( ! empty( $this->extra_search_fields ) ) {
			
			foreach ( $this->extra_search_fields as $field_name ) {
				if ( isset( $_REQUEST[ $field_name ] ) && is_array( $_REQUEST[ $field_name ] ) ) {
					$this->extra_search_fields[ ${$field_name} ] = array_map( 'sanitize_text_field', $_REQUEST[ $field_name ] );
				} else if ( isset( $_REQUEST[ $field_name ] ) && ! is_array( $_REQUEST[ $field_name ] ) ) {
					$this->extra_search_fields[ ${$field_name} ] = sanitize_text_field( $_REQUEST[ $field_name ] );
				} else if ( empty( $_REQUEST[ $field_name ] ) ) {
					$this->extra_search_fields[ ${$field_name} ] = null;
				}
			}
		}
		
		// Add (more) JOIN clauses for any extra search field(s)
		if ( ! empty( $this->extra_search_fields ) ) {
			
			$cnt = $this->findMaxOrderForJoins( $joins );
			global $wpdb;
			
			foreach ( $this->extra_search_fields as $f => $f ) {
				
				$cnt ++;
				
				if ( ! empty( ${$f} ) ) {
					$joins[] = array(
						'table_name'  => $wpdb->usermeta,
						'table_alias' => sprintf( 'umrh_%d', $cnt ),
						'type'        => 'LEFT JOIN',
						'on_clause'   => sprintf( 'umrh_%1$d.meta_key = %2$s AND u.ID = umrh_%1$d.user_id', $cnt, $f ),
						'order'       => $cnt,
					);
					
					// $sqlQuery .= "LEFT JOIN {$wpdb->usermeta} umrh_{$cnt} ON umrh_{$cnt}.meta_key = '{$f}' AND u.ID = umrh_{$cnt}.user_id";
				}
			}
		}
		
		return $joins;
	}
	
	/**
	 * If there are multiple JOIN clauses, find the highest (first) order number
	 * (JOIN clauses are ordered in the Select class)
	 *
	 * @param array $joins
	 *
	 * @return int
	 */
	private function findMaxOrderForJoins( $joins ) {
		
		$max_order = 0;
		
		foreach ( $joins as $join_clause ) {
			
			if ( isset( $join_clause['order'] ) ) {
				$max_order = $max_order < $join_clause['order'] ? $join_clause['order'] : $max_order;
			}
		}
		
		return $max_order;
	}
	
	/**
	 * Make sure the user is or has been a member
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function maybeAddLevelCheckToWhere( $select, $where_counter ) {
		
		$utils = Utilities::get_instance();
		
		// Add for active members
		$this->where_list[ ++ $where_counter ] = $select->whereSettings( 'standard' );
		
		$utils->log( "Where clause #{$where_counter} is being added" );
		
		// Configure for the membership ID check
		$this->where_list[ $where_counter ]['order']         = $where_counter;
		$this->where_list[ $where_counter ]['column']        = 'membership_id';
		$this->where_list[ $where_counter ]['prefix']        = 'mu';
		$this->where_list[ $where_counter ]['value']         = 0;
		$this->where_list[ $where_counter ]['operator']      = 'AND';
		$this->where_list[ $where_counter ]['multi_clause']  = true;
		$this->where_list[ $where_counter ]['variable_type'] = 'numeric';
		
		if ( count( $this->statuses ) == 1 && in_array( 'active', $this->statuses ) ) {
			$this->where_list[ $where_counter ]['comparison'] = '>';
			// $sqlQuery .= " AND mu.membership_id > 0";
		} else {
			$this->where_list[ $where_counter ]['comparison'] = '>=';
			// $sqlQuery .= " AND mu.membership_id >= 0";
		}
		
		return $where_counter;
	}
	
	/**
	 * Add status based filtering of the result(s) in the Query
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function addUserStatusCheck( $select, $where_counter = 1 ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty( $this->status ) ) {
			return $where_counter;
		}
		
		// Configure the status part of the WHERE clause
		$this->where_list[ ++ $where_counter ] = $select->whereSettings( 'in' );
		
		$utils->log( "Where clause #{$where_counter} is being added" );
		
		$this->where_list[ $where_counter ]['order']         = $where_counter;
		$this->where_list[ $where_counter ]['operator']      = 'AND';
		$this->where_list[ $where_counter ]['column']        = 'status';
		$this->where_list[ $where_counter ]['prefix']        = 'mu';
		$this->where_list[ $where_counter ]['value']         = $this->statuses;
		$this->where_list[ $where_counter ]['variable_type'] = 'string';
		$this->where_list[ $where_counter ]['multi_clause']  = false;
		$this->where_list[ $where_counter ]['comparison']    = 'IN';
		
		return $where_counter;
	}
	
	/**
	 * Add clause for setting to hide the user form the directory
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function addHiddenUserCheck( $select, $where_counter ) {
		
		// Make sure we only accept umh.meta_value's that are null or != 1
		// ---> AND (umh.meta_value IS NULL OR umh.meta_value <> \'1\')',
		$this->where_list[ ++ $where_counter ]               = $select->whereSettings( 'standard' );
		$this->where_list[ $where_counter ]['order']         = $where_counter;
		$this->where_list[ $where_counter ]['operator']      = 'AND';
		$this->where_list[ $where_counter ]['column']        = 'meta_value';
		$this->where_list[ $where_counter ]['prefix']        = 'umhide';
		$this->where_list[ $where_counter ]['value']         = 'NULL';
		$this->where_list[ $where_counter ]['variable_type'] = 'null';
		$this->where_list[ $where_counter ]['comparison']    = 'IS';
		$this->where_list[ $where_counter ]['sub_clause']    = array();
		$this->where_list[ $where_counter ]['multi_clause']  = true;
		
		$this->where_list[ $where_counter ]['sub_clause'][0]                  = $select->whereSettings( 'standard' );
		$this->where_list[ $where_counter ]['sub_clause'][0]['order']         = 0;
		$this->where_list[ $where_counter ]['sub_clause'][0]['operator']      = 'OR';
		$this->where_list[ $where_counter ]['sub_clause'][0]['column']        = 'meta_value';
		$this->where_list[ $where_counter ]['sub_clause'][0]['prefix']        = 'umhide';
		$this->where_list[ $where_counter ]['sub_clause'][0]['value']         = 1;
		$this->where_list[ $where_counter ]['sub_clause'][0]['variable_type'] = 'numeric';
		$this->where_list[ $where_counter ]['sub_clause'][0]['comparison']    = '<>';
		$this->where_list[ $where_counter ]['sub_clause'][0]['multi_clause']  = false;
		
		return $where_counter;
	}
	
	/**
	 * Add search specific SQL
	 *
	 * @param Select $select_class
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function maybeAddSearch( $select_class, $where_counter ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty( $this->search ) ) {
			$utils->log( "No search required/found" );
			
			return $where_counter;
		}
		
		$search_where                  = $select_class->whereSettings( 'like' );
		$search_where['order']         = $where_counter;
		$search_where['value']         = $this->search;
		$search_where['variable_type'] = 'string';
		$search_where['multi_clause']  = true;
		$search_where['operator']      = 'AND';
		$search_where['comparison']    = 'LIKE';
		$search_where['column']        = 'user_login';
		$search_where['prefix']        = 'u';
		$search_where['sub_clause']    = array();
		
		// Look for search string in user_email
		$search_where['sub_clause'][0]                  = $select_class->whereSettings( 'like' );
		$search_where['sub_clause'][0]['order']         = 0;
		$search_where['sub_clause'][0]['operator']      = 'OR';
		$search_where['sub_clause'][0]['multi_clause']  = false;
		$search_where['sub_clause'][0]['column']        = 'user_email';
		$search_where['sub_clause'][0]['prefix']        = 'u';
		$search_where['sub_clause'][0]['value']         = $this->search;
		$search_where['sub_clause'][0]['variable_type'] = 'string';
		$search_where['sub_clause'][0]['comparison']    = 'LIKE';
		
		
		// Look for search string in display_name
		$search_where['sub_clause'][1]                  = $select_class->whereSettings( 'like' );
		$search_where['sub_clause'][1]['order']         = 1;
		$search_where['sub_clause'][1]['operator']      = 'OR';
		$search_where['sub_clause'][1]['multi_clause']  = false;
		$search_where['sub_clause'][1]['column']        = 'display_name';
		$search_where['sub_clause'][1]['prefix']        = 'u';
		$search_where['sub_clause'][1]['value']         = $this->search;
		$search_where['sub_clause'][1]['variable_type'] = 'string';
		$search_where['sub_clause'][1]['comparison']    = 'LIKE';
		
		// Look for search string in meta values
		$search_where['sub_clause'][2]                  = $select_class->whereSettings( 'like' );
		$search_where['sub_clause'][2]['order']         = 2;
		$search_where['sub_clause'][2]['operator']      = 'OR';
		$search_where['sub_clause'][2]['multi_clause']  = false;
		$search_where['sub_clause'][2]['column']        = 'meta_value';
		$search_where['sub_clause'][2]['prefix']        = 'um';
		$search_where['sub_clause'][2]['value']         = $this->search;
		$search_where['sub_clause'][2]['variable_type'] = 'string';
		$search_where['sub_clause'][2]['comparison']    = 'LIKE';
		
		$this->where_list[ ++ $where_counter ] = apply_filters( 'e20r-directory-for-pmpro_sql_where_search', $search_where, $this->search, $select_class );
		
		return $where_counter;
	}
	
	/**
	 * Add extra search clauses if defined via the filter to the SQL Query
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return int
	 */
	private function maybeAddExtraSearchFieldClauses( $select, $where_counter ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty( $this->extra_search_fields ) ) {
			$utils->log( "No extra search fields to process" );
			
			return $where_counter;
		}
		
		$cnt                = $where_counter;
		$extra_fields_where = array();
		
		foreach ( $this->extra_search_fields as $field => $value ) {
			
			$utils->log( "Processing WHERE clause for the {$field}" );
			
			$cnt ++;
			
			if ( false === $this->use_precise_values ) {
				$extra_fields_where[ $cnt ] = $select->whereSettings( 'like' );
			} else {
				$extra_fields_where[ $cnt ] = $select->whereSettings( 'standard' );
			}
			
			$extra_fields_where[ $cnt ]['multi_clause']  = true;
			$extra_fields_where[ $cnt ]['prefix']        = "umrh_{$cnt}";
			$extra_fields_where[ $cnt ]['column']        = 'meta_value';
			$extra_fields_where[ $cnt ]['order']         = $cnt;
			$extra_fields_where[ $cnt ]['variable_type'] = 'string';
			
			if ( is_array( $this->extra_search_fields[ $field ] ) && ! empty( $this->extra_search_fields[ $field ] ) ) {
				
				$utils->log( "{$field} contains an array value" );
				
				$max_v                                    = count( $this->extra_search_fields[ $field ] ) - 1;
				$i                                        = 0;
				$extra_fields_where[ $cnt ]['sub_clause'] = array();
				
				foreach ( $this->extra_search_fields[ $field ] as $v ) {
					
					$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['value']         = $v;
					$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['column']        = 'meta_value';
					$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['prefix']        = "umrh_{$cnt}";
					$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['order']         = $i;
					$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['variable_type'] = 'string';
					
					if ( $max_v > $i ) {
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['operator']     = "OR";
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['multi_clause'] = true;
					}
					
					if ( true === $this->use_precise_values ) {
						$extra_fields_where[ $cnt ]['sub_clause']                   = array();
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['prefix']   = "umrh_{$cnt}";
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['column']   = 'meta_key';
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['operator'] = "AND";
						
						// Using the meta key name, not its value
						$extra_fields_where[ $cnt ]['sub_clause'][ $i ]['value'] = $this->extra_search_fields[ $field ];
					}
					
					++ $i;
				}
			} else if ( ! empty( $this->extra_search_fields[ $field ] ) ) {
				
				$utils->log( "Field {$field} contains {$this->extra_search_fields[ $field ]}" );
				
				$extra_fields_where[ $cnt ]['value'] = $this->extra_search_fields[ $field ];
			}
		}
		
		$this->where_list += apply_filters(
			'e20r-directory-for-pmpro-sql-where-extra-fields',
			$extra_fields_where,
			$this->extra_search_fields,
			$this->use_precise_values
		);
		
		return $cnt;
	}
	
	/**
	 * User defined member levels to include users by
	 *
	 * @param Select $select
	 * @param int    $where_counter
	 *
	 * @return mixed
	 */
	private function maybeAddLevelWhere( $select, $where_counter ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty( $this->levels ) ) {
			$utils->log( "No level(s) specified for the short code. using all members/levels" );
			
			return $where_counter;
		}
		
		$this->where_list[ ++ $where_counter ] = $select->whereSettings( 'in' );
		
		$this->where_list[ $where_counter ]['order']         = $where_counter;
		$this->where_list[ $where_counter ]['operator']      = 'AND';
		$this->where_list[ $where_counter ]['column']        = 'membership_id';
		$this->where_list[ $where_counter ]['prefix']        = 'mu';
		$this->where_list[ $where_counter ]['value']         = array_map( 'intval', $this->levels );
		$this->where_list[ $where_counter ]['variable_type'] = 'numeric';
		$this->where_list[ $where_counter ]['comparison']    = 'IN';
		$this->where_list[ $where_counter ]['multi_clause']  = true;
		
		$this->where_list[ $where_counter ] = apply_filters(
			'e20r-directory-sql-where-levels',
			$this->where_list[ $where_counter ],
			$this->levels
		);
		
		return $where_counter;
	}
	
	/**
	 * Map order by attribute to proper column name definition
	 *
	 * @param string $order_by
	 *
	 * @return string
	 */
	private function getOrderByCol( $order_by ) {
		
		$utils  = Utilities::get_instance();
		$column = '';
		
		$utils->log( "Looking for {$order_by} in column or alias for the column defs" );
		
		foreach ( $this->columns as $id => $column_def ) {
			
			$utils->log( "Processing column # {$id}" );
			
			$matches_column = ( $order_by == $column_def['column'] );
			$matches_alias  = ( $order_by == $column_def['alias'] );
			
			if ( $matches_alias || $matches_column ) {
				
				$utils->log( "Found {$order_by} column data" );
				
				if ( ! empty( $column_def['prefix'] ) && $matches_column ) {
					$column .= "{$column_def['prefix']}.";
				}
				
				if ( $matches_column ) {
					$column .= "{$column_def['column']}";
				}
				
				if ( $matches_alias ) {
					$column .= "{$column_def['alias']}";
				}
				
				break;
			}
		}
		
		if ( empty( $column ) ) {
			$column = "u.display_name";
		}
		
		return $column;
	}
	
	/**
	 * Remove members from the directory list who do not belong to the specified WP roles
	 *
	 * @param array $users
	 *
	 * @return array
	 */
	private function maybeFilterByRole( $users ) {
		
		$utils = Utilities::get_instance();
		
		// No roles to process (i.e. don't worry about it)
		if ( empty( $this->roles ) ) {
			$utils->log( "No role limiting needed!" );
			
			return $users;
		}
		
		$utils->log( "Have to limit by roles" );
		
		foreach ( $users as $key => $user ) {
			
			$include_by_role = false;
			$the_user        = get_userdata( $user->ID );
			
			// Does the user belong to (one of) the specified role(s)?
			foreach ( $this->roles as $role ) {
				$include_by_role = $include_by_role || in_array( $role, $the_user->roles );
			}
			
			// Skip this user record if not
			if ( false === $include_by_role ) {
				unset( $users[ $key ] );
			}
		}
		
		return $users;
	}
	
	/**
	 * Generate the Search form for the page(s)
	 *
	 * @return false|string
	 */
	public function addSearchForm() {
		
		ob_start(); ?>
        <form role="search"
              class="e20r-directory-for-pmpro_search search-form <?php echo apply_filters( 'e20r-directory-for-pmpro_search_class', 'locate-right' ); ?>">
            <div class="e20r-directory-for-pmpro_search_field ">
                <label>
                    <span class="screen-reader-text"><?php _e( 'Search for:', E20R_Directory_For_PMPro::plugin_slug ); ?></span>
                    <input type="search" class="search-field"
                           placeholder="<?php echo apply_filters( 'pmpromd_search_placeholder_text', __( "Search Members", "e20r-directory-for-pmpro" ) ); ?>"
                           name="ps" value="<?php esc_attr_e( $this->search ); ?>"
                           title="<?php echo apply_filters( 'pmpromd_search_placeholder_text', __( "Search Members", "e20r-directory-for-pmpro" ) ); ?>"/>
                    <input type="hidden" name="page_size" value="<?php esc_attr_e( $this->page_size ); ?>"/>
                </label>
            </div>
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
            <div class="search-button clear">
                <input type="submit" class="search-submit"
                       value="<?php _e( "Search Members", "e20r-directory-for-pmpro" ); ?>">
            </div>
			<?php if ( ! empty( $this->search ) ) { ?>
                <div class="search-button clear">
                    <a class="button button-secondary"
                       href="<?php echo esc_url( $this->directory_url ); ?>"><?php _e( "Reset", "e20r-directory-for-pmpro" ); ?></a>
                </div>
			<?php } ?>

        </form>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Add result string as HTML
	 */
	private static function addResultString() {
		$class = self::getInstance();
		
		if ( $class->total_in_db > 0 ) { ?>
            <small class="muted">
                (<?php
				if ( $class->total_in_db == 1 ) {
					printf( __( 'Showing 1 Result', E20R_Directory_For_PMPro::plugin_slug ), $class->start + 1, $class->end, $class->total_in_db );
				} else {
					printf( __( 'Showing %s-%s of %s results', E20R_Directory_For_PMPro::plugin_slug ), $class->start + 1, $class->end, $class->total_in_db );
				} ?>)
            </small>
		<?php }
	}
	
	/**
	 * Get or instantiate and return the E20R_Directory class instance
	 *
	 * @return Directory_Page|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Generates a HTML Table based layout
	 *
	 * @param array $members
	 *
	 * @return false|string
	 */
	private function generateTableLayout( $members ) {
		
		global $current_user;
		$utils = Utilities::get_instance();
		ob_start(); ?>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <thead>
			<?php if ( true === $this->show_avatar ) { ?>
                <th class="e20r-directory-for-pmpro_avatar">
					<?php _e( 'Avatar', 'paid-memberships-pro' ); ?>
                </th>
			<?php } ?>
            <th class="e20r-directory-for-pmpro_display-name">
				<?php _e( 'Member', 'paid-memberships-pro' ); ?>
            </th>
			<?php if ( true === $this->show_email ) { ?>
                <th class="e20r-directory-for-pmpro_email">
					<?php _e( 'Email Address', 'paid-memberships-pro' ); ?>
                </th>
			<?php } ?>
			<?php if ( ! empty( $this->fields_array ) ) { ?>
                <th class="e20r-directory-for-pmpro_additional">
					<?php _e( 'More Information', 'paid-memberships-pro' ); ?>
                </th>
			<?php } ?>
			<?php if ( true === $this->show_level ) { ?>
                <th class="e20r-directory-for-pmpro_level">
					<?php _e( 'Level', 'paid-memberships-pro' ); ?>
                </th>
			<?php } ?>
			<?php if ( true === $this->show_startdate ) { ?>
                <th class="e20r-directory-for-pmpro_date">
					<?php _e( 'Start Date', 'paid-memberships-pro' ); ?>
                </th>
			<?php } ?>
			<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                <th class="e20r-directory-for-pmpro_link">&nbsp;</th>
			<?php } ?>
            </thead>
            <tbody>
			<?php $count = 0;
			foreach ( $members as $member ) {
				
				// Fix timestamp(s) if necessary
				$utils->log( "Start date: {$member->startdate}" );
				$member->startdate = strtotime( $member->startdate, current_time( 'timestamp' ) );
				$member->joindate  = strtotime( $member->joindate, current_time( 'timestamp' ) );
				$member->enddate   = ( ( '0000-00-00 00:00:00' != $member->enddate || null == $member->enddate ) ?
					strtotime( $member->enddate, current_time( 'timestamp' ) ) :
					null
				);
				
				$count ++;
				$the_user                   = get_userdata( $member->ID );
				$the_user->membership_level = function_exists( 'pmpro_getMembershipLevelForUser' ) ?
					pmpro_getMembershipLevelForUser( $member->ID ) :
					false;
				
				echo $this->createMemberTableRow( $the_user, $member );
			} // Foreach ?>
            </tbody>
        </table>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Generate the row for the member (in the directory) as a table row (<tr> element)
	 *
	 * @param \WP_User $the_user
	 * @param array    $member_info
	 *
	 * @return false|string
	 */
	private function createMemberTableRow( $the_user, $member_info ) {
		
		ob_start(); ?>
        <tr id="e20r-directory-for-pmpro_row-<?php esc_attr_e( $the_user->ID ); ?>"
            class="e20r-directory-for-pmpro_row<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) {
			    echo " e20r-directory-for-pmpro_linked";
		    } ?>">
			<?php if ( true === $this->show_avatar ) { ?>
                <td class="e20r-directory-for-pmpro_avatar">
					<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $this->profile_url ) ); ?>"><?php echo get_avatar( $the_user->ID, $this->avatar_size ); ?></a>
					<?php } else { ?>
						<?php echo get_avatar( $the_user->ID, $this->avatar_size ); ?>
					<?php } ?>
                </td>
			<?php } ?>
            <td>
                <h3 class="e20r-directory-for-pmpro_display-name">
					<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $this->profile_url ) ); ?>"><?php esc_html_e( $the_user->display_name ); ?></a>
					<?php } else { ?>
						<?php esc_html_e( $the_user->display_name ); ?>
					<?php } ?>
                </h3>
            </td>
			<?php if ( true === $this->show_email ) { ?>
                <td class="e20r-directory-for-pmpro_email">
					<?php esc_html_e( $the_user->user_email ); ?>
                </td>
			<?php } ?>
			<?php
			if ( ! empty( $this->fields_array ) ) { ?>
                <td class="e20r-directory-for-pmpro_additional">
					<?php
					foreach ( $this->fields_array as $field ) {
						$meta_field = wp_unslash( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $the_user->{$field[1]}, $field[1], $the_user ) );
						if ( ! empty( $meta_field ) ) {
							?>
                            <p class="e20r-directory-for-pmpro_<?php esc_attr_e( $field[1] ); ?>">
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
									} ?>
                                    <strong><?php esc_html_e( $field[0] ); ?></strong>
									<?php echo apply_filters( 'e20r-directory-for-pmpro_metafield_value', implode( ", ", $meta_field ), $field[1], $the_user ); ?>
									<?php
								} else {
									if ( $field[1] == 'user_url' || 1 === preg_match( '/url/i', $field[1] ) ) { ?>
                                        <a href="<?php echo esc_url( $meta_field ); ?>"
                                           target="_blank"><?php esc_html_e( $field[0] ); ?></a>
										<?php
									} else { ?>
                                        <strong><?php esc_html_e( $field[0] ); ?></strong>
										<?php
										$meta_field_embed = wp_oembed_get( $meta_field );
										if ( ! empty( $meta_field_embed ) ) {
											echo wp_unslash( $meta_field_embed );
										} else {
											echo make_clickable( wp_unslash( $meta_field ) );
										}
									}
								}
								?>
                            </p>
							<?php
						}
					}
					?>
                </td>
				<?php
			}
			?>
			<?php if ( true === $this->show_level ) { ?>
                <td class="e20r-directory-for-pmpro_level">
					<?php esc_html_e( $member_info->membership ); ?>
                </td>
			<?php } ?>
			<?php if ( true === $this->show_startdate ) { ?>
                <td class="e20r-directory-for-pmpro_date">
					<?php echo date_i18n( get_option( "date_format" ), $member_info->startdate ); ?>
                </td>
			<?php } ?>
			<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                <td class="e20r-directory-for-pmpro_link">
					<?php $this->displayLinks( $the_user ); ?>
                </td>
			<?php } ?>
        </tr>
		<?php
		
		return ob_get_clean();
	}
	
	/**
	 * Generate View and Edit links for Profile/user as needed
	 *
	 * @param \WP_User $wp_user
	 */
	public function displayLinks( $wp_user ) {
		
		global $current_user;
		$utils             = Utilities::get_instance();
		$read_only_profile = $editable_profile = $this->profile_url;
		
		if ( true === $this->editable_profile && is_user_logged_in() && $current_user->ID === $wp_user->ID ) {
			$editable_profile = get_edit_user_link( $wp_user->ID );
		} ?>
        <span><?php if (
			true === $this->editable_profile &&
			is_user_logged_in() &&
			$current_user->ID === $wp_user->ID
		) {
			$utils->log( "Allowing edit for {$wp_user->ID}" ); ?>
            <a href="<?php echo esc_url( add_query_arg( 'pu', $wp_user->user_nicename, $editable_profile ) ); ?>">
                <?php _e( 'Edit', E20R_Directory_For_PMPro::plugin_slug ); ?>
            </a>
			<?php _e( 'or', E20R_Directory_For_PMPro::plugin_slug );
		} ?>
        <a href="<?php echo esc_url( add_query_arg( 'pu', $wp_user->user_nicename, $read_only_profile ) ); ?>">
                <?php _e( 'View Profile', E20R_Directory_For_PMPro::plugin_slug ); ?>
            </a>
        </span><?php
	}
	
	/**
	 * Create DIV based layout for the members list
	 *
	 * @param array $members
	 *
	 * @return string
	 */
	private function generateDivLayout( $members ) {
		
		global $current_user;
		
		$layout_cols = preg_replace( '/[^0-9]/', '', $this->layout );
		
		if ( ! empty( $layout_cols ) ) {
			$chunks = array_chunk( $members, $layout_cols );
		} else {
			$chunks = array_chunk( $members, 1 );
		}
		
		$count = 0;
		ob_start();
		
		foreach ( $chunks as $row ) { ?>
            <div class="row">
				<?php foreach ( $row as $member ) {
					
					$member->startdate = strtotime( $member->startdate, current_time( 'timestamp' ) );
					$member->joindate  = strtotime( $member->joindate, current_time( 'timestamp' ) );
					$member->enddate   = ( ( '0000-00-00 00:00:00' != $member->enddate || null == $member->enddate ) ?
						strtotime( $member->enddate, current_time( 'timestamp' ) ) :
						null
					);
					$count ++;
					$wp_user                   = get_userdata( $member->ID );
					$wp_user->membership_level = pmpro_getMembershipLevelForUser( $wp_user->ID );
					
					echo $this->createMemberDivRow( $wp_user, $member, $count );
				} ?>
            </div> <!-- end row -->
            <hr/> <?php
		}
		
		return ob_get_clean();
	}
	
	/**
	 * Generate the row for the member (in the directory) as a DIV element
	 *
	 * @param \WP_User $wp_user
	 * @param array    $member_info
	 * @param int      $count
	 *
	 * @return false|string
	 */
	private function createMemberDivRow( $wp_user, $member_info, $count ) {
		ob_start(); ?>
        <div class="medium-<?php $avatar_align = $this->selectLayout( $count, $this->end ); ?> columns">
            <div id="pmpro_member-<?php echo esc_attr( $wp_user->ID ); ?>">
				<?php if ( true === $this->show_avatar ) { ?>
                    <div class="e20r-directory-for-pmpro_avatar">
						<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                            <a class="<?php esc_attr_e( $avatar_align ); ?>"
                               href="<?php echo esc_url( add_query_arg( 'pu', $wp_user->user_nicename, $this->profile_url ) ); ?>"><?php echo get_avatar( $wp_user->ID, $this->avatar_size, null, $wp_user->display_name ); ?></a>
						<?php } else { ?>
                            <span
                                    class="<?php echo esc_attr( $avatar_align ); ?>"><?php echo get_avatar( $wp_user->ID, $this->avatar_size, null, $wp_user->display_name ); ?></span>
						<?php } ?>
                    </div>
				<?php } ?>
                <h3 class="e20r-directory-for-pmpro_display-name">
					<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                        <a href="<?php echo esc_url( add_query_arg( 'pu', $wp_user->user_nicename, $this->profile_url ) ); ?>"><?php echo $wp_user->display_name; ?></a>
					<?php } else { ?>
						<?php esc_attr_e( $wp_user->display_name ); ?>
					<?php } ?>
                </h3>
				<?php if ( true === $this->show_email ) { ?>
                    <p class="e20r-directory-for-pmpro_email">
                        <strong><?php _e( 'Email Address', 'pmpro' ); ?></strong>
						<?php esc_attr_e( $wp_user->user_email ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_level ) { ?>
                    <p class="e20r-directory-for-pmpro_level">
                        <strong><?php _e( 'Level', 'pmpro' ); ?></strong>
						<?php esc_attr_e( $member_info->membership ); ?>
                    </p>
				<?php } ?>
				<?php if ( true === $this->show_startdate ) { ?>
                    <p class="e20r-directory-for-pmpro_date">
                        <strong><?php _e( 'Start Date', 'pmpro' ); ?></strong>
						<?php echo date_i18n( get_option( "date_format" ), $member_info->startdate ); ?>
                    </p>
				<?php } ?>
				<?php
				// Save a copy of the extracted fields (for the e20rmd_add_extra_directory_output action)
				$real_fields_array = $this->fields_array;
				
				/**
				 * Process the received field list from the 'fields=""' attribute
				 *
				 * @filter e20r-member-profile_fields
				 *
				 * @param array    $this ->fields_array
				 * @param \WP_User $wp_user
				 */
				$this->fields_array = apply_filters( 'e20r-member-profile_fields', $this->fields_array, $wp_user );
				
				if ( is_array( $this->fields_array ) && ! empty( $this->fields_array ) ) {
					
					foreach ( $this->fields_array as $field ) {
						
						$meta_field = wp_unslash( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $wp_user->{$field[1]}, $field[1], $wp_user ) );
						if ( ! empty( $meta_field ) ) { ?>
                            <p class="e20r-directory-for-pmpro_<?php echo esc_attr( $field[1] ); ?>">
								<?php
								if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
									//this is a file field ?>
                                    <strong><?php echo esc_attr( $field[0] ); ?></strong>
									<?php echo E20R_Directory_For_PMPro::displayFileField( $meta_field ); ?>
									<?php
								} else if ( is_array( $meta_field ) ) {
									
									//this is a general array, check for Register Helper options first
									if ( ! empty( $rh_fields[ $field[1] ] ) ) {
										foreach ( $meta_field as $key => $value ) {
											$meta_field[ $key ] = $rh_fields[ $field[1] ][ $value ];
										}
									} ?>
                                    <strong><?php esc_attr_e( $field[0] ); ?></strong>
									<?php echo apply_filters( 'e20r-directory-for-pmpro_metafield_value', implode( ", ", $meta_field ), $field[1], $wp_user );
								} else if ( $field[1] == 'user_url' ) { ?>
                                    <a href="<?php esc_attr_e( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $meta_field, $field[1], $wp_user ) ); ?>"
                                       target="_blank"><?php esc_attr_e( $field[0] ); ?></a> <?php
								} else { ?>
                                    <strong><?php esc_attr_e( $field[0] ); ?>:</strong>
									<?php echo make_clickable( apply_filters( 'e20r-directory-for-pmpro_metafield_value', $meta_field, $field[1], $wp_user ) ); ?>
									<?php
								} ?>
                            </p>
							<?php
						}
					}
				}
				
				do_action( 'e20rmd_add_extra_directory_output', $real_fields_array, $wp_user );
				?>
				<?php if ( true === $this->show_link && true === $this->link && ! empty( $this->profile_url ) ) { ?>
                    <p class="e20r-directory-for-pmpro_link">
						<?php $this->displayLinks( $wp_user ); ?>
                    </p>
				<?php } ?>
            </div> <!-- end pmpro_addon_package-->
        </div>
		<?php
		
		return ob_get_clean();
	}
	
	/**
	 * Configure alignment and column info for the <div> row
	 *
	 * @param int $count
	 * @param int $end
	 *
	 * @return string
	 */
	private function selectLayout( $count, $end ) {
		
		switch ( $this->layout ) {
			
			case 2:
				echo '6 ';
				$align = "alignright";
				break;
			
			case 3:
				echo '4 text-center ';
				$align = "aligncenter";
				break;
			
			case 4:
				echo '3 text-center ';
				$align = "aligncenter";
				break;
			
			default:
				echo '12 ';
				$align = "alignright";
		}
		
		if ( $count == $end ) {
			echo 'end ';
		}
		
		return $align;
	}
	
	/**
	 * Generate prev/next links
	 *
	 * @param int $page_number
	 * @param int $end
	 */
	private function prevNextLinks( $page_number, $end ) {
		
		global $post;
		$utils = Utilities::get_instance();
		
		// Configure the basics of the Pagination arguments
		$pn_args = array(
			"ps"        => $this->search,
			"page_size" => $this->page_size,
		);
		
		// Link to previous page
		if ( $page_number > 1 ) {
			
			$utils->log( "Adding a 'prev' link" );
			// Decrement the page counter by 1
			$pn_args['page_number'] = $page_number - 1; ?>
            <span class="pmpro_prev">
                    <a href="<?php echo esc_url_raw(
	                    add_query_arg(
		                    $this->paginationArgs( $pn_args ),
		                    get_permalink( $post->ID )
	                    )
                    ); ?>">
                        <?php _e( "&laquo; Previous", "e20r-directory-for-pmpro" ); ?>
                    </a>
                </span>
			<?php
		}
		
		// Link to next page
		if ( $this->total_in_db > $end ) {
			
			$utils->log( "Adding a 'next' link" );
			// Increment the page counter by 1
			$pn_args['page_number'] = $page_number + 1; ?>
            <span class="pmpro_next">
                    <a href="<?php echo esc_url_raw(
	                    add_query_arg(
		                    $this->paginationArgs( $pn_args ),
		                    get_permalink( $post->ID )
	                    )
                    ); ?>">
                        <?php _e( "Next &raquo;", "e20r-directory-for-pmpro" ); ?>
                    </a>
                </span>
			<?php
		}
	}
	
	/**
	 * Include existing REQUEST arguments (if they belong to the 'extra search fields')
	 *
	 * @param array $arguments
	 *
	 * @return array
	 */
	public function paginationArgs( $arguments ) {
		
		if ( empty( $this->extra_search_fields ) ) {
			return $arguments;
		}
		
		$extra_search_fields = array_keys( $this->extra_search_fields );
		
		foreach ( $extra_search_fields as $ef_name ) {
			
			$req_value = ( isset( $_REQUEST[ $ef_name ] ) && ! empty( $_REQUEST[ $ef_name ] ) ? sanitize_text_field( $_REQUEST[ $ef_name ] ) : null );
			
			if ( ! isset( $addl_arguments[ $ef_name ] ) && ! is_null( $req_value ) ) {
				$arguments[ $ef_name ] = $req_value;
			}
		}
		
		return $arguments;
	}
}