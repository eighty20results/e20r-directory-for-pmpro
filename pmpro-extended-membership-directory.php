<?php
/*
Plugin Name: Extended Member Directory for Paid Memberships Pro (Add-on)
Plugin URI: https://eighty20results.com/wordpress-plugins/pmpro-extended-membership-directory
Description: Extended version of the PMPro Member Directory add-on
Version: 1.6
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
	define( 'PMPROED_VER', "1.5.2" );
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

//show the option to hide from directory on edit user profile
function pmproemd_show_extra_profile_fields( $user ) {
	global $pmpro_pages;
	?>
    <h3><?php echo get_the_title( $pmpro_pages['directory'] ); ?></h3>
    <table class="form-table">
        <tbody>
        <tr class="user-hide-directory-wrap">
            <th scope="row"></th>
            <td>
                <label for="hide_directory">
                    <input name="hide_directory" type="checkbox" id="hide_directory" <?php checked( get_user_meta( $user->ID, 'pmpromd_hide_directory', true ), 1 ); ?> value="1"><?php printf( __( 'Hide from %s?', 'pmpro-member-directory' ), get_the_title( $pmpro_pages['directory'] ) ); ?>
                </label>
            </td>
        </tr>
        </tbody>
    </table>
	<?php
}

add_action( 'show_user_profile', 'pmproemd_show_extra_profile_fields', 10 );
add_action( 'edit_user_profile', 'pmproemd_show_extra_profile_fields', 10 );

function pmproemd_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	
	if ( isset( $_POST['hide_directory'] ) ) {
		update_user_meta( $user_id, 'pmpromd_hide_directory', true );
	}
}

add_action( 'personal_options_update', 'pmproemd_save_extra_profile_fields', 10 );
add_action( 'edit_user_profile_update', 'pmproemd_save_extra_profile_fields', 10 );


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

/*
Function to add links to the plugin row meta
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