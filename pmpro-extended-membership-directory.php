<?php
/*
Plugin Name: Paid Memberships Pro - Enhanced Member Directory and Profile Pages
Plugin URI: https://eighty20results.com/wordpress-plugins/pmpro-extended-membership-directory
Description: Replaces and significantly enhances the functionality of the "Paid Memberships Pro - Member Directory and Profile Pages" add-on, including more precise user metadata search capabilities (by link - URL - or input field(s)), user profile links, easy-to-add search fields/drop-downs, etc.
Version: 2.8
Author: eighty20results, strangerstudios
Author URI: https://eighty20results.com/thomas-sjolshagen
Text Domain: pmpro-extended-membership-directory
Domain Path: /languages
License: GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * @credit https://www.paidmembershipspro.com
 */

global $pmpromd_options;

if ( ! defined( "PMPRO_EXTENDED_DIRECTORY" ) ) {
	define( 'PMPRO_EXTENDED_DIRECTORY', true );
}

if ( ! defined( "PMPROED_VER" ) ) {
	define( 'PMPROED_VER', "2.8" );
}

/**
 * Check for the original PMPro Member Directory add-on
 */
function pmproemd_init() {
 
    if ( function_exists( 'pmpromd_register_styles' ) ) {
        
        pmpro_setMessage( __( "The 'Member Directory & Profile Pages' add-on for Paid Memberships Pro is currently active. You'll need to deactivate it in order to activate this Extended version of the plugin.", 'pmpro-member-directory' ), 'error' );
        
        return;
    }
}

/**
 * Show error notice(s) if found
 */
function pmproemd_admin_notice( ) {
    
    global $pmpro_msg;
    global $pmpro_msgt;
    
    if ( !empty( $pmpro_msg ) ) {
        ?>
        <div class="notice notice-<?php esc_attr_e( $pmpro_msgt ); ?> is-dismissible">
            <p><?php esc_html_e( $pmpro_msg ); ?></p>
        </div>
        <?php
    }
}
add_action( 'admin_init', 'pmproemd_admin_notice', 10 );
add_action( 'plugins_loaded', 'pmproemd_init', 99 );

$path                  = dirname( __FILE__ );
$custom_dir            = get_stylesheet_directory() . "/paid-memberships-pro/pmpro-member-directory/";
$custom_directory_file = $custom_dir . "directory.php";
$custom_profile_file   = $custom_dir . "profile.php";

//load custom or default templates
if ( file_exists( $custom_directory_file ) ) {
	require_once( $custom_directory_file );
} else {
	require_once( $path . "/templates/directory.php" );
}
if ( file_exists( $custom_profile_file ) ) {
	require_once( $custom_profile_file );
} else {
	require_once( $path . "/templates/profile.php" );
}

// Add localization feature(s)
require_once( "{$path}/includes/localization.php");

/**
 * Check if the value is one of the valid responses for the boolean type
 *
 * @param int|string $value
 * @param string $type
 *
 * @return bool
 */
function pmproemd_true_false( $value, $type = 'false' ) {
    
    
    switch( $type ) {
        case 'true':
	        // Return true if we found one of the 'true' values
	        $found = in_array( $value,array( 'yes', '1', 'true' ) );
	        error_log("Checking for true: {$value} -> " . ( $found ? 'Found' : 'Not Found' ) );
	        return ( true === $found ? true : false );
            break;
        default:
	        // Return false if we found one of the 'false' values
	        $found = in_array( $value,array( 'no', 'false', '0' ) );
	        error_log("Checking for false: {$value} -> " . ( $found ? 'Found' : 'Not Found' ) );
            return ( true === $found ? false : true );
    }
}

/**
 * Include existing REQUEST arguments (when they belong to the 'extra search fields')
 *
 * @param array $arguments
 *
 * @return array
 */
function pmproemd_pagination_args( $arguments ) {
    
    $extra_search_fields = apply_filters( 'pmpromd_extra_search_fields', array() );
    
    foreach( $extra_search_fields as $ef_name ) {
        
        $req_value = ( isset( $_REQUEST[$ef_name] ) && !empty(  $_REQUEST[$ef_name] ) ? sanitize_text_field(  $_REQUEST[$ef_name] ) : null );
        
	    if ( ! isset( $addl_arguments[$ef_name] ) && ! is_null( $req_value ) ) {
	        $arguments[$ef_name] = $req_value;
        }
    }
    
    return $arguments;
}

/**
 * Register/load Directory/Profile page styles as/when needed
 */
function pmproemd_register_styles() {
	//load stylesheet (check child theme, then parent theme, then plugin folder)	
	if ( file_exists( get_stylesheet_directory() . "/paid-memberships-pro/member-directory/css/pmpro-member-directory.css" ) ) {
		wp_register_style( 'pmpro-member-directory-styles', get_stylesheet_directory_uri() . "/paid-memberships-pro/member-directory/css/pmpro-member-directory.css", null, PMPROED_VER );
	} else if ( file_exists( get_template_directory() . "/paid-memberships-pro/member-directory/css/pmpro-member-directory.css" ) ) {
		wp_register_style( 'pmpro-member-directory-styles', get_template_directory_uri() . "/paid-memberships-pro/member-directory/css/pmpro-member-directory.css", null, PMPROED_VER );
	} else if ( function_exists( "pmpro_https_filter" ) ) {
		wp_register_style( 'pmpro-member-directory-styles', pmpro_https_filter( plugins_url( 'css/pmpro-member-directory.css', __FILE__ ) ), null, PMPROED_VER );
	} else {
		wp_register_style( 'pmpro-member-directory-styles', plugins_url( 'css/pmpro-member-directory.css', __FILE__ ), null, PMPROED_VER );
	}
	
	wp_enqueue_style( 'pmpro-member-directory-styles' );
}
add_action( 'wp_enqueue_scripts', 'pmproemd_register_styles', 10 );

/**
 * Load pages to include on "Memberships" -> "Page Settings" page for Paid Memberships Pro
 *
 * @param array $pages
 *
 * @return array
 */
function pmproemd_extra_page_settings( $pages ) {
	$pages['directory'] = array(
		'title'   => __( 'Directory', 'pmpro-member-directory' ),
		'content' => '[pmpro_member_directory]',
		'hint'    => __( 'Include the shortcode [pmpro_member_directory].', 'pmpro-member-directory' ),
	);
	$pages['profile']   = array(
		'title'   => __( 'Profile', 'pmpro-member-directory' ),
		'content' => '[pmpro_member_profile]',
		'hint'    => __( 'Include the shortcode [pmpro_member_profile].', 'pmpro-member-directory' ),
	);
	
	return $pages;
}

add_action( 'pmpro_extra_page_settings', 'pmproemd_extra_page_settings', 10 );

/**
 * Let user decide to show/hide their entry in the directory via their profile
 *
 * @param \WP_User $user
 */
function pmproemd_show_extra_profile_fields( $user ) {
	global $pmpro_pages;
	
	$is_admin = current_user_can( 'manage_options' );
	
	if ( (
	        true === apply_filters( 'pmpro_member_directory_non_admin_profile_settings', true ) &&
            false === $is_admin
         ) || true === $is_admin
    ) {
		?>
        <h3><?php echo get_the_title( $pmpro_pages['directory'] ); ?></h3>
        <table class="form-table">
            <tbody>
            <tr class="user-hide-directory-wrap">
                <th scope="row"></th>
                <td>
                    <label for="pmproed_hide_directory">
                        <input name="pmproed_hide_directory" type="checkbox"
                               id="pmproed_hide_directory" <?php checked( get_user_meta( $user->ID, 'pmpromd_hide_directory', true ), 1 ); ?>
                               value="1"><?php printf( __( 'Hide from %s?', 'pmpro-member-directory' ), get_the_title( $pmpro_pages['directory'] ) ); ?>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}
}

add_action( 'show_user_profile', 'pmproemd_show_extra_profile_fields', 10 );
add_action( 'edit_user_profile', 'pmproemd_show_extra_profile_fields', 10 );

/**
 * Save the 'exclude from directory' setting for the user
 *
 * @param int $user_id
 *
 * @return bool
 */
function pmproemd_save_extra_profile_fields( $user_id ) {
	
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	
	if ( isset( $_POST['pmproed_hide_directory'] ) ) {
		update_user_meta( $user_id, 'pmpromd_hide_directory', true );
	}
}

add_action( 'personal_options_update', 'pmproemd_save_extra_profile_fields', 10 );
add_action( 'edit_user_profile_update', 'pmproemd_save_extra_profile_fields', 10 );

/**
 * Display the correct media type
 *
 * @param array $meta_field
 *
 * @return string
 */
function pmproemd_display_file_field( $meta_field ) {
 
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
 * Include our plugin's links in the plugin row meta of the plugins.php page
 *
 * @param array $links
 * @param string $file
 *
 * @return array
 */
function pmproemd_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-extended-member-directory.php' ) !== false ) {
		$new_links = array(
			sprintf( '<a href="%s" title="%s">%s</a>', esc_url( 'http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-member-directory/' ),__( 'View Documentation', 'paid-memberships-pro' ), __( 'Docs', 'paid-memberships-pro' )  ),
			sprintf( '<a href="%s" title="%s">%s</a>',esc_url( 'http://paidmembershipspro.com/support/' ),__( 'Visit Customer Support Forum', 'pmpro' ),  __( 'Support', 'paid-memberships-pro' ) ),
		);
		$links     = array_merge( $links, $new_links );
	}
	
	return $links;
}

add_filter( 'plugin_row_meta', 'pmproemd_plugin_row_meta', 10, 2 );

if ( ! class_exists( '\\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/pmpro-extended-membership-directory/metadata.json',
	__FILE__,
	'pmpro-extended-membership-directory'
);