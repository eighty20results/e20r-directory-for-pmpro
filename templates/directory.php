<?php
/**
 * This shortcode will display the members list and additional content based on the defined attributes.
 * @credit https://www.paidmembershipspro.com
 */
function pmproemd_shortcode( $atts, $content = null, $code = "" ) {
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_member_directory show_avatar="false" show_email="false" levels="1,2"]
	
	// init vars
	$avatar_size       = '128';
	$fields            = null;
	$layout            = 'div';
	$level             = null;
	$levels            = null;
	$limit             = null;
	$link              = null;
	$order_by          = 'u.display_name';
	$order             = 'ASC';
	$show_avatar       = null;
	$show_email        = null;
	$show_level        = null;
	$show_search       = null;
	$show_startdate    = null;
	$limit_to          = null;
	$show_roles        = null;
	$members_only_link = true;
	$editable_profile  = false;
	
	extract( shortcode_atts( array(
		'avatar_size'       => '128',
		'fields'            => null,
		'layout'            => 'div',
		'level'             => null,
		'levels'            => null,
		'limit'             => 'true',
		'link'              => 'true',
		'order_by'          => 'u.display_name',
		'order'             => 'ASC',
		'show_avatar'       => 'true',
		'show_email'        => 'true',
		'show_level'        => 'true',
		'show_search'       => 'true',
		'show_startdate'    => 'true',
		'limit_to'          => 15,
		'show_roles'        => 'false',
		'members_only_link' => 'false',
		'editable_profile'  => 'false',
	), $atts ) );
	
	global $wpdb, $post, $pmpro_pages, $pmprorh_registration_fields, $current_user;
	
	$levels = empty( $levels ) && ! empty( $level ) ? $level : $levels;
	
	$link              = pmproemd_true_false( $link );
	$show_avatar       = pmproemd_true_false( $show_avatar );
	$show_email        = pmproemd_true_false( $show_email );
	$show_level        = pmproemd_true_false( $show_level );
	$show_search       = pmproemd_true_false( $show_search );
	$show_startdate    = pmproemd_true_false( $show_startdate );
	$limit_to          = pmproemd_true_false( $limit_to );
	$members_only_link = pmproemd_true_false( $members_only_link );
	$editable_profile  = pmproemd_true_false( $editable_profile );
	
	$directory_url = ! empty( $pmpro_pages['directory'] ) ? get_permalink( $pmpro_pages['directory'] ) : null;
	$profile_url   = ! empty( $pmpro_pages['profile'] ) ? get_permalink( $pmpro_pages['profile'] ) : null;
	
	$roles = true === $show_roles ? array_map( 'trim', explode( ',', strtolower( $show_roles ) ) ) : array();
	
	// Set & sanitize request values
	$s     = isset( $_REQUEST['ps'] ) ? sanitize_text_field( $_REQUEST['ps'] ) : null;
	$pn    = isset( $_REQUEST['pn'] ) ? intval( $_REQUEST['pn'] ) : 1;
	$limit = isset( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : 15;
	
	/*
	 * Add support for user defined search fields & tables (array value = usermeta field name)
	 * Can be array of field names (usermeta fields)
	 *
	 * @filter pmpromd_extra_search_fields
	 * @param array
	 */
	$extra_search_fields = apply_filters( 'pmpromd_extra_search_fields', array() );
	
	/**
	 * Whether to use the exact value specified (useful for drop-down based metavalues)
	 *
	 * @filter pmpromd_exact_search_values
	 *
	 * @param bool - False (uses 'LIKE' with wildcard comparisons for metadata
	 */
	$use_precise_values = apply_filters( 'pmpromd_exact_search_values', false );
	
	if ( ! empty( $extra_search_fields ) && ! is_array( $extra_search_fields ) ) {
		$extra_search_fields = array( $extra_search_fields );
	}
	
	if ( ! empty( $extra_search_fields ) ) {
		
		foreach ( $extra_search_fields as $field_name ) {
			if ( isset( $_REQUEST[ $field_name ] ) && is_array( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = array_map( 'sanitize_text_field', $_REQUEST[ $field_name ] );
			} else if ( isset( $_REQUEST[ $field_name ] ) && ! is_array( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = sanitize_text_field( $_REQUEST[ $field_name ] );
			} else if ( empty( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = null;
			}
		}
	}
	$end   = $pn * $limit;
	$start = $end - $limit;
 
	// Member statuses to include in the search (default is 'active' only)
	$statuses    = apply_filters( 'pmpromd_membership_statuses', array( 'active' ) );
	
	// Backwards compatibility
	$statuses = apply_filters( 'pmprod_membership_statuses',  $statuses );
	
	$status_list = esc_sql( implode( "', '", $statuses ) );
	
	if ( ! empty( $s ) || ! empty( $extra_search_fields ) ) {
		$sqlQuery = "
		SELECT SQL_CALC_FOUND_ROWS
			u.ID AS ID,
			u.user_login,
			u.user_email,
			u.user_nicename,
			u.display_name,
			UNIX_TIMESTAMP(u.user_registered) as joindate,
			mu.membership_id, mu.initial_payment,
			mu.billing_amount, mu.cycle_period,
			mu.cycle_number,
			mu.billing_limit,
			mu.trial_amount,
			mu.trial_limit,
			UNIX_TIMESTAMP(mu.startdate) as startdate,
			UNIX_TIMESTAMP(mu.enddate) as enddate,
			m.name as membership,
			umf.meta_value as first_name,
			uml.meta_value as last_name
		FROM {$wpdb->users} u
		LEFT JOIN {$wpdb->usermeta} umh ON umh.meta_key = 'pmpromd_hide_directory' AND u.ID = umh.user_id
		LEFT JOIN {$wpdb->usermeta} umf ON umf.meta_key = 'first_name' AND u.ID = umf.user_id
		LEFT JOIN {$wpdb->usermeta} uml ON uml.meta_key = 'last_name' AND u.ID = uml.user_id
		LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
		LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id
		LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id
		";
		
		if ( ! empty( $extra_search_fields ) ) {
			$cnt = 1;
			
			foreach ( $extra_search_fields as $f ) {
				if ( ! empty( ${$f} ) ) {
					$sqlQuery .= "LEFT JOIN {$wpdb->usermeta} umrh_{$cnt} ON umrh_{$cnt}.meta_key = '{$f}' AND u.ID = umrh_{$cnt}.user_id
					";
				}
				++ $cnt;
			}
		}
		
		$sqlQuery .= " WHERE mu.status IN ('{$status_list}')
			AND (umh.meta_value IS NULL
				OR umh.meta_value <> '1')
				";
		
		if ( ! empty( $s ) ) {
			$sqlQuery .= " AND (u.user_login LIKE '%" . esc_sql( $s ) . "%'
				OR u.user_email LIKE '%" . esc_sql( $s ) . "%'
				OR u.display_name LIKE '%" . esc_sql( $s ) . "%'
				OR um.meta_value LIKE '%" . esc_sql( $s ) . "%') ";
		}
		
		if ( ! empty( $extra_search_fields ) ) {
			$cnt = 1;
			
			foreach ( $extra_search_fields as $f ) {
				if ( is_array( ${$f} ) && ! empty( ${$f} ) ) {
					$sqlQuery .= " AND (";
					
					$max_v = count( ${$f} ) - 1;
					$i     = 0;
					
					foreach ( ${$f} as $v ) {
						
						if ( false === $use_precise_values ) {
							$sqlQuery .= " umrh_{$cnt}.meta_value LIKE '%{$v}%' ";
						} else {
							$sqlQuery .= " umrh_{$cnt}.meta_value = '{$v}' ";
						}
						
						if ( $max_v > $i ) {
							$sqlQuery .= " OR ";
							++ $i;
						}
					}
					
					$sqlQuery .= ")
					";
				} else if ( ! empty( ${$f} ) ) {
					$sqlQuery .= " AND (";
					if ( false === $use_precise_values ) {
						$sqlQuery .= " umrh_{$cnt}.meta_value LIKE '%{${$f}}%' ";
					} else {
						$sqlQuery .= " umrh_{$cnt}.meta_value = '{${$f}}' ";
					}
					$sqlQuery .= " )
					";
				}
				
				++ $cnt;
			}
		}
		
		if ( count( $statuses ) == 1 && in_array( 'active', $statuses ) ) {
			$sqlQuery .= " AND mu.membership_id > 0";
		} else {
			$sqlQuery .= " AND mu.membership_id >= 0";
		}
		
		if ( $levels ) {
			$sqlQuery .= " AND mu.membership_id IN(" . esc_sql( $levels ) . ") ";
		}
		
		$sqlQuery .= " GROUP BY u.ID ";
		
		
	} else {
		$sqlQuery = "
		SELECT SQL_CALC_FOUND_ROWS
			DISTINCT u.ID AS ID,
			u.user_login,
			u.user_email,
			u.user_nicename,
			u.display_name,
			UNIX_TIMESTAMP(u.user_registered) as joindate,
			mu.membership_id,
			mu.initial_payment,
			mu.billing_amount,
			mu.cycle_period,
			mu.cycle_number,
			mu.billing_limit,
			mu.trial_amount,
			mu.trial_limit,
			UNIX_TIMESTAMP(mu.startdate) as startdate,
			UNIX_TIMESTAMP(mu.enddate) as enddate,
			m.name as membership,
			umf.meta_value as first_name,
			uml.meta_value as last_name
		FROM {$wpdb->users} u
		LEFT JOIN {$wpdb->usermeta} umh ON umh.meta_key = 'pmpromd_hide_directory' AND u.ID = umh.user_id
		LEFT JOIN {$wpdb->usermeta} umf ON umf.meta_key = 'first_name' AND u.ID = umf.user_id
		LEFT JOIN {$wpdb->usermeta} uml ON uml.meta_key = 'last_name' AND u.ID = uml.user_id
		LEFT JOIN {$wpdb->pmpro_memberships_users} mu ON u.ID = mu.user_id
		LEFT JOIN {$wpdb->pmpro_membership_levels} m ON mu.membership_id = m.id
		WHERE mu.status IN ('{$status_list}')
			AND (umh.meta_value IS NULL OR umh.meta_value <> '1')
			";
		
		if ( count( $statuses ) == 1 && in_array( 'active', $statuses ) ) {
			$sqlQuery .= " AND mu.membership_id > 0";
		} else {
			$sqlQuery .= " AND mu.membership_id >= 0";
		}
		
		if ( $levels ) {
			$sqlQuery .= " AND mu.membership_id IN(" . esc_sql( $levels ) . ") ";
		}
		
	}
	
	$sort_sql = " ORDER BY " . esc_sql( $order_by ) . " " . esc_sql( $order );
	$sqlQuery .= apply_filters( 'pmpro_member_directory_set_order', $sort_sql, $order_by, $order );
	
	$start = esc_sql( $start );
	$limit = esc_sql( $limit );
	
	$sqlQuery .= " LIMIT {$start}, {$limit}";
	
	$sqlQuery = apply_filters( "pmpro_member_directory_sql", $sqlQuery, $levels, $s, $pn, $limit, $start, $end, $order_by, $order );
	
	if ( WP_DEBUG ) {
		error_log( "Query for Directory search: " . $sqlQuery );
	}
	
	$theusers = $wpdb->get_results( $sqlQuery );
	
	if ( ! empty( $roles ) ) {
		
		foreach ( $theusers as $key => $user ) {
			
			$include_by_role = false;
			$the_user        = get_userdata( $user->ID );
			
			// Does the user belong to (one of) the specified role(s)?
			foreach ( $roles as $r ) {
				$include_by_role = $include_by_role || in_array( $r, $the_user->roles );
			}
			
			// Skip this user record if not
			if ( false === $include_by_role ) {
				unset( $theusers[ $key ] );
			}
		}
	}
	
	$totalrows = $wpdb->get_var( "SELECT FOUND_ROWS() AS found_rows" );
	
	//update end to match totalrows if total rows is small
	if ( $totalrows < $end ) {
		$end = $totalrows;
	}
	
	$layout_cols = preg_replace( '/[^0-9]/', '', $layout );
	
	if ( ! empty( $layout_cols ) ) {
		$theusers_chunks = array_chunk( $theusers, $layout_cols );
	} else {
		$theusers_chunks = array_chunk( $theusers, 1 );
	}
	
	// Grab the membership level for the current user
	$member_level = pmpro_hasMembershipLevel( null, $current_user->ID );
    $show_link = false;
    
	if ( true === $link && false === $members_only_link ) {
	    $show_link = true;
    } else if ( true === $link && true === $members_only_link && !empty( $member_level ) ) {
		$show_link = true;
	}
	
	//$show_link = ( true === $link || ( ( $members_only_link && ( ! empty( $member_level ) ) ) ||
	//               ( true === $link && false === $members_only_link ) ) );
	
	ob_start();
	
	?>
	<?php if ( true === $show_search ) {
		$search_string = ! empty( $_REQUEST['ps'] ) ? sanitize_text_field( $_REQUEST['ps'] ) : null;
		?>
        <form role="search"
              class="pmpro_member_directory_search search-form <?php echo apply_filters( 'pmpro_member_directory_search_class', 'locate-right' ); ?>">
            <div class="pmpro_member_directory_search_field ">
                <label>
                    <span class="screen-reader-text"><?php _e( 'Search for:', 'label' ); ?></span>
                    <input type="search" class="search-field"
                           placeholder="<?php echo apply_filters( 'pmpromd_search_placeholder_text', __( "Search Members", "pmpro-member-directory" ) ); ?>"
                           name="ps" value="<?php esc_attr_e( $search_string ); ?>"
                           title="<?php echo apply_filters( 'pmpromd_search_placeholder_text', __( "Search Members", "pmpro-member-directory" ) ); ?>"/>
                    <input type="hidden" name="limit" value="<?php echo esc_attr( $limit ); ?>"/>
                </label>
            </div>
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
            <div class="search-button clear">
                <input type="submit" class="search-submit"
                       value="<?php _e( "Search Members", "pmpro-member-directory" ); ?>">
            </div>
			<?php if ( ! empty( $search_string ) ) {
				error_log( "Something in the search string!" ); ?>
                <div class="search-button clear">
                    <a class="button button-secondary"
                       href="<?php echo esc_url( pmpro_url( 'directory' ) ); ?>"><?php _e( "Reset", "pmpro-member-directory" ); ?></a>
                </div>
			<?php } ?>

        </form>
	<?php } ?>

    <h3 id="pmpro_member_directory_subheading">
		<?php if ( ! empty( $roles ) ) { ?>
			<?php printf( __( 'Viewing All %s Profiles.', 'pmpro-member-directory' ), implode( ', ', array_map( 'ucwords', $roles ) ) ); ?>
		<?php } else if ( ! empty( $s ) ) { ?>
			<?php printf( __( 'Profiles Within <em>%s</em>.', 'pmpro-member-directory' ), ucwords( esc_html( $s ) ) ); ?>
		<?php } else { ?>
			<?php _e( 'Viewing All Profiles.', 'pmpro-member-directory' ); ?>
		<?php } ?>
		<?php /* if ( $totalrows > 0 ) { ?>
            <small class="muted">
                (<?php
				if ( $totalrows == 1 ) {
					printf( __( 'Showing 1 Result', 'pmpro-member-directory' ), $start + 1, $end, $totalrows );
				} else {
					printf( __( 'Showing %s-%s of %s Results', 'pmpro-member-directory' ), $start + 1, $end, $totalrows );
				}
				?>)
            </small>
		<?php } */ ?>
    </h3>
	<?php
	if ( ! empty( $theusers ) ) {
		if ( ! empty( $fields ) ) {
			$fields_array = explode( ";", $fields );
			if ( ! empty( $fields_array ) ) {
				for ( $i = 0; $i < count( $fields_array ); $i ++ ) {
					$fields_array[ $i ] = explode( ",", trim( $fields_array[ $i ] ) );
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
        <div class="pmpro_member_directory">
            <hr class="clear"/>
			<?php
			if ( $layout == "table" ) {
				?>
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <thead>
					<?php if ( true === $show_avatar ) { ?>
                        <th class="pmpro_member_directory_avatar">
							<?php _e( 'Avatar', 'paid-memberships-pro' ); ?>
                        </th>
					<?php } ?>
                    <th class="pmpro_member_directory_display-name">
						<?php _e( 'Member', 'paid-memberships-pro' ); ?>
                    </th>
					<?php if ( true === $show_email ) { ?>
                        <th class="pmpro_member_directory_email">
							<?php _e( 'Email Address', 'paid-memberships-pro' ); ?>
                        </th>
					<?php } ?>
					<?php if ( ! empty( $fields_array ) ) { ?>
                        <th class="pmpro_member_directory_additional">
							<?php _e( 'More Information', 'paid-memberships-pro' ); ?>
                        </th>
					<?php } ?>
					<?php if ( true === $show_level ) { ?>
                        <th class="pmpro_member_directory_level">
							<?php _e( 'Level', 'paid-memberships-pro' ); ?>
                        </th>
					<?php } ?>
					<?php if ( true === $show_startdate ) { ?>
                        <th class="pmpro_member_directory_date">
							<?php _e( 'Start Date', 'paid-memberships-pro' ); ?>
                        </th>
					<?php } ?>
					<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                        <th class="pmpro_member_directory_link">&nbsp;</th>
					<?php } ?>
                    </thead>
                    <tbody>
					<?php
					$count = 0;
					foreach ( $theusers as $the_user ) {
						$the_user                   = get_userdata( $the_user->ID );
						$the_user->membership_level = pmpro_getMembershipLevelForUser( $the_user->ID );
						$count ++;
						
						if ( ! empty( $profile_url ) ) {
						    
						    if ( true === $editable_profile && is_user_logged_in() && $current_user->ID === $the_user->ID ) {
						        $profile_url = get_edit_user_link( $the_user->ID );
						        $read_only_profile = get_permalink( $pmpro_pages['profile'] );
                            } else {
                                $profile_url = get_permalink( $pmpro_pages['profile'] );
						    }
						}
						?>
                        <tr id="pmpro_member_directory_row-<?php esc_attr_e( $the_user->ID ); ?>"
                            class="pmpro_member_directory_row<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) {
							    echo " pmpro_member_directory_linked";
						    } ?>">
							<?php if ( true === $show_avatar ) { ?>
                                <td class="pmpro_member_directory_avatar">
									<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                                        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php echo get_avatar( $the_user->ID, $avatar_size ); ?></a>
									<?php } else { ?>
										<?php echo get_avatar( $the_user->ID, $avatar_size ); ?>
									<?php } ?>
                                </td>
							<?php } ?>
                            <td>
                                <h3 class="pmpro_member_directory_display-name">
									<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                                        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php esc_html_e( $the_user->display_name ); ?></a>
									<?php } else { ?>
										<?php esc_html_e( $the_user->display_name ); ?>
									<?php } ?>
                                </h3>
                            </td>
							<?php if ( true === $show_email ) { ?>
                                <td class="pmpro_member_directory_email">
									<?php esc_html_e( $the_user->user_email ); ?>
                                </td>
							<?php } ?>
							<?php
							if ( ! empty( $fields_array ) ) {
								?>
                                <td class="pmpro_member_directory_additional">
									<?php
									foreach ( $fields_array as $field ) {
										$meta_field = wp_unslash( $the_user->{$field[1]} );
										if ( ! empty( $meta_field ) ) {
											?>
                                            <p class="pmpro_member_directory_<?php esc_attr_e( $field[1] ); ?>">
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
                                                    <strong><?php esc_html_e( $field[0] ); ?></strong>
													<?php echo implode( ", ", $meta_field ); ?>
													<?php
												} else {
													if ( $field[1] == 'user_url' ) {
														?>
                                                        <a href="<?php echo esc_url( $meta_field ); ?>"
                                                           target="_blank"><?php esc_html_e( $field[0] ); ?></a>
														<?php
													} else {
                                                        ?>
                                                        <strong><?php esc_html_e( $field[0] ); ?></strong>
														<?php
														$meta_field_embed = wp_oembed_get( $meta_field );
														if ( ! empty( $meta_field_embed ) ) {
															echo wp_unslash( $meta_field_embed );
														} else {
															echo make_clickable( wp_unslash( $meta_field ) );
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
									?>
                                </td>
								<?php
							}
							?>
							<?php if ( true === $show_level ) { ?>
                                <td class="pmpro_member_directory_level">
									<?php esc_html_e( $the_user->membership_level->name ); ?>
                                </td>
							<?php } ?>
							<?php if ( true === $show_startdate ) { ?>
                                <td class="pmpro_member_directory_date">
									<?php echo date_i18n( get_option( "date_format" ), $the_user->membership_level->startdate ); ?>
                                </td>
							<?php } ?>
							<?php if ( true === $show_link && true === $link && ! empty( $profile_url ) ) { ?>
                                <td class="pmpro_member_directory_link">
                                    <?php if ( empty( $read_only_profile ) ) { ?>
                                    <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php _e( 'View Profile', 'pmpro-member-directory' ); ?></a>
                                <?php } else { ?>
                                    <span>
								        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php _e( 'Edit', 'pmpro-member-directory' ); ?></a> <?php _e( 'or', 'pmpro-member-directory'); ?> <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $read_only_profile ) ); ?>"><?php _e( 'View', 'pmpro-member-directory' ); ?></a> <?php _e("Profile", "pmpro-member-directory" ); ?>
                                        </span>
                                   <?php } ?>
                                </td>
							<?php } ?>
                        </tr>
						<?php
					}
					?>
                    </tbody>
                </table>
				<?php
			} else {
				$count = 0;
				foreach ( $theusers_chunks as $row ): ?>
                    <div class="row">
						<?php
						foreach ( $row as $the_user ) {
							$count ++;
							$the_user                   = get_userdata( $the_user->ID );
							$the_user->membership_level = pmpro_getMembershipLevelForUser( $the_user->ID );
							
							if ( ! empty( $profile_url ) ) {
								if ( true === $editable_profile && is_user_logged_in() && $current_user->ID === $the_user->ID ) {
									$profile_url = get_edit_user_link( $the_user->ID );
									$read_only_profile = get_permalink( $pmpro_pages['profile'] );
								} else {
									$profile_url = get_permalink( $pmpro_pages['profile'] );
								}
							}
							
							?>
                            <div class="medium-<?php
							if ( $layout == '2col' ) {
								$avatar_align = "alignright";
								echo '6 ';
							} else if ( $layout == '3col' ) {
								$avatar_align = "aligncenter";
								echo '4 text-center ';
							} else if ( $layout == '4col' ) {
								$avatar_align = "aligncenter";
								echo '3 text-center ';
							} else {
								$avatar_align = "alignright";
								echo '12 ';
							}
							if ( $count == $end ) {
								echo 'end ';
							}
							?>
								columns">
                                <div id="pmpro_member-<?php echo esc_attr( $the_user->ID ); ?>">
									<?php if ( true === $show_avatar ) { ?>
                                        <div class="pmpro_member_directory_avatar">
											<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                                                <a class="<?php echo esc_attr( $avatar_align ); ?>"
                                                   href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php echo get_avatar( $the_user->ID, $avatar_size, null, $the_user->display_name ); ?></a>
											<?php } else { ?>
                                                <span
                                                        class="<?php echo esc_attr( $avatar_align ); ?>"><?php echo get_avatar( $the_user->ID, $avatar_size, null, $the_user->display_name ); ?></span>
											<?php } ?>
                                        </div>
									<?php } ?>
                                    <h3 class="pmpro_member_directory_display-name">
										<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                                            <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php echo $the_user->display_name; ?></a>
										<?php } else { ?>
											<?php echo esc_attr( $the_user->display_name ); ?>
										<?php } ?>
                                    </h3>
									<?php if ( true === $show_email ) { ?>
                                        <p class="pmpro_member_directory_email">
                                            <strong><?php _e( 'Email Address', 'pmpro' ); ?></strong>
											<?php echo esc_attr( $the_user->user_email ); ?>
                                        </p>
									<?php } ?>
									<?php if ( true === $show_level ) { ?>
                                        <p class="pmpro_member_directory_level">
                                            <strong><?php _e( 'Level', 'pmpro' ); ?></strong>
											<?php echo esc_attr( $the_user->membership_level->name ); ?>
                                        </p>
									<?php } ?>
									<?php if ( true === $show_startdate ) { ?>
                                        <p class="pmpro_member_directory_date">
                                            <strong><?php _e( 'Start Date', 'pmpro' ); ?></strong>
											<?php echo date( get_option( "date_format" ), $the_user->membership_level->startdate ); ?>
                                        </p>
									<?php } ?>
									<?php
									// Save a copy of the extracted fields (for the pmproemd_add_extra_directory_output action)
									$real_fields_array = $fields_array;
									//filter the fields
									$fields_array = apply_filters( 'pmpro_member_profile_fields', $fields_array, $the_user );
									if ( ! empty( $fields_array ) ) {
										foreach ( $fields_array as $field ) {
											$meta_field = wp_unslash( $the_user->{$field[1]} );
											if ( ! empty( $meta_field ) ) {
												?>
                                                <p class="pmpro_member_directory_<?php echo esc_attr( $field[1] ); ?>">
													<?php
													if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
														//this is a file field
														?>
                                                        <strong><?php echo esc_attr( $field[0] ); ?></strong>
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
                                                        <strong><?php echo esc_attr( $field[0] ); ?></strong>
														<?php echo implode( ", ", $meta_field ); ?>
														<?php
													} else if ( $field[1] == 'user_url' ) {
														?>
                                                        <a href="<?php echo esc_attr( $the_user->{$field[1]} ); ?>"
                                                           target="_blank"><?php echo esc_attr( $field[0] ); ?></a>
														<?php
													} else {
														?>
                                                        <strong><?php echo esc_attr( $field[0] ); ?>:</strong>
														<?php echo make_clickable( $the_user->{$field[1]} ); ?>
														<?php
													}
													?>
                                                </p>
												<?php
											}
										}
									}
									
									do_action( 'pmproemd_add_extra_directory_output', $real_fields_array, $the_user );
									?>
									<?php if ( true === $show_link && true === $link && ! empty( $pmpro_pages['profile'] ) ) { ?>
                                        <p class="pmpro_member_directory_link">
	                                        <?php if ( empty( $read_only_profile ) ) { ?>
                                                <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php _e( 'View Profile', 'pmpro-member-directory' ); ?></a>
	                                        <?php } else { ?>
                                                <span>
								        <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $profile_url ) ); ?>"><?php _e( 'Edit', 'pmpro-member-directory' ); ?></a> <?php _e( 'or', 'pmpro-member-directory'); ?> <a href="<?php echo esc_url( add_query_arg( 'pu', $the_user->user_nicename, $read_only_profile ) ); ?>"><?php _e( 'View', 'pmpro-member-directory' ); ?></a> <?php _e("Profile", "pmpro-member-directory" ); ?>
                                        </span>
	                                        <?php } ?>
                                        </p>
									<?php } ?>
                                </div> <!-- end pmpro_addon_package-->
                            </div>
							<?php
						}
						?>
                    </div> <!-- end row -->
                    <hr/>
					<?php
				endforeach;
			}
			?>
        </div> <!-- end pmpro_member_directory -->
		<?php
	} else {
		?>
        <p class="pmpro_member_directory_message pmpro_message pmpro_error">
			<?php _e( 'No matching profiles found', 'pmpro-member-directory' ); ?>
			<?php
			if ( ! empty( $s ) ) {
				printf( __( 'within <em>%s</em>.', 'pmpro-member-directory' ), ucwords( esc_html( $s ) ) );
				if ( ! empty( $directory_url ) ) {
					?>
                    <a class="more-link"
                       href="<?php echo esc_url_raw( $directory_url ); ?>"><?php _e( 'View All Members', 'pmpro-member-directory' ); ?></a>
					<?php
				}
			} else {
				echo ".";
			}
			?>
        </p>
		<?php
	}
	
	//prev/next
	?>
    <div class="pmpro_pagination">
		<?php
        
        // Configure the basics of the Pagination arguments
        $pn_args = array(
	        "ps"    => $s,
	        "limit" => $limit,
        );
		
		// Link to previous page
        if ( $pn > 1 ) {
            
            // Decrement the page counter by 1
	        $pn_args['pn'] = $pn - 1; ?>
            <span class="pmpro_prev"><a href="<?php echo esc_url( add_query_arg( pmproemd_pagination_args( $pn_args ), get_permalink( $post->ID ) ) ); ?>">&laquo; <?php _e( "Previous", "pmpro-member-directory" ); ?></a></span>
			<?php
		}
		
		// Link to next page
		if ( $totalrows > $end ) {
   
			// Increment the page counter by 1
			$pn_args['pn'] = $pn + 1; ?>
            <span class="pmpro_next"><a href="<?php echo esc_url( add_query_arg( pmproemd_pagination_args( $pn_args ), get_permalink( $post->ID ) ) ); ?>"> <?php _e( "Next", "pmpro-member-directory" ); ?>
                    &raquo;</a></span>
			<?php
		}
		?>
    </div>
	<?php
	?>
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();
	
	return $temp_content;
}

add_shortcode( "pmpro_member_directory", "pmproemd_shortcode" );
