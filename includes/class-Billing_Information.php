<?php
/**
 * Copyright (c) 2017-2019 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Member_Directory\Tools;

use E20R\Utilities\Cache;
use E20R\Utilities\Utilities;

/**
 * Class Billing_Information
 * @package E20R\Member_Directory\Tools
 */
class Billing_Information {
	
	/**
	 * The current instance of this class
	 *
	 * @var null|Billing_Information $instance
	 */
	private static $instance = null;
	
	/**
	 * List of PMPro billing address fields
	 *
	 * @var array
	 */
	private $billing_field_list = array();
	
	/**
	 * List of PMPro shipping address fields
	 *
	 * @var array
	 */
	private $shipping_field_list = array();
	
	/**
	 * Found address field(s) for billing or shipping addresses in the fields="" attribute
	 *
	 * @param string $type
	 * @param array  $field_list
	 *
	 * @return bool
	 */
	public static function addressFieldsFound( $type, $field_list ) {
		
		$class = self::getInstance();
		
		if ( empty( $field_list ) ) {
			return false;
		}
		
		switch ( $type ) {
			case 'billing':
				
				$type_list = $class->billing_field_list;
				break;
			
			case 'shipping':
				
				$type_list = $class->shipping_field_list;
				break;
			
			default:
				
				$type_list = null;
		}
		
		// No address info so returning false
		if ( null === $type_list ) {
			return false;
		}
		
		$has_fields      = false;
		$field_name_list = array_keys( $field_list );
		
		foreach ( $type_list as $field_name => $field_label ) {
			
			// Found a Billing or Shipping field in the admin supplied fields attribute values?
			$has_fields = $has_fields || in_array( $field_name, $field_name_list );
		}
	}
	
	/**
	 * Get or instantiate and return the class (singleton)
	 *
	 * @return Billing_Information|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			
			// PMPro billing address fields (standard/default)
			self::$instance->billing_field_list = apply_filters( 'e20r-directory-billing-address-fields', array(
					'pmpro_bfirstname' => __( 'First name', 'e20r-directory-for-pmpro' ),
					'pmpro_blastname'  => __( 'Last name', 'e20r-directory-for-pmpro' ),
					'pmpro_baddress'   => __( 'Street address', 'e20r-directory-for-pmpro' ),
					'pmpro_baddress2'  => __( 'Street address 2', 'e20r-directory-for-pmpro' ),
					'pmpro_bcity'      => __( 'City', 'e20r-directory-for-pmpro' ),
					'pmpro_bstate'     => apply_filters( 'e20r-directory-state-label', __( 'State', 'e20r-directory-for-pmpro' ) ),
					'pmpro_bzipcode'   => apply_filters( 'e20r-directory-zipcode-label', __( 'Zip code', 'e20r-directory-for-pmpro' ) ),
					'pmpro_bcountry'   => __( 'Country', 'e20r-directory-for-pmpro' ),
					'pmpro_bphone'     => __( 'Phone', 'e20r-directory-for-pmpro' ),
					'pmpro_bemail'     => __( 'Email address', 'e20r-directory-for-pmpro' ),
				)
			);
			
			// PMPro shipping address fields (standard/default)
			self::$instance->shipping_field_list = apply_filters( 'e20r-directory-shipping-address-fields', array(
					'pmpro_sfirstname' => __( 'First name', 'e20r-directory-for-pmpro' ),
					'pmpro_slastname'  => __( 'Last name', 'e20r-directory-for-pmpro' ),
					'pmpro_saddress'   => __( 'Street address', 'e20r-directory-for-pmpro' ),
					'pmpro_saddress2'  => __( 'Street address 2', 'e20r-directory-for-pmpro' ),
					'pmpro_scity'      => __( 'City', 'e20r-directory-for-pmpro' ),
					'pmpro_sstate'     => apply_filters( 'e20r-directory-state-label', __( 'State', 'e20r-directory-for-pmpro' ) ),
					'pmpro_szipcode'   => apply_filters( 'e20r-directory-zipcode-label', __( 'Zip code', 'e20r-directory-for-pmpro' ) ),
					'pmpro_scountry'   => __( 'Country', 'e20r-directory-for-pmpro' ),
					'pmpro_sphone'     => __( 'Phone', 'e20r-directory-for-pmpro' ),
					'pmpro_semail'     => __( 'Email address', 'e20r-directory-for-pmpro' ),
				)
			);
		}
		
		return self::$instance;
	}
	
	/**
	 * Remove shipping and billing address info
	 *
	 * @param array    $custom_fields
	 * @param \WP_User $user
	 *
	 * @return array
	 */
	public function fixAddressInfo( $custom_fields, $user ) {
		
		global $e20rmd_has_billing_fields;
		global $e20rmd_has_shipping_fields;
		global $e20rmd_show_billing_address;
		global $e20rmd_show_shipping_address;
		
		$utils = Utilities::get_instance();
		
		if ( isset( $custom_fields['pmpro_baddress1'] ) ) {
			$utils->log( "No Billing address fields found in list..." );
			
			return $custom_fields;
		}
		
		if ( false === $e20rmd_show_billing_address && false === $e20rmd_show_shipping_address ) {
			
			$utils->log( "Not expecting there to be any fields to process!" );
			
			return $custom_fields;
		}
		
		$user_has_billing_address  = self::userHasAddress( 'billing', $user );
		$user_has_shipping_address = self::userHasAddress( 'shipping', $user );
		
		if ( true === $user_has_billing_address ) {
			$e20rmd_has_billing_fields = self::getBillingFields();
		}
		
		if ( true === $user_has_shipping_address ) {
			$e20rmd_has_shipping_fields = self::getShippingFields();
		}
		
		$search = "(firstname|lastname|address$|address\d$|city|state|zipcode|country|phone|email)";
		
		foreach ( $custom_fields as $key => $field_def ) {
			
			$label      = $field_def[0];
			$field_name = $field_def[1];
			
			// Find any configured billing info fields
			if ( true === $user_has_billing_address &&
			     1 === preg_match( "/^pmpro_b{$search}/", $field_name )
			) {
				
				if ( empty( $e20rmd_has_billing_fields ) ) {
					$e20rmd_has_billing_fields = array();
				}
				
				// Set the custom field name/label and remove the entry for the custom fields list (moved to own Billing address section)
				$e20rmd_has_billing_fields[ $field_name ] = $label;
				
				unset( $custom_fields[ $key ] );
			} else {
				$utils->log( "No billing address found for user" );
			}
			
			// Find any configured shipping info fields
			if ( true === $user_has_shipping_address &&
			     1 === preg_match( "/^pmpro_s{$search}/", $field_name )
			) {
				
				// Save the custom label (if available)
				$e20rmd_has_shipping_fields[ $field_name ] = $label;
				
				// Remove the field name/label from the fields array (moved to own Shipping address section)
				unset( $custom_fields[ $key ] );
				
			} else {
				$utils->log( "No shipping address found for user" );
			}
		}
		
		return $custom_fields;
	}
	
	/**
	 * Check if the user object contains the PMPro Shipping or Billing address data
	 *
	 * @param string   $type
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	public static function userHasAddress( $type, $user ) {
		
		$utils       = Utilities::get_instance();
		$has_address = false;
		$class       = self::getInstance();
		
		switch ( $type ) {
			case 'billing':
				
				$type_list = array_keys( $class->billing_field_list );
				break;
			
			case 'shipping':
				
				$type_list = array_keys( $class->shipping_field_list );
				break;
			
			default:
				
				$type_list = null;
		}
		
		// No address info so returning false
		if ( null === $type_list ) {
			return false;
		}
		
		foreach ( $type_list as $address_variable ) {
			
			$has_address = $has_address || ( ! empty( $user->{$address_variable} ) && 1 !== preg_match( '/pmpro_[bs][email|firstname|lastname]/i', $address_variable ) );
		}
		
		$utils->log( "Did we find a {$type} address for {$user->ID}? " . ( $has_address ? 'Yes' : 'No' ) );
		
		return $has_address;
	}
	
	/**
	 * Return the list of PMPro billing address fields
	 *
	 * @return array
	 */
	public static function getBillingFields() {
		
		$class = self::getInstance();
		
		return $class->billing_field_list;
	}
	
	/**
	 * Return the list of PMPro shipping address fields
	 *
	 * @return array
	 */
	public static function getShippingFields() {
		
		$class = self::getInstance();
		
		return $class->shipping_field_list;
	}
	
	/**
	 * Add Billing and Shipping fields
	 *
	 * @param array    $fields
	 * @param \WP_User $user
	 *
	 */
	public function addAddressSection( $fields, $user ) {
		
		global $e20rmd_has_billing_fields;
		global $e20rmd_has_shipping_fields;
		global $e20rmd_show_billing_address;
		global $e20rmd_show_shipping_address;
		
		$utils = Utilities::get_instance();
		
		$pmpro_shipping_addon = (bool) ( $utils->plugin_is_active( null, 'pmproship_pmpro_checkout_boxes' ) ||
		                                 $this->addressInfoFoundInDB( 'billing' ) );
		$pmpro_billing_addon  = (bool) ( pmpro_getOption( 'stripe_billingaddress' ) ||
		                                 apply_filters( 'pmpro_include_billing_address_fields', true ) ||
		                                 $this->addressInfoFoundInDB( 'billing' )
		);
		
		if ( false === $pmpro_shipping_addon && false === $pmpro_billing_addon ) {
			$utils->log( "Billing and shipping address info not enabled!" );
			
			return;
		}
		
		$b_heading = apply_filters( 'e20r-member-profile_billing_header', __( "Billing Address", "e20r-directory-for-pmpro" ) );
		$s_heading = apply_filters( 'e20r-member-profile_shipping_header', __( "Shipping Address", "e20r-directory-for-pmpro" ) );
		
		// Nothing to show!
		if ( ( false === $e20rmd_show_billing_address && false === $e20rmd_show_shipping_address ) ||
		     ( empty( $e20rmd_has_billing_fields ) && empty( $e20rmd_has_shipping_fields ) ) ) {
			return;
		}
		
		$has_billing_address  = self::userHasAddress( 'billing', $user );
		$has_shipping_address = self::userHasAddress( 'shipping', $user );
		
		if ( false === $has_billing_address && false === $has_shipping_address ) {
			$utils->log( "User doesn't have a billing or shipping address..." );
			
			return;
		} ?>
        <div class="e20rmd_address_section">
			<?php if ( true === $has_billing_address &&
			           true === $e20rmd_show_billing_address &&
			           ! empty( $e20rmd_has_billing_fields )
			) {
				$utils->log( "Loading billing info! " ); ?>
                <!-- Billing address -->
                <div class="e20r-directory-for-pmpro_billing_address">
                    <h3 class="e20rmd_billing_address_heading"><?php esc_attr_e( $b_heading ); ?></h3>
                    <div class="e20rmd-billing-address">
						<?php
						foreach ( $e20rmd_has_billing_fields as $field_name => $field_label ) { ?>
                            <div class="e20rmd-address"><?php
								
								$value = isset( $user->{$field_name} ) ? $user->{$field_name} : null;
								
								if ( 1 === preg_match( '/pmpro_bfirstname/i', $field_name ) ) {
									$value       = sprintf( '%s %s', $user->pmpro_bfirstname, $user->pmpro_blastname );
									$field_label = __( 'Name:', 'e20r-directory-for-pmpro' );
								}
								
								if ( 1 === preg_match( '/pmpro_blastname/i', $field_name ) ) {
									continue;
								}
								
								printf(
									'<span class="e20r-directory-address-entry"><label>%s:</label><span>%s</span></span>',
									$field_label,
									$value
								); ?>
                            </div> <?php
						} ?>
                    </div>
                </div>
			<?php } ?>
			<?php if ( true === $has_shipping_address &&
			           true === $e20rmd_show_shipping_address &&
			           ! empty( $e20rmd_has_shipping_fields ) ) { ?>
                <!-- Shipping address -->
                <div class="e20r-directory-for-pmpro_shipping_address">
                    <h3 class="e20rmd_shipping_address_heading"><?php esc_attr_e( $s_heading ); ?></h3>
                    <div class="e20rmd-shipping-address">
						<?php
						foreach ( $e20rmd_has_shipping_fields as $field_name => $field_label ) { ?>
                            <div class="e20rmd-address">
								<?php
								
								$value = isset( $user->{$field_name} ) ? $user->{$field_name} : null;
								
								if ( 1 === preg_match( '/pmpro_sfirstname/i', $field_name ) ) {
									$value       = sprintf( '%s %s', $user->pmpro_bfirstname, $user->pmpro_blastname );
									$field_label = __( 'Name:', 'e20r-directory-for-pmpro' );
								}
								
								if ( 1 === preg_match( '/pmpro_slastname/i', $field_name ) ) {
									continue;
								}
								printf(
									'<span class="e20r-directory-address-entry"><label>%s:</label><span>%s</span></span>',
									$field_label,
									$value
								); ?>
                            </div> <?php
						} ?>
                    </div>
                </div>
			<?php } ?>
        </div>
		<?php
	}
	
	/**
	 * Did we find the address type fields in the usermeta table
	 *
	 * @param string $type - billing or shipping
	 *
	 * @return bool
	 */
	private function addressInfoFoundInDB( $type ) {
		
		$utils = Utilities::get_instance();
		
		switch ( $type ) {
			case 'billing':
				$cache_key   = 'billing_found';
				$field_names = array_keys( self::getBillingFields() );
				
				break;
			case 'shipping':
				$cache_key   = 'shipping_found';
				$field_names = array_keys( self::getShippingFields() );
				break;
			
			default:
				return false;
		}
		
		if ( null === ( $found = Cache::get( $cache_key, 'e20rdfp' ) ) ) {
			
			global $wpdb;
			
			$fields = implode( "', '", array_map( 'esc_sql', $field_names ) );
			$sql    = "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key IN ( '{$fields}' )";
			
			$utils->log( "SQL to use: {$sql}" );
			
			$field_count = (int) $wpdb->get_var( $sql );
			
			$found = $field_count > 0 ? true : null;
			
			if ( null !== $found ) {
				Cache::set( $cache_key, $found, DAY_IN_SECONDS, 'e20rdfp' );
			}
		}
		
		return $found;
	}
	
	/**
	 * @param int      $user_id
	 * @param \WP_User $old_user_data
	 *
	 * @return int
	 */
	public function maybeSaveBillingInfo( $user_id, $old_user_data ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty( $user_id ) ) {
			return $user_id;
		}
		
		$user = get_user_by( 'ID', $user_id );
		
		if ( false === self::userHasAddress( 'billing', $user ) ) {
			$utils->log( "No billing info settings found" );
			
			return $user_id;
		}
		
		$billing_fields  = array_keys( self::getBillingFields() );
		$shipping_fields = array_keys( self::getShippingFields() );
		
		if ( ! empty( $billing_address ) ) {
			$this->saveFields( $user_id, $billing_fields );
		}
		
		if ( ! empty( $shipping_fields ) ) {
			$this->saveFields( $user_id, $shipping_fields );
		}
	}
	
	/**
	 * Save any update(d) address fields
	 *
	 * @param int   $user_id
	 * @param array $field_list
	 */
	private function saveFields( $user_id, $field_list ) {
		
		$utils = Utilities::get_instance();
		
		if ( empty ( $field_list ) ) {
			return;
		}
		
		foreach ( array_keys( $field_list ) as $address_field ) {
			
			$value = $utils->get_variable( $address_field, null );
			
			if ( null === $value ) {
				delete_user_meta( $user_id, $address_field );
			}
			
			if ( ! empty( $value ) ) {
				update_user_meta( $user_id, $address_field, $value, true );
			}
		}
	}
	
}
