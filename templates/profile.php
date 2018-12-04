<?php

/**
 * This shortcode will display the profile for the user ID specified in the URL and
 * additional content based on the defined attributes.
 *
 * @credit https://www.paidmembershipspro.com
 */
global $pmproemd_show_billing_address;
global $pmproemd_show_shipping_address;
global $pmproemd_has_billing_fields;
global $pmproemd_has_shipping_fields;

function pmproemd_profile_preheader() {
	global $post, $pmpro_pages, $current_user;
	if ( ! empty( $post->ID ) && $post->ID == $pmpro_pages['profile'] ) {
		/*
			Preheader operations here.
		*/
		global $main_post_id;
		$main_post_id = $post->ID;
		
		//Get the profile user
		if ( ! empty( $_REQUEST['pu'] ) && is_numeric( $_REQUEST['pu'] ) ) {
			$profile_user = get_user_by( 'id', intval( $_REQUEST['pu'] ) );
		} else if ( ! empty( $_REQUEST['pu'] ) ) {
			$profile_user = get_user_by( 'slug', sanitize_text_field( $_REQUEST['pu'] ) );
		} else if ( ! empty( $current_user->ID ) ) {
			$profile_user = $current_user;
		} else {
			$profile_user = false;
		}
		
		//If no profile user, go to directory or home
		if ( empty( $profile_user ) || empty( $profile_user->ID ) ) {
			if ( ! empty( $pmpro_pages['directory'] ) ) {
				wp_redirect( get_permalink( $pmpro_pages['directory'] ) );
			} else {
				wp_redirect( home_url() );
			}
			exit;
		}
		
		/*
			If a level is required for the profile page, make sure the profile user has it.
		*/
		//check is levels are required
		$levels = pmpro_getMatches( "/ levels?=[\"']([^\"^']*)[\"']/", $post->post_content, true );
		if ( ! empty( $levels ) && ! pmpro_hasMembershipLevel( explode( ",", $levels ), $profile_user->ID ) ) {
			if ( ! empty( $pmpro_pages['directory'] ) ) {
				wp_redirect( get_permalink( $pmpro_pages['directory'] ) );
			} else {
				wp_redirect( home_url() );
			}
			exit;
		}
		
		/*
			Update the head title and H1
		*/
		function pmproemd_the_title( $title, $post_id = null ) {
			
			global $main_post_id, $current_user;
			
			if ( $post_id == $main_post_id ) {
				
				if ( ! empty( $_REQUEST['pu'] ) ) {
					
					global $wpdb;
					$user_nicename = sanitize_text_field( $_REQUEST['pu'] );
					$display_name  = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT u.display_name
                                      FROM {$wpdb->users} AS u
                                      WHERE u.user_nicename = %s
                                      LIMIT 1",
							$user_nicename
						)
					);
					
				} else if ( ! empty( $current_user ) ) {
					$display_name = $current_user->display_name;
				}
				if ( ! empty( $display_name ) ) {
					$title = $display_name;
				}
			}
			
			return $title;
		}
		
		add_filter( "the_title", "pmproemd_the_title", 10, 2 );
		
		function pmproemd_wp_title( $title, $sep ) {
			global $wpdb, $main_post_id, $post, $current_user;
			if ( $post->ID == $main_post_id ) {
				if ( ! empty( $_REQUEST['pu'] ) ) {
					
					$user_nicename = sanitize_text_field( $_REQUEST['pu'] );
					
					$display_name = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT u.display_name
                                      FROM {$wpdb->users} AS u
                                      WHERE u.user_nicename = %s
                                      LIMIT 1",
							$user_nicename
						)
					);
				} else if ( ! empty( $current_user ) ) {
					$display_name = $current_user->display_name;
				}
				if ( ! empty( $display_name ) ) {
					$title = $display_name . ' ' . $sep . ' ';
				}
				$title .= get_bloginfo( 'name' );
			}
			
			return $title;
		}
		
		add_filter( "wp_title", "pmproemd_wp_title", 10, 2 );
	}
}

add_action( "wp", "pmproemd_profile_preheader", 1 );

function pmproemd_profile_shortcode( $atts, $content = null, $code = "" ) {
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_member_profile avatar="false" email="false"]
	
	$avatar_size      = '128';
	$fields           = null;
	$show_avatar      = null;
	$billing_address  = null;
	$shipping_address = null;
	$show_bio         = null;
	$show_billing     = null;
	$show_email       = null;
	$show_level       = null;
	$show_name        = null;
	$show_phone       = null;
	$show_search      = null;
	$show_startdate   = null;
	$user_id          = null;
	
	extract( shortcode_atts( array(
		'avatar_size'      => '128',
		'fields'           => null,
		'billing_address'  => 'false',
		'shipping_address' => 'false',
		'show_avatar'      => 'true',
		'show_bio'         => 'true',
		'show_billing'     => 'true',
		'show_email'       => 'true',
		'show_level'       => 'true',
		'show_name'        => 'true',
		'show_phone'       => 'true',
		'show_search'      => 'true',
		'show_startdate'   => 'true',
		'user_id'          => null,
	), $atts ) );
	
	global $current_user;
	global $display_name;
	global $wpdb;
	global $pmpro_pages;
	global $pmprorh_registration_fields;
	
	global $pmproemd_show_billing_address;
	global $pmproemd_show_shipping_address;
	
	//some page vars
	$directory_url = ! empty( $pmpro_pages['directory'] ) ? get_permalink( $pmpro_pages['directory'] ) : null;
	$profile_url   = ! empty( $pmpro_pages['profile'] ) ? get_permalink( $pmpro_pages['profile'] ) : null;
	
	//turn 0's into falses
	$show_avatar      = pmproemd_true_false( $show_avatar );
	$show_billing     = pmproemd_true_false( $show_billing );
	$show_bio         = pmproemd_true_false( $show_bio );
	$show_email       = pmproemd_true_false( $show_email );
	$show_level       = pmproemd_true_false( $show_level );
	$show_name        = pmproemd_true_false( $show_name );
	$show_phone       = pmproemd_true_false( $show_phone );
	$show_search      = pmproemd_true_false( $show_search );
	$show_startdate   = pmproemd_true_false( $show_startdate );
	$billing_address  = pmproemd_true_false( $billing_address );
	$shipping_address = pmproemd_true_false( $shipping_address );
	
	$pmproemd_show_billing_address  = $billing_address;
	$pmproemd_show_shipping_address = $shipping_address;
 
	$limit = isset( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : 15;
	
	if ( true === $pmproemd_show_billing_address || true === $pmproemd_show_shipping_address ) {
		require_once( dirname( __FILE__ ) . "/../includes/address-section.php" );
	}
 
	
	if ( empty( $user_id ) && ! empty( $_REQUEST['pu'] ) ) {
		//Get the profile user
		if ( is_numeric( $_REQUEST['pu'] ) ) {
			$profile_user = get_user_by( 'id', intval( $_REQUEST['pu'] ) );
		} else if ( is_email( $_REQUEST['pu'] ) ) {
			$profile_user = get_user_by( 'email', sanitize_email( $_REQUEST['pu'] ) );
		} else {
			$profile_user = get_user_by( 'slug', sanitize_text_field( $_REQUEST['pu'] ) );
		}
		
		if ( ! empty( $profile_user ) ) {
			$user_id = $profile_user->ID;
		}
	}
	
	// Load the specified user ID
	if ( ! empty( $user_id ) ) {
		$profile_user = get_userdata( $user_id );
	} else if ( empty( $_REQUEST['pu'] ) ) {
		$profile_user = get_userdata( $current_user->ID );
	}
	
	if ( ! empty( $profile_user ) ) {
		$profile_user->membership_level = pmpro_getMembershipLevelForUser( $profile_user->ID );
	}
	
	ob_start();
	
	?>
	<?php if ( ! empty( $show_search ) ) { ?>
        <form action="<?php echo esc_url_raw( $directory_url ); ?>" method="post" role="search"
              class="pmpro_member_directory_search search-form">
            <label>
                <span class="screen-reader-text"><?php _e( 'Search for:', 'label' ); ?></span>
                <input type="search" class="search-field"
                       placeholder="<?php _e( "Search Members", "pmpro-member-directory" ); ?>" name="ps"
                       value="<?php if ( ! empty( $_REQUEST['ps'] ) ) {
					       esc_attr_e( $_REQUEST['ps'] );
				       } ?>" title="<?php _e( "Search Members", "pmpro-member-directory" ); ?>"/>
                <input type="hidden" name="limit" value="<?php echo esc_attr( $limit ); ?>"/>
            </label>
	        <?php
	        $search_fields = apply_filters( 'pmpro_member_directory_extra_search_input', array() );
	
	        if ( ! empty( $search_fields ) ) {
		
		        if ( ! empty( $search_fields ) && is_array( $search_fields ) ) {
			        foreach ( $search_fields as $search_field ) {
				        printf( '%s', $search_field );
			        }
		        }
	        }
	        do_action( 'pmpro_member_directory_extra_search_output' );
	        ?>
            <input type="submit" class="search-submit"
                   value="<?php _e( "Search Members", "pmpro-member-directory" ); ?>">
        </form>
	<?php } ?>
	<?php
	if ( ! empty( $profile_user ) ) {
		if ( ! empty( $fields ) ) {
			$fields_array = explode( ";", $fields );
			if ( ! empty( $fields_array ) ) {
				for ( $i = 0; $i < count( $fields_array ); $i ++ ) {
					$fields_array[ $i ] = explode( ",", $fields_array[ $i ] );
				}
			}
		} else {
			$fields_array = false;
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
		}
		
		?>
        <div id="pmpro_member_profile-<?php esc_attr_e( $profile_user->ID ); ?>" class="pmpro_member_profile">
			<?php if ( true === $show_avatar ) { ?>
                <p class="pmpro_member_directory_avatar">
					<?php echo get_avatar( $profile_user->ID, $avatar_size, null, $profile_user->display_name, array( "class" => "alignright" ) ); ?>
                </p>
			<?php } ?>
			<?php if ( true === $show_name && ! empty( $profile_user->display_name ) ) { ?>
                <h2 class="pmpro_member_directory_name">
					<?php esc_html_e( $profile_user->display_name ); ?>
                </h2>
			<?php } ?>
			<?php if ( true === $show_bio && ! empty( $profile_user->description ) ) { ?>
                <p class="pmpro_member_directory_bio">
                    <strong><?php _e( 'Biographical Info', 'wp' ); ?></strong>
					<?php esc_html_e( $profile_user->description ); ?>
                </p>
			<?php } ?>
			<?php if ( true === $show_email ) { ?>
                <p class="pmpro_member_directory_email">
                    <strong><?php _e( 'Email Address', 'pmpro' ); ?></strong>
					<?php esc_html_e( $profile_user->user_email ); ?>
                </p>
			<?php } ?>
			<?php if ( true === $show_level ) { ?>
                <p class="pmpro_member_directory_level">
                    <strong><?php _e( 'Level', 'pmpro' ); ?></strong>
					<?php esc_html_e( $profile_user->membership_level->name ); ?>
                </p>
			<?php } ?>
			<?php if ( true === $show_startdate ) { ?>
                <p class="pmpro_member_directory_date">
                    <strong><?php _e( 'Start Date', 'pmpro' ); ?></strong>
					<?php echo date_i18n( get_option( "date_format" ), $profile_user->membership_level->startdate ); ?>
                </p>
			<?php } ?>
			<?php if ( ( true === $show_billing && ( false === $pmproemd_show_billing_address && false === $pmproemd_show_shipping_address ) ) && ! empty( $profile_user->pmpro_baddress1 ) ) { ?>
                <p class="pmpro_member_directory_baddress">
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
			<?php if ( true === $show_phone && ! empty( $profile_user->pmpro_bphone ) ) { ?>
                <p class="pmpro_member_directory_phone">
                    <strong><?php _e( 'Phone Number', 'pmpro' ); ?></strong>
					<?php echo formatPhone( $profile_user->pmpro_bphone ); ?>
                </p>
			<?php } ?>
			<?php
			// Save a copy of the extracted fields (for the pmproemd_add_extra_profile_output action)
			$real_fields_array = $fields_array;
			//filter the fields
			$fields_array = apply_filters( 'pmpro_member_profile_fields', $fields_array, $profile_user );
			
			if ( ! empty( $fields_array ) ) {
				foreach ( $fields_array as $field ) {
					if ( empty( $field[0] ) ) {
						break;
					}
					error_log("Field info: " . print_r($field,true));
					
					$meta_field = wp_unslash( apply_filters( 'pmpro_member_directory_metafield_value', $profile_user->{$field[1]}, $field[1], $profile_user ) );
					if ( ! empty( $meta_field ) ) {
						?>
                        <p class="pmpro_member_directory_<?php echo esc_attr( $field[1] ); ?>">
							<?php
							if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
								//this is a file field
								?>
                                <strong><?php esc_html_e( $field[0] ); ?></strong>
								<?php echo pmproemd_display_file_field( $meta_field ); ?>
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
								<?php echo apply_filters( 'pmpro_member_directory_metafield_value', implode( ", ", $meta_field ), $field[1], $profile_user ); ?>
								<?php
							} else {
								if ( false !== stripos( $field[1],  'url' ) ) {
									?>
                                    <a href="<?php echo esc_url( apply_filters( 'pmpro_member_directory_metafield_value', $meta_field, $field[1], $profile_user ) ); ?>"
                                       target="_blank"><?php esc_html_e( $field[0] ); ?></a>
									<?php
								} else {
									?>
                                    <strong><?php esc_html_e( $field[0] ); ?></strong>
									<?php
									$meta_field_embed = wp_oembed_get( $meta_field );
									if ( ! empty( $meta_field_embed ) ) {
										echo apply_filters( 'pmpro_member_directory_metafield_value', $meta_field_embed, $field[1], $profile_user );
									} else {
										echo make_clickable( apply_filters( 'pmpro_member_directory_metafield_value', $meta_field, $field[1], $profile_user ) );
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
			
			do_action( 'pmproemd_add_extra_profile_output', $real_fields_array, $profile_user );
			?>

            <div class="pmpro_clear"></div>
        </div>
        <hr/>
		<?php if ( apply_filters( 'pmpro_member_directory_profile_show_return_link', true ) && ! empty( $directory_url ) ) { ?>
            <div align="center"><a class="more-link"
                                   href="<?php echo esc_url_raw( $directory_url ); ?>"><?php _e( 'View All Members', 'pmpro-member-directory' ); ?></a>
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

add_shortcode( "pmpro_member_profile", "pmproemd_profile_shortcode" );
