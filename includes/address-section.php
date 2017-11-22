<?php
/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

error_log("Loading address section display code");

global $pmproemd_has_billing_fields;
global $pmproemd_has_shipping_fields;
global $pmproemd_show_billing_address;
global $pmproemd_show_shipping_address;

function pmproemd_remove_address_info( $fields, $user ) {
	
	global $pmproemd_has_billing_fields;
	global $pmproemd_has_shipping_fields;
	global $pmproemd_show_billing_address;
	global $pmproemd_show_shipping_address;
	
	
	if ( false === $pmproemd_show_billing_address && false === $pmproemd_show_shipping_address ) {
		error_log( "Not expecting there to be any fields to process!");
		return $fields;
	}
	
	$search = "(firstname|lastname|address\d|city|state|country|phone|email)";
	
	foreach( $fields as $key => $field_def ) {
		
		$label = $field_def[0];
		$value = $field_def[1];
		
		// Find any configured billing info fields
		if ( 1 === preg_match( "/^pmpro_b{$search}/", $value ) ) {
			
			if ( empty( $pmproemd_has_billing_fields ) ) {
				$pmproemd_has_billing_fields = array();
			}
			
			$pmproemd_has_billing_fields[$value] = $label;
			unset( $fields[$key] );
		}
		
		// Find any configured shipping info fields
		if ( 1 === preg_match( "/^pmpro_s{$search}/", $value ) ) {
			
			if ( empty( $pmproemd_has_shipping_fields ) ) {
				$pmproemd_has_shipping_fields = array();
			}
			//
			$pmproemd_has_shipping_fields[$value] = $label;
			unset( $fields[$key] );
		}
	}
	
	// Send the info back
	return $fields;
}

add_filter( 'pmpro_member_profile_fields', 'pmproemd_remove_address_info', 99, 2 );

function pmproemd_add_address_section( $fields, $user ) {
	
	global $pmproemd_has_billing_fields;
	global $pmproemd_has_shipping_fields;
	global $pmproemd_show_billing_address;
	global $pmproemd_show_shipping_address;
	
	$b_heading = apply_filters( 'pmpro-member-profile-billing-header',__("Billing Address", "pmpro-member-directory" ) );
	$s_heading = apply_filters( 'pmpro-member-profile-shipping-header', __( "Shipping Address", "pmpro-member-directory" ) );
	
	// Nothing to show!
	if ( ( false === $pmproemd_show_billing_address && false === $pmproemd_show_shipping_address )  ||
	     ( empty( $pmproemd_has_billing_fields ) && empty( $pmproemd_has_shipping_fields ) ) )
	{
		return;
	}
 
	?>
	<div class="pmproemd_address_section">
	<?php if ( true === $pmproemd_show_billing_address && !empty( $pmproemd_has_billing_fields )): ?>
		<!-- Billing address -->
		<div class="pmpro_member_directory_billing_address">
			<h3 class="pmproemd_billing_address_heading"><?php esc_attr_e( $b_heading ); ?></h3>
			<div class="pmproemd-billing-address">
		<?php if ( isset( $pmproemd_has_billing_fields['pmpro_baddress1'] ) ) {
			if ( isset( $pmproemd_has_billing_fields['pmpro_bfirstname'] ) ) { ?>
				<div
					class="pmproemd-address"><?php printf( '<label>%s</label><span>%s %s</span>', __( 'Name', 'pmpro-membership-directory' ), esc_attr( $user->pmpro_bfirstname ), esc_attr( $user->pmpro_blastname ) ); ?></div>
				<?php
			} ?>
			<div
				class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_baddress1'] ), esc_attr( $user->pmpro_baddress1 ) ); ?></div>
			<?php
			if ( isset( $pmproemd_has_billing_fields['pmpro_baddress2'] ) ) {
				?>
				<div
					class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_baddress2'] ), esc_attr( $user->pmpro_baddress2 ) ); ?></div>
				<?php
			}
			if ( isset( $pmproemd_has_billing_fields['pmpro_bcity'] ) ) {
				?>
				<div class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_bcity'] ), esc_attr( $user->pmpro_bcity ) ); ?></div>
				<?php
			}
			if ( isset( $pmproemd_has_billing_fields['pmpro_bstate'] ) ) {
				?>
				<div
					class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_bstate'] ), esc_attr( $user->pmpro_bstate ) ); ?></div>
				<?php
			}
			if ( isset( $pmproemd_has_billing_fields['pmpro_bzipcode'] ) ) {
				?>
				<div
					class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_bzipcode'] ), esc_attr( $user->pmpro_bzipcode ) ); ?></div>
				<?php
			}
			if ( isset( $pmproemd_has_billing_fields['pmpro_bcountry'] ) ) {
				?>
				<div
					class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_billing_fields['pmpro_bcountry'] ), esc_attr( $user->pmpro_bcountry ) ); ?></div>
				<?php
			}
		} ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( true === $pmproemd_show_shipping_address && !empty( $pmproemd_has_shipping_fields )): ?>
		<!-- Shipping address -->
		<div class="pmpro_member_directory_shipping_address">
			<h3 class="pmproemd_shipping_address_heading"><?php esc_attr_e( $s_heading ); ?></h3>
			<div class="pmproemd-shipping-address">
				<?php if ( isset( $pmproemd_has_shipping_fields['pmpro_saddress1'] ) ) {
					if ( isset( $pmproemd_has_shipping_fields['pmpro_sfirstname'] ) ) { ?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s %s</span>', __( 'Name', 'pmpro-membership-directory' ), esc_attr( $user->pmpro_sfirstname ), esc_attr( $user->pmpro_slastname ) ); ?></div>
						<?php
					} ?>
					<div
						class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_saddress1'] ), esc_attr( $user->pmpro_saddress1 ) ); ?></div>
					<?php
					if ( isset( $pmproemd_has_shipping_fields['pmpro_saddress2'] ) ) {
						?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_saddress2'] ), esc_attr( $user->pmpro_saddress2 ) ); ?></div>
						<?php
					}
					if ( isset( $pmproemd_has_shipping_fields['pmpro_scity'] ) ) {
						?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_scity'] ), esc_attr( $user->pmpro_scity ) ); ?></div>
						<?php
					}
					if ( isset( $pmproemd_has_shipping_fields['pmpro_sstate'] ) ) {
						?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_sstate'] ), esc_attr( $user->pmpro_sstate ) ); ?></div>
						<?php
					}
					if ( isset( $pmproemd_has_shipping_fields['pmpro_szipcode'] ) ) {
						?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_szipcode'] ), esc_attr( $user->pmpro_szipcode ) ); ?></div>
						<?php
					}
					if ( isset( $pmproemd_has_shipping_fields['pmpro_scountry'] ) ) {
						?>
						<div
							class="pmproemd-address"><?php printf( '<label>%s</label><span>%s</span>', esc_attr( $pmproemd_has_shipping_fields['pmpro_scountry'] ), esc_attr( $user->pmpro_scountry ) ); ?></div>
						<?php
					}
				} ?>
			</div>
		</div>
	<?php endif; ?>
	</div>
	<?php
}

add_action( 'pmproemd_add_extra_profile_output', 'pmproemd_add_address_section', 5, 2 );