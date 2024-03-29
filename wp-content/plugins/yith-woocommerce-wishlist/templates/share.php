<?php
/**
 * Share template
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Wishlist
 * @version 1.0.0
 */

global $yith_wcwl;

if( !is_user_logged_in() ) { return; }

if( get_option( 'yith_wcwl_share_fb' ) == 'yes' || get_option( 'yith_wcwl_share_twitter' ) == 'yes' || get_option( 'yith_wcwl_share_pinterest' ) == 'yes' ) {
    $share_url  = $yith_wcwl->get_wishlist_url();
    $share_url .= get_option( 'permalink-structure' ) != '' ? '&amp;user_id=' : '?user_id=';
    $share_url .= get_current_user_id();
    echo YITH_WCWL_UI::get_share_links( $share_url );
}