<?php
/*
	This shortcode will display the members list and additional content based on the defined attributes.
*/
function pmpromd_shortcode( $atts, $content = null, $code = "" ) {
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_member_directory show_avatar="false" show_email="false" levels="1,2"]

	// init vars
	$avatar_size    = '128';
	$fields         = null;
	$layout         = 'div';
	$level          = null;
	$levels         = null;
	$limit          = null;
	$link           = null;
	$order_by       = 'u.display_name';
	$order          = 'ASC';
	$show_avatar    = null;
	$show_email     = null;
	$show_level     = null;
	$show_search    = null;
	$show_startdate = null;
	$limit_to       = null;

	extract( shortcode_atts( array(
		'avatar_size'    => '128',
		'fields'         => null,
		'layout'         => 'div',
		'level'          => null,
		'levels'         => null,
		'limit'          => null,
		'link'           => null,
		'order_by'       => 'u.display_name',
		'order'          => 'ASC',
		'show_avatar'    => null,
		'show_email'     => null,
		'show_level'     => null,
		'show_search'    => null,
		'show_startdate' => null,
		'limit_to'       => null,
	), $atts ) );

	global $wpdb, $post, $pmpro_pages, $pmprorh_registration_fields;

	//some page vars
	if ( ! empty( $pmpro_pages['directory'] ) ) {
		$directory_url = get_permalink( $pmpro_pages['directory'] );
	}

	if ( ! empty( $pmpro_pages['profile'] ) ) {
		$profile_url = get_permalink( $pmpro_pages['profile'] );
	}

	//turn 0's into falses
	if ( $link === "0" || $link === "false" || $link === "no" ) {
		$link = false;
	} else {
		$link = true;
	}

	//did they use level instead of levels?
	if ( empty( $levels ) && ! empty( $level ) ) {
		$levels = $level;
	}

	if ( $show_avatar === "0" || $show_avatar === "false" || $show_avatar === "no" ) {
		$show_avatar = false;
	} else {
		$show_avatar = true;
	}

	if ( $show_email === "0" || $show_email === "false" || $show_email === "no" ) {
		$show_email = false;
	} else {
		$show_email = true;
	}

	if ( $show_level === "0" || $show_level === "false" || $show_level === "no" ) {
		$show_level = false;
	} else {
		$show_level = true;
	}

	if ( $show_search === "0" || $show_search === "false" || $show_search === "no" ) {
		$show_search = false;
	} else {
		$show_search = true;
	}

	if ( $show_startdate === "0" || $show_startdate === "false" || $show_startdate === "no" ) {
		$show_startdate = false;
	} else {
		$show_startdate = true;
	}

	if ( $limit_to === "0" || $limit_to === "false" || $limit_to === "no" ) {
		$limit_to = false;
	} else {
		$limit_to = true;
	}

	ob_start();

	if ( isset( $_REQUEST['ps'] ) ) {
		$s = $_REQUEST['ps'];
	} else {
		$s = "";
	}

	if ( isset( $_REQUEST['pn'] ) ) {
		$pn = intval( $_REQUEST['pn'] );
	} else {
		$pn = 1;
	}

	if ( isset( $_REQUEST['limit'] ) ) {
		$limit = intval( $_REQUEST['limit'] );
	} elseif ( empty( $limit ) ) {
		$limit = 15;
	}

	/*
	 * Add support for user defined search fields & tables (array value = usermeta field name)
	 * Can be array of field names (usermeta fields)
	 */
	$extra_search_fields = apply_filters( 'pmpromd_extra_search_fields', array() );

	if ( ! empty( $extra_search_fields ) && ! is_array( $extra_search_fields ) ) {
		$extra_search_fields = array( $extra_search_fields );
	}

	if ( ! empty( $extra_search_fields ) ) {

		foreach ( $extra_search_fields as $field_name ) {
			if ( isset( $_REQUEST[ $field_name ] ) && is_array( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = array_map( 'sanitize_text_field', $_REQUEST[ $field_name ] );
			} elseif ( isset( $_REQUEST[ $field_name ] ) && ! is_array( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = sanitize_text_field( $_REQUEST[ $field_name ] );
			} elseif ( empty( $_REQUEST[ $field_name ] ) ) {
				${$field_name} = null;
			}
		}
	}
	$end   = $pn * $limit;
	$start = $end - $limit;

	$statuses    = apply_filters( 'pmprod_membership_statuses', array( 'active' ) );
	$status_list = esc_sql( implode( "', '", $statuses ) );

	if ( ! empty( $s ) || ! empty( $extra_search_fields ) ) {
		$sqlQuery = "
		SELECT SQL_CALC_FOUND_ROWS
			u.ID,
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

						$sqlQuery .= " umrh_{$cnt}.meta_value LIKE '%{$v}%' ";

						if ( $max_v > $i ) {
							$sqlQuery .= " OR ";
							++$i;
						}
					}

					$sqlQuery .= ")
					";
				} elseif ( ! empty( ${$f} ) ) {
					$sqlQuery .= " AND (";
					$sqlQuery .= " umrh_{$cnt}.meta_value LIKE '%{${$f}}%' ";
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

		$sort_sql = " ORDER BY " . esc_sql( $order_by ) . " " . esc_sql( $order );
		$sqlQuery .= apply_filters( 'pmpro_member_directory_set_order', $sort_sql, $order_by, $order );

	} else {
		$sqlQuery = "
		SELECT SQL_CALC_FOUND_ROWS
			DISTINCT u.ID,
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

		$sort_sql = " ORDER BY " . esc_sql( $order_by ) . " " . esc_sql( $order );
		$sqlQuery .= apply_filters( 'pmpro_member_directory_set_order', $sort_sql, $order_by, $order );
	}

	$sqlQuery .= " LIMIT $start, $limit";

	$sqlQuery = apply_filters( "pmpro_member_directory_sql", $sqlQuery, $levels, $s, $pn, $limit, $start, $end );

	if ( WP_DEBUG ) {
		error_log( "Query for Directory search: " . $sqlQuery );
	}

	$theusers  = $wpdb->get_results( $sqlQuery );
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

	ob_start();

	?>
	<?php if ( ! empty( $show_search ) ) { ?>
		<form role="search" class="pmpro_member_directory_search search-form">
			<label>
				<span class="screen-reader-text"><?php _e( 'Search for:', 'label' ); ?></span>
				<input type="search" class="search-field" placeholder="Search Members" name="ps"
				       value="<?php if ( ! empty( $_REQUEST['ps'] ) ) {
					       echo esc_attr( $_REQUEST['ps'] );
				       } ?>" title="Search Members"/>
				<input type="hidden" name="limit" value="<?php echo esc_attr( $limit ); ?>"/>
			</label>
			<?php
			$field_array = apply_filters( 'pmpro_member_directory_extra_search_input', array() );

			foreach ( $field_array as $field ) {
				echo $field;
			}
			?>
			<div class="search-button clear">
				<input type="submit" class="search-submit" value="<?php _e( "Search Members", "pmpromd" ); ?>">
			</div>
		</form>
	<?php } ?>

	<h3 id="pmpro_member_directory_subheading">
		<?php if ( ! empty( $s ) ) { ?>
			<?php printf( __( 'Profiles Within <em>%s</em>.', 'pmpromd' ), ucwords( esc_html( $s ) ) ); ?>
		<?php } else { ?>
			<?php _e( 'Viewing All Profiles.', 'pmpromd' ); ?>
		<?php } ?>
		<?php if ( $totalrows > 0 ) { ?>
			<small class="muted">
				(<?php
				if ( $totalrows == 1 ) {
					printf( __( 'Showing 1 Result', 'pmpromd' ), $start + 1, $end, $totalrows );
				} else {
					printf( __( 'Showing %s-%s of %s Results', 'pmpromd' ), $start + 1, $end, $totalrows );
				}
				?>)
			</small>
		<?php } ?>
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
					<?php if ( ! empty( $show_avatar ) ) { ?>
						<th class="pmpro_member_directory_avatar">
							<?php _e( 'Avatar', 'pmpro' ); ?>
						</th>
					<?php } ?>
					<th class="pmpro_member_directory_display-name">
						<?php _e( 'Member', 'pmpro' ); ?>
					</th>
					<?php if ( ! empty( $show_email ) ) { ?>
						<th class="pmpro_member_directory_email">
							<?php _e( 'Email Address', 'pmpro' ); ?>
						</th>
					<?php } ?>
					<?php if ( ! empty( $fields_array ) ) { ?>
						<th class="pmpro_member_directory_additional">
							<?php _e( 'More Information', 'pmpro' ); ?>
						</th>
					<?php } ?>
					<?php if ( ! empty( $show_level ) ) { ?>
						<th class="pmpro_member_directory_level">
							<?php _e( 'Level', 'pmpro' ); ?>
						</th>
					<?php } ?>
					<?php if ( ! empty( $show_startdate ) ) { ?>
						<th class="pmpro_member_directory_date">
							<?php _e( 'Start Date', 'pmpro' ); ?>
						</th>
					<?php } ?>
					<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
						<th class="pmpro_member_directory_link">&nbsp;</th>
					<?php } ?>
					</thead>
					<tbody>
					<?php
					$count = 0;
					foreach ( $theusers as $auser ) {
						$auser                   = get_userdata( $auser->ID );
						$auser->membership_level = pmpro_getMembershipLevelForUser( $auser->ID );
						$count ++;
						?>
						<tr id="pmpro_member_directory_row-<?php echo $auser->ID; ?>"
						    class="pmpro_member_directory_row<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) {
							    echo " pmpro_member_directory_linked";
						    } ?>">
							<?php if ( ! empty( $show_avatar ) ) { ?>
								<td class="pmpro_member_directory_avatar">
									<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
										<a href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php echo get_avatar( $auser->ID, $avatar_size ); ?></a>
									<?php } else { ?>
										<?php echo get_avatar( $auser->ID, $avatar_size ); ?>
									<?php } ?>
								</td>
							<?php } ?>
							<td>
								<h3 class="pmpro_member_directory_display-name">
									<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
										<a href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php echo $auser->display_name; ?></a>
									<?php } else { ?>
										<?php echo $auser->display_name; ?>
									<?php } ?>
								</h3>
							</td>
							<?php if ( ! empty( $show_email ) ) { ?>
								<td class="pmpro_member_directory_email">
									<?php echo $auser->user_email; ?>
								</td>
							<?php } ?>
							<?php
							if ( ! empty( $fields_array ) ) {
								?>
								<td class="pmpro_member_directory_additional">
									<?php
									foreach ( $fields_array as $field ) {
										$meta_field = $auser->$field[1];
										if ( ! empty( $meta_field ) ) {
											?>
											<p class="pmpro_member_directory_<?php echo $field[1]; ?>">
												<?php
												if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
													//this is a file field
													?>
													<strong><?php echo $field[0]; ?></strong>
													<?php echo pmpromd_display_file_field( $meta_field ); ?>
													<?php
												} elseif ( is_array( $meta_field ) ) {
													//this is a general array, check for Register Helper options first
													if ( ! empty( $rh_fields[ $field[1] ] ) ) {
														foreach ( $meta_field as $key => $value ) {
															$meta_field[ $key ] = $rh_fields[ $field[1] ][ $value ];
														}
													}
													?>
													<strong><?php echo $field[0]; ?></strong>
													<?php echo implode( ", ", $meta_field ); ?>
													<?php
												} else {
													if ( $field[1] == 'user_url' ) {
														?>
														<a href="<?php echo esc_url( $meta_field ); ?>"
														   target="_blank"><?php echo $field[0]; ?></a>
														<?php
													} else {
														?>
														<strong><?php echo $field[0]; ?></strong>
														<?php
														$meta_field_embed = wp_oembed_get( $meta_field );
														if ( ! empty( $meta_field_embed ) ) {
															echo $meta_field_embed;
														} else {
															echo make_clickable( $meta_field );
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
							<?php if ( ! empty( $show_level ) ) { ?>
								<td class="pmpro_member_directory_level">
									<?php echo $auser->membership_level->name; ?>
								</td>
							<?php } ?>
							<?php if ( ! empty( $show_startdate ) ) { ?>
								<td class="pmpro_member_directory_date">
									<?php echo date( get_option( "date_format" ), $auser->membership_level->startdate ); ?>
								</td>
							<?php } ?>
							<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
								<td class="pmpro_member_directory_link">
									<a href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php _e( 'View Profile', 'pmpromd' ); ?></a>
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
						foreach ( $row as $auser ) {
							$count ++;
							$auser                   = get_userdata( $auser->ID );
							$auser->membership_level = pmpro_getMembershipLevelForUser( $auser->ID );
							?>
							<div class="medium-<?php
							if ( $layout == '2col' ) {
								$avatar_align = "alignright";
								echo '6 ';
							} elseif ( $layout == '3col' ) {
								$avatar_align = "aligncenter";
								echo '4 text-center ';
							} elseif ( $layout == '4col' ) {
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
								<div id="pmpro_member-<?php echo $auser->ID; ?>">
									<?php if ( ! empty( $show_avatar ) ) { ?>
										<div class="pmpro_member_directory_avatar">
											<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
												<a class="<?php echo $avatar_align; ?>"
												   href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php echo get_avatar( $auser->ID, $avatar_size, null, $auser->display_name ); ?></a>
											<?php } else { ?>
												<span
													class="<?php echo $avatar_align; ?>"><?php echo get_avatar( $auser->ID, $avatar_size, null, $auser->display_name ); ?></span>
											<?php } ?>
										</div>
									<?php } ?>
									<h3 class="pmpro_member_directory_display-name">
										<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
											<a href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php echo $auser->display_name; ?></a>
										<?php } else { ?>
											<?php echo $auser->display_name; ?>
										<?php } ?>
									</h3>
									<?php if ( ! empty( $show_email ) ) { ?>
										<p class="pmpro_member_directory_email">
											<strong><?php _e( 'Email Address', 'pmpro' ); ?></strong>
											<?php echo $auser->user_email; ?>
										</p>
									<?php } ?>
									<?php if ( ! empty( $show_level ) ) { ?>
										<p class="pmpro_member_directory_level">
											<strong><?php _e( 'Level', 'pmpro' ); ?></strong>
											<?php echo $auser->membership_level->name; ?>
										</p>
									<?php } ?>
									<?php if ( ! empty( $show_startdate ) ) { ?>
										<p class="pmpro_member_directory_date">
											<strong><?php _e( 'Start Date', 'pmpro' ); ?></strong>
											<?php echo date( get_option( "date_format" ), $auser->membership_level->startdate ); ?>
										</p>
									<?php } ?>
									<?php
									if ( ! empty( $fields_array ) ) {
										foreach ( $fields_array as $field ) {
											$meta_field = $auser->$field[1];
											if ( ! empty( $meta_field ) ) {
												?>
												<p class="pmpro_member_directory_<?php echo $field[1]; ?>">
													<?php
													if ( is_array( $meta_field ) && ! empty( $meta_field['filename'] ) ) {
														//this is a file field
														?>
														<strong><?php echo $field[0]; ?></strong>
														<?php echo pmpromd_display_file_field( $meta_field ); ?>
														<?php
													} elseif ( is_array( $meta_field ) ) {
														//this is a general array, check for Register Helper options first
														if ( ! empty( $rh_fields[ $field[1] ] ) ) {
															foreach ( $meta_field as $key => $value ) {
																$meta_field[ $key ] = $rh_fields[ $field[1] ][ $value ];
															}
														}
														?>
														<strong><?php echo $field[0]; ?></strong>
														<?php echo implode( ", ", $meta_field ); ?>
														<?php
													} elseif ( $field[1] == 'user_url' ) {
														?>
														<a href="<?php echo $auser->$field[1]; ?>"
														   target="_blank"><?php echo $field[0]; ?></a>
														<?php
													} else {
														?>
														<strong><?php echo $field[0]; ?>:</strong>
														<?php echo make_clickable( $auser->$field[1] ); ?>
														<?php
													}
													?>
												</p>
												<?php
											}
										}
									}
									?>
									<?php if ( ! empty( $link ) && ! empty( $profile_url ) ) { ?>
										<p class="pmpro_member_directory_link">
											<a class="more-link"
											   href="<?php echo add_query_arg( 'pu', $auser->user_nicename, $profile_url ); ?>"><?php _e( 'View Profile', 'pmpromd' ); ?></a>
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
			<?php _e( 'No matching profiles found', 'pmpromd' ); ?>
			<?php
			if ( $s ) {
				printf( __( 'within <em>%s</em>.', 'pmpromd' ), ucwords( esc_html( $s ) ) );
				if ( ! empty( $directory_url ) ) {
					?>
					<a class="more-link"
					   href="<?php echo $directory_url; ?>"><?php _e( 'View All Members', 'pmpromd' ); ?></a>
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
		//prev
		if ( $pn > 1 ) {
			?>
			<span class="pmpro_prev"><a href="<?php echo esc_url( add_query_arg( array(
					"ps"    => $s,
					"pn"    => $pn - 1,
					"limit" => $limit
				), get_permalink( $post->ID ) ) ); ?>">&laquo; Previous</a></span>
			<?php
		}
		//next
		if ( $totalrows > $end ) {
			?>
			<span class="pmpro_next"><a href="<?php echo esc_url( add_query_arg( array(
					"ps"    => $s,
					"pn"    => $pn + 1,
					"limit" => $limit
				), get_permalink( $post->ID ) ) ); ?>">Next &raquo;</a></span>
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

add_shortcode( "pmpro_member_directory", "pmpromd_shortcode" );
