<?php 
/*
  Plugin Name: WooCommerce testplugin
  Plugin URI: http://innobit.co.in/woo
  Description:  WooCommerce testplugin.
  Version: 1.3.2
  Author: Akhilesh Singh
  Author URI: http://google.com
  Requires at least: 3.1
  Tested up to: 3.3

  Text Domain: wc_wishlist
  Domain Path: /lang/

  Copyright: © 2009-2013 Lucas Stark
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 
 
/**
 * Builds us our custom buy text if we are on any of the boat categories
 *
 * @since 1.0
 * @author SFNdesign, Curtis McHale
 *
 * @uses is_product()               Returns true if we are on a single product
 * @uses get_product()              Gets the product object
 * @uses has_term()                 Returns true if the post_object has the term
 * @uses west_is_child_of_term()    Returns true if current post_object has any terms that are a child if the specified term
 */
function western_custom_buy_buttons(){
 
    // die early if we aren't on a product
    if ( ! is_product() ) return;
 
    $product = get_product();
 
    //if ( has_term( 'boats', 'product_cat', $product ) || west_is_child_of_term( 'boats', 'product_cat', $product ) ){
 
        // removing the purchase buttons
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
        remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
        remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
        remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
 
        // adding our own custom text
        add_action( 'woocommerce_single_product_summary', 'western_boat_purchase_text', 30 );
        add_action( 'woocommerce_simple_add_to_cart', 'western_boat_purchase_text', 30 );
        add_action( 'woocommerce_grouped_add_to_cart', 'western_boat_purchase_text', 30 );
        add_action( 'woocommerce_variable_add_to_cart', 'western_boat_purchase_text', 30 );
        add_action( 'woocommerce_external_add_to_cart', 'western_boat_purchase_text', 30 );
 
    //}
 
} // western_custom_buy_buttons
add_action( 'wp', 'western_custom_buy_buttons' );
 
/**
 * Our custom text
 */
function western_boat_purchase_text(){
    echo '<p>Please get in touch about boat purchases</p><br />';
} // western_boat_purchase_text
 
/**
 * Should return true if any of the terms on the post object is a child if the specified term
 *
 * @since 1.0
 * @author SFNdesign, Curtis McHale
 *
 * @param string    $term           required        The term parent we are checking against
 * @param string    $taxonomy       required        The taxonomy that we are checking for
 * @param object    $post_object    required        The post object we are checking against
 *
 * @return bool     True if term is child of parent
 *
 * @uses get_term_by()          Gets term for given taxonomy by the field specified
 * @uses get_the_terms()        Returns terms for post_object
 * @uses wp_list_pluck()        Bloody magic, or pulls all the values from an array, bloody magic
 */
function west_is_child_of_term( $term, $taxonomy, $post_object ){
 
    // get id of parent term
    $term = get_term_by( 'slug', $term, $taxonomy );
 
    // get terms on post objects
    $post_terms = get_the_terms( (int) $post_object->ID, (string) $taxonomy );
 
    // build array of term parent ids
    $post_term_parent_ids = wp_list_pluck( $post_terms, 'parent' );
 
    if ( in_array( $term->term_id, $post_term_parent_ids ) ){
        return true;
    }
 
    return false;
 
} // west_is_child_of_term