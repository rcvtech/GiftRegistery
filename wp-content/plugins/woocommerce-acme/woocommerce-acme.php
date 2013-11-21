<?php
/*
Plugin Name: WooCommerce Acme
Plugin URI: http://www.foxrunsoftware.net/articles/wordpress/creating-custom-plugin-for-your-woocommerce-shop/
Description: WooCommerce Acme custom plugin
Author: Acme Corp
Author URI: http://www.acme.com
Version: 1.0.0

	Copyright: © 2012 Acme Corp (email : developer@acme.com)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Acme' ) ) {
		
		/**
		 * Localisation
		 **/
		load_plugin_textdomain( 'wc_acme', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

		class WC_Acme {
			public function __construct() {
				// called only after woocommerce has finished loading
				add_action( 'woocommerce_init', array( &$this, 'woocommerce_loaded' ) );
				
				// called after all plugins have loaded
				add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
				
				// called just before the woocommerce template functions are included
				add_action( 'init', array( &$this, 'include_template_functions' ), 20 );
				
				// indicates we are running the admin
				if ( is_admin() ) {
					// ...
				}
				
				// indicates we are being served over ssl
				if ( is_ssl() ) {
					// ...
				}
    
				// take care of anything else that needs to be done immediately upon plugin instantiation, here in the constructor
			}
			
			/**
			 * Take care of anything that needs woocommerce to be loaded.  
			 * For instance, if you need access to the $woocommerce global
			 */
			public function woocommerce_loaded() {
				// ...
			}
			
			/**
			 * Take care of anything that needs all plugins to be loaded
			 */
			public function plugins_loaded() {
				// ...
			}
			
			/**
			 * Override any of the template functions from woocommerce/woocommerce-template.php 
			 * with our own template functions file
			 */
			public function include_template_functions() {
				include( 'woocommerce-template.php' );
			}
		}

		// finally instantiate our plugin class and add it to the set of globals
		$GLOBALS['wc_acme'] = new WC_Acme();
	}
}