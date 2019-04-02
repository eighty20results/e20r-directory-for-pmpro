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


use E20R\Member_Directory\E20R_Directory_For_PMPro;
use E20R\Utilities\Utilities;

class PMPro_PageSettings {
	
	/**
	 * @var null|PMPro_PageSettings
	 */
	private static $instance = null;
	
	/**
	 * PMPro_PageSettings constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}
	
	/**
	 * Get or instantiate and get the current class (singleton)
	 *
	 * @return PMPro_PageSettings|null
	 */
	public static function getInstance() {
		
		if ( true === is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load all required hooks for the settings submenu (at the end of the list)
	 */
	public function loadHooks() {
		
		add_action( 'admin_menu', array( $this, 'fixPMProPageSettings' ), 9999 );
		add_action( 'e20r-directory-process-extra-pages', array( $this, 'addExtraPages' ), - 1, 2 );
	}
	
	/**
	 * Load PMPro pages (from add-ons, etc)
	 *
	 * @param array $extra_pages
	 * @param null  $from_pmpro
	 */
	public function addExtraPages( $extra_pages ) {
		
		if ( ! is_array( $extra_pages ) ) {
			return;
		}
		
		/*
		if ( ! is_null( $from_pmpro ) ) {
			$this->loadE20RDirSettings();
			
			return;
		}
		*/
		
		if ( ! empty( $extra_pages ) ) { ?>
            <h2><?php _e( 'Additional Page Settings', 'paid-memberships-pro' ); ?></h2>
            <table class="form-table">
            <tbody>
			<?php foreach ( $extra_pages as $name => $page ) { ?>
				<?php
				
				if ( is_array( $page ) ) {
					$label = $page['title'];
					if ( ! empty( $page['hint'] ) ) {
						$hint = $page['hint'];
					} else {
						$hint = '';
					}
				} else {
					$label = $page;
					$hint  = '';
				}
				
				echo $this->generatePageTableRow( $name, $label, $hint ) ?>
                </tbody>
                </table>
			<?php }
		}
	}
	
	/**
	 * Generates a HTML table row containing the settings/info for a PMPro page on the "Memberships" -> "Settings" ->
	 * "Pages" menu page
	 *
	 * @param string      $page_name
	 * @param string      $page_label
	 * @param null|string $hint
	 *
	 * @return string
	 */
	private function generatePageTableRow( $page_name, $page_label, $hint = null ) {
		
		global $pmpro_pages;
		
		$css_id         = sprintf( '%1$s_page_id', $page_name );
		$page_settings  = array(
			'name'              => $css_id,
			'show_option_none'  => sprintf( '-- %1$s --', __( 'Choose One', 'paid-memberships-pro' ) ),
			'option_none_value' => - 1,
			'selected'          => $pmpro_pages[ $page_name ],
			'echo'              => false,
		);
		$post_arguments = array( 'post' => $pmpro_pages[ $page_name ], 'action' => 'edit' );
		
		$html   = array();
		$html[] = sprintf( '<tr>' );
		$html[] = sprintf( '<th scope="row" valign="top">' );
		$html[] = sprintf( '<label for="%1$s">%2$s</label>', $css_id, $page_label );
		$html[] = sprintf( '</th>' );
		$html[] = sprintf( '<td>' );
		$html[] = wp_dropdown_pages( $page_settings );
		
		// Allow edit/view if the page has been generated and the setting saved
		if ( ! empty( $pmpro_pages[ $page_name ] ) ) {
			$html[] = sprintf( '<a target="_blank" href="%1$s"class="button button-secondary pmpro_page_edit">%2$s</a>',
				add_query_arg( $post_arguments, admin_url( 'post.php' ) ),
				__( 'edit page', 'paid-memberships-pro' )
			);
			$html[] = sprintf(
				'<a target="_blank" href="%1$s" class="button button-secondary pmpro_page_view">%2$s</a>',
				get_permalink( $pmpro_pages[ $page_name ] ),
				__( 'view page', 'paid-memberships-pro' )
			);
		}
		
		if ( empty( $hint ) ) {
			$hint = sprintf( '%1$s %2$s',
				__( 'Include the shortcode: ', 'paid-memberships-pro' ),
				sprintf( '[pmpro_%1$s]', $page_name )
			);
		}
		
		if ( ! empty( $hint ) ) {
			$html[] = sprintf( '<br/>' );
			$html[] = sprintf( '<small class="pmpro_lite">%1$s</small>', $hint );
		}
		
		$html[] = sprintf( '</td>' );
		$html[] = sprintf( '</tr>' );
		
		// Assemble HTML string and return it
		return implode( "\n", $html );
	}
	
	/**
	 * Unhook the standard PMPro Page settings page & add our own
	 */
	public function fixPMProPageSettings() {
		
		// remove all traces of the default (PMPro) Admin Page Settings page
		$hook_name = get_plugin_page_hookname( 'pmpro-pagesettings', 'admin.php' );
		remove_action( $hook_name, 'pmpro_pagesettings' );
		remove_submenu_page( 'admin.php', 'pmpro-pagesettings' );
		
		// Add our own
		add_submenu_page(
			'admin.php',
			__( 'Page Settings', 'paid-memberships-pro' ),
			__( 'Page Settings', 'paid-memberships-pro' ),
			'pmpro_pagesettings',
			'pmpro-pagesettings',
			array( $this, 'loadPageSettingsOverride', )
		);
		
		// $utils->log( "Found submenu items: " . print_r( $submenu, true ) );
	}
	
	/**
	 * Overrides the PMPro Page Settings page
	 *
	 * @credit https://github.com/strangerstudios/paid-memberships-pro/blob/dev/adminpages/pagesettings.php
	 *
	 * Filters:
	 *
	 * @uses pmpro_extra_page_settings
	 * @uses pmpro_admin_pagesetting_post_type_array
	 * @uses e20r-directory-page-types
	 */
	public function loadPageSettingsOverride() {
		
		$utils = Utilities::get_instance();
		
		$utils->log( "Loading PMPro Pages settings page from override" );
		
		if ( ! function_exists( "current_user_can" ) ||
		     ( ! current_user_can( "manage_options" ) && ! current_user_can( "pmpro_pagesettings" ) )
		) {
			$utils->log( "Incorrect permissions" );
			wp_die(
				__( "You do not have permissions to perform this action.", 'paid-memberships-pro' )
			);
		}
		
		if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
			$utils->log( "PMPro not active!" );
			
			return null;
		}
		
		$utils->log( "Loading page settings page for PMPro" );
		
		global $wpdb;
		global $msg;
		global $msgt;
		
		/**
		 * Adds additional page settings for use with add-on plugins, etc.
		 *
		 * @param array $pages {
		 *                     Formatted as array($name => $label)
		 *
		 * @type string $name  Page name. (Letters, numbers, and underscores only.)
		 * @type string $label Settings label.
		 * }
		 * @since 1.8.5
		 */
		$extra_pages = apply_filters( 'pmpro_extra_page_settings', array() );
		$post_types  = apply_filters( 'pmpro_admin_pagesetting_post_type_array', array( 'page' ) );
		
		/**
		 * For compatibility with this plugin
		 */
		$post_types = apply_filters( 'e20r-directory-page-types', $post_types );
		
		$save_settings = (bool) $utils->get_variable( 'savesettings', false );
		
		// Do not have a valid NONCE?
		if ( false !== $save_settings ) {
			
			$utils->log( "Nonce not valid? " );
			
			if ( false === check_admin_referer( 'savesettings', 'pmpro_pagesettings_nonce' ) ) {
				
				$utils->log( "NONCE for save settings operation isn't valid" );
				
				$msg  = - 1;
				$msgt = __( 'Are you sure you want to do that? Try again.', 'paid-memberships-pro' );
				unset( $_REQUEST['savesettings'] );
			}
		}
		
		$this->maybeSavePageSettingsFromRequest( $save_settings );
		$this->maybeCreatePages( $extra_pages );
		
		$header    = $this->loadPMProAdmin( 'header' );
		$page_body = $this->loadPageSettingsHTML( $post_types, $extra_pages );
		$footer    = $this->loadPMProAdmin( 'footer' );
		
		echo $header;
		echo $page_body;
		echo $footer;
	}
	
	/**
	 * Save received page settings (from the $_REQUEST)
	 *
	 * @param bool $save_settings
	 *
	 * @return bool
	 */
	public function maybeSavePageSettingsFromRequest( $save_settings ) {
		
		$utils = Utilities::get_instance();
		
		// Nothing to do
		if ( false === $save_settings ) {
			$utils->log( "No need to save the settings... " . print_r( $_REQUEST, true ) );
			
			return true;
		}
		
		$utils->log( "Save default PMPro Page settings" );
		
		global $msg;
		global $msgt;
		global $pmpro_pages;
		
		$status = true;
		
		// Update the default page settings from the REQUEST[] array
		pmpro_setOption( "account_page_id", null, 'intval' );
		pmpro_setOption( "billing_page_id", null, 'intval' );
		pmpro_setOption( "cancel_page_id", null, 'intval' );
		pmpro_setOption( "checkout_page_id", null, 'intval' );
		pmpro_setOption( "confirmation_page_id", null, 'intval' );
		pmpro_setOption( "invoice_page_id", null, 'intval' );
		pmpro_setOption( "levels_page_id", null, 'intval' );
		
		/** Update the global PMPro pages list */
		$pmpro_pages["account"]      = pmpro_getOption( "account_page_id" );
		$pmpro_pages["billing"]      = pmpro_getOption( "billing_page_id" );
		$pmpro_pages["cancel"]       = pmpro_getOption( "cancel_page_id" );
		$pmpro_pages["checkout"]     = pmpro_getOption( "checkout_page_id" );
		$pmpro_pages["confirmation"] = pmpro_getOption( "confirmation_page_id" );
		$pmpro_pages["invoice"]      = pmpro_getOption( "invoice_page_id" );
		$pmpro_pages["levels"]       = pmpro_getOption( "levels_page_id" );
		
		/**
		 * Adds additional page settings for use with add-on plugins, etc.
		 *
		 * @param array $pages {
		 *                     Formatted as array($name => $label)
		 *
		 * @type string $name  Page name. (Letters, numbers, and underscores only.)
		 * @type string $label Settings label.
		 * }
		 * @since 1.8.5
		 */
		$extra_pages = apply_filters( 'pmpro_extra_page_settings', array() );
		
		//Save data from additional pages?
		if ( empty( $extra_pages ) ) {
			$msg  = true;
			$msgt = __( "Your page settings have been updated.", 'paid-memberships-pro' );
		}
		
		$utils->log( "Trigger custom save actions for PMPro settings" );
		do_action( 'e20r-directory-save-pmpro-page-settings' );
		
		if ( empty( $extra_pages ) ) {
			return true;
		}
		
		$utils->log( "Save settings for the extra pages filter?" );
		
		// Process any configured extra pages for the Pages settings
		foreach ( $extra_pages as $name => $label ) {
			
			// Generate the option name based on the Extra pages info
			$option_name = sprintf( '%s_page_id', $name );
			
			// Save the setting and set status
			$status = $status && pmpro_setOption( $option_name, null, 'intval' );
			
			// Load the new setting to the  pmpro page ID cache
			$pmpro_pages[ $name ] = pmpro_getOption( $option_name );
		}
		
		// If we're successful, we'll post a message
		if ( true === $status ) {
			$msg  = $status;
			$msgt = __( "Your page settings have been updated.", 'paid-memberships-pro' );
		}
	}
	
	/**
	 * Maybe create PMPro and PMPro Add-on pages
	 *
	 * @param array $extra_pages
	 *
	 * @return bool
	 */
	public function maybeCreatePages( $extra_pages ) {
		
		global $pmpro_pages;
		
		$utils = Utilities::get_instance();
		
		$create_pages = (bool) $utils->get_variable( 'createpages', false );
		$page_nonce   = $utils->get_variable( 'pmpro_pagesettings_nonce', null );
		
		if ( false === $create_pages ) {
			return true;
		}
		
		// Check nonce if we're going to generate pages
		if ( true === $create_pages &&
		     ( empty( $page_nonce ) || false === check_admin_referer( 'createpages', 'pmpro_pagesettings_nonce' ) )
		) {
			$msg  = - 1;
			$msgt = __( "Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
			unset( $_REQUEST['createpages'] );
			
			return true;
		}
		
		$page_name       = $utils->get_variable( 'page_name', null );
		$pages           = array();
		$pmpro_page_name = null;
		
		if ( empty( $page_name ) ) {
			
			$pages = array(
				'account'      => __( 'Membership Account', 'paid-memberships-pro' ),
				'billing'      => __( 'Membership Billing', 'paid-memberships-pro' ),
				'cancel'       => __( 'Membership Cancel', 'paid-memberships-pro' ),
				'checkout'     => __( 'Membership Checkout', 'paid-memberships-pro' ),
				'confirmation' => __( 'Membership Confirmation', 'paid-memberships-pro' ),
				'invoice'      => __( 'Membership Invoice', 'paid-memberships-pro' ),
				'levels'       => __( 'Membership Levels', 'paid-memberships-pro' ),
			);
		} else {
			
			$pmpro_page_name           = $utils->get_variable( 'page_name', null );
			$pmpro_page_id             = $pmpro_pages[ $pmpro_page_name ];
			$pages[ $pmpro_page_name ] = isset( $extra_pages[ $pmpro_page_name ] ) ? $extra_pages[ $pmpro_page_name ] : null;
		}
		
		$pages_created = pmpro_generatePages( $pages );
		
		if ( ! empty( $pages_created ) ) {
			
			$msg  = true;
			$msgt = sprintf(
				__( "The following pages have been created: %s.", E20R_Directory_For_PMPro::plugin_slug ),
				implode( ", ", $pages_created )
			);
		}
	}
	
	/**
	 * Load the specified PMPro header/footer (type)
	 *
	 * @param string $type
	 *
	 * @return string|null
	 */
	public function loadPMProAdmin( $type ) {
		
		if ( ! defined( 'PMPRO_DIR' ) ) {
			return null;
		}
		
		$path_to_file = sprintf( '%1$s/adminpages/admin_%2$s.php', PMPRO_DIR, $type );
		
		ob_start();
		require_once( $path_to_file );
		
		return ob_get_clean();
	}
	
	/**
	 * Load HTML for the Page settings (for PMPro)
	 *
	 * @param array $post_types
	 * @param array $extra_pages
	 *
	 * @return false|string
	 */
	public function loadPageSettingsHTML( $post_types, $extra_pages ) {
		
		global $pmpro_pages;
		$utils = Utilities::get_instance();
		
		$page_url     = add_query_arg( 'page', 'pmpro-pagesettings', admin_url( 'admin.php' ) );
		$manual_pages = (bool) $utils->get_variable( 'manualpages', false );
		
		$default_pages = array(
			'account'      => __( 'Membership Account', 'paid-memberships-pro' ),
			'billing'      => __( 'Membership Billing', 'paid-memberships-pro' ),
			'cancel'       => __( 'Membership Cancel', 'paid-memberships-pro' ),
			'checkout'     => __( 'Membership Checkout', 'paid-memberships-pro' ),
			'confirmation' => __( 'Membership Confirmation', 'paid-memberships-pro' ),
			'invoice'      => __( 'Membership Invoice', 'paid-memberships-pro' ),
			'levels'       => __( 'Membership Levels', 'paid-memberships-pro' ),
		);
		
		ob_start(); ?>

        <form action="<?php echo esc_url( $page_url ); ?>" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'savesettings', 'pmpro_pagesettings_nonce' ); ?>
            <h2><?php _e( 'Page Settings', 'paid-memberships-pro' ); ?></h2>
			<?php echo $this->insertDescription(); ?>
			
			<?php if ( ! empty( $pmpro_pages_ready ) || false === $manual_pages ) { ?>
                <table class="form-table">
                    <tbody>
					<?php foreach ( $default_pages as $page_name => $page_label ) {
						$utils->log( "Loading page settings for: {$page_name}" );
						echo $this->generatePageTableRow( $page_name, $page_label );
					} ?>
					<?php do_action( 'e20r-directory-process-extra-pages', $extra_pages ); ?>
                    </tbody>
                </table>
                <p class="submit">
                    <input name="savesettings" type="submit" class="button button-primary"
                           value="<?php _e( 'Save Settings', 'paid-memberships-pro' ); ?>"/>
                </p>
			<?php } ?>
        </form>
		<?php
		
		return ob_get_clean();
	}
	
	/**
	 * Load description (top of settings page) for the PMPro Pages settings
	 *
	 * @return string
	 */
	private function insertDescription() {
		
		$utils            = Utilities::get_instance();
		$manual_pages     = $utils->get_variable( 'manualpages', false );
		$create_page_args = array( 'page' => 'pmpro-pagesettings', 'createpages' => true );
		$manual_page_args = array( 'page' => 'pmpro-pagesettings', 'manualpages' => true );
		$html             = array();
		
		global $pmpro_pages_ready;
		
		if ( $pmpro_pages_ready ) {
			$html[] = sprintf(
				'<p>%1$s</p>',
				__( 'Manage the WordPress pages assigned to each required Paid Memberships Pro page.', 'paid-memberships-pro' ) );
		} else if ( ! empty( $manual_pages ) ) {
			$html[] = sprintf(
				'<p>%1$s <a href="%2$s">%3$s</a></p>',
				__( 'Assign the WordPress pages for each required Paid Memberships Pro page or', 'paid-memberships-pro' ),
				wp_nonce_url( add_query_arg( $create_page_args, admin_url( 'admin.php' ) ), 'createpages', 'pmpro_pagesettings_nonce' ),
				__( 'click here to let us generate them for you', 'paid-memberships-pro' )
			);
			
			
		} else {
			$html[] = sprintf( '<div class="pmpro-new-install">' );
			$html[] = sprintf( '<h2>%1$s</h2>', __( 'Manage Pages', 'paid-memberships-pro' ) );
			$html[] = sprintf(
				'<h4>%1$s</h4>',
				__( 'Several frontend pages are required for your Paid Memberships Pro site.', 'paid-memberships-pro' )
			);
			$html[] = sprintf(
				'<a href="%1$s" class="button-primary">%2$s</a>',
				wp_nonce_url(
					add_query_arg(
						$create_page_args,
						admin_url( 'admin.php' )
					),
					'createpages',
					'pmpro_pagesettings_nonce'
				),
				__( 'Generate Pages For Me', 'paid-memberships-pro' )
			);
			$html[] = sprintf( '<a href="%1$s" class="button">%2$s</a>',
				esc_url( add_query_arg( $manual_page_args, admin_url( 'admin.php' ) ) ),
				__( 'Create Pages Manually', 'paid-memberships-pro' )
			);
			$html[] = sprintf( ' </div> <!-- end pmpro-new-install -->' );
		}
		
		return implode( "\n", $html );
	}
	
	/**
	 * Hide the clone() magic method (singleton)
	 *
	 * @access private
	 */
	private function __clone() {
		// TODO: Implement __clone() method.
	}
}