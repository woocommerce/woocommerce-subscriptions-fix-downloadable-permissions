<?php
/**
 * Plugin Name: WooCommerce Subscriptions - Downloadable Permissions Fixer
 * Plugin URI: https://github.com/Prospress/woocommerce-subscriptions-fix-downloadable-permissions
 * Description: Fixes the downloadable products permissions if some rows are duplicated or contain an empty "order_key" value
 * Author: Prospress Inc.
 * Version: 1.0.4
 * Author URI: http://prospress.com
 *
 * GitHub Plugin URI: Prospress/woocommerce-subscriptions-fix-downloadable-permissions
 * GitHub Branch: master
 *
 * Copyright 2017 Prospress, Inc.  (email : freedoms@prospress.com)
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
 *
 * @package		WooCommerce Subscriptions
 * @author		Prospress Inc.
 * @since		1.0
 */
function wcs_fix_downloadable_permissions() {
	// Use a URL param to act as a flag for when to run the fixes - avoids running fixes in multiple requests at the same time
	if ( ! isset( $_GET['wcs-fix-downloadable-permissions'] ) ) {
		return;
	}
	global $wpdb;
	
	$logger = new WC_Logger();
	$logger->add( 'wcs-fix-downloadable-permissions', '----------- Initiating Download Permissions Fixer -----------' );
		
	$downloads_with_empty_order_key = $wpdb->get_results( 
		"
		SELECT * 
		FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
		WHERE `order_key` = '' 
		"
	);
	
	if($downloads_with_empty_order_key){
		
		foreach ( $downloads_with_empty_order_key as $download ) 
		{
			$permission_id = $download->permission_id;
			$user_id = $download->user_id;
			$download_id = $download->download_id;
			$order_id = $download->order_id;
			$order_key = get_post_meta($order_id, '_order_key', true);
			
			$logger->add( 'wcs-fix-downloadable-permissions', "#$permission_id - Download permission with empty 'order_key' ( order_id = $order_id  -  order_key = $order_key" );
			
			$wpdb->update( 
				"{$wpdb->prefix}woocommerce_downloadable_product_permissions", 
				array( 
					'order_key' => $order_key,	
				), 
				array( 'permission_id' => $permission_id ), 
				array( 
					'%s',	
				)
			);
			$logger->add( 'wcs-fix-downloadable-permissions', "#$permission_id - Fixed permission" );
			
			$duplicated_rows = $wpdb->query( 
				$wpdb->prepare( 
				"
		         DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
				 WHERE permission_id <> %d
				 AND user_id = %d
				 AND order_key = %s
				 AND download_id = %s
				",
			        $permission_id, $user_id, $order_key, $download_id
		        )
			);
			
			$logger->add( 'wcs-fix-downloadable-permissions', "#$permission_id - Deleted all duplicates of this permission " );
			
		}
	}else{
		$logger->add( 'wcs-fix-downloadable-permissions', 'No downloadable product permissions found with an empty "order_key" (Everything is ok)' );
	}
		
}
add_action( 'admin_footer', 'wcs_fix_downloadable_permissions' );
