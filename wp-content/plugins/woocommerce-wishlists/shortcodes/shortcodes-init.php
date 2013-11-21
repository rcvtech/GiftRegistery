<?php

/**
 * Shortcodes init
 *
 * Init main shortcodes, and add a few others such as recent products.
 *
 * @author 	Lucas Stark
 * @category 	Shortcodes
 * @package 	WooCommerce_Wishlists/Shortcodes
 * @version     1.0.0
 */
function shortcode_wc_wishlists_create($atts) {
    ob_start();
    if (is_user_logged_in() || (WC_Wishlists_Settings::get_setting('wc_wishlist_guest_enabled', 'enabled') == 'enabled')) {
        woocommerce_wishlists_get_template('create-a-list.php');
    } else {
        woocommerce_wishlists_get_template('guest-disabled.php');
    }
    return ob_get_clean();
}

function shortcode_wc_wishlists_search($atts) {
    global $paged;

    if (empty($paged)) {
        $paged = 1;
    }

    ob_start();

    $args = array(
        'paged' => $paged,
        'posts_per_page' => 10,
        'post_type' => 'wishlist',
        'meta_query' => array(
            array(
                'key' => '_wishlist_sharing',
                'value' => 'public'
            )
        )
    );

    if (isset($_GET['f-list'])) {

        $email_args = array(
            'post_type' => 'wishlist',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_wishlist_sharing',
                    'value' => 'public'
                ),
                array(
                    'key' => '_wishlist_email',
                    'value' => $_GET['f-list'],
                    'compare' => '='
                )
            )
        );

        $name = explode(' ', $_GET['f-list']);
        $name_args = array(
            'post_type' => 'wishlist',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_wishlist_sharing',
                    'value' => 'public'
                ),
                array(
                    'key' => '_wishlist_first_name',
                    'value' => $name[0],
                    'compare' => '='
                )
            )
        );


        if (count($name) > 1) {
            $name_args['meta_query'][] = array(
                'key' => '_wishlist_last_name',
                'value' => $name[1],
                'compare' => '='
            );
        }

        $email_ids = get_posts($email_args);
        $name_ids = get_posts($name_args);

        if (count($name == 1)) {
            $name_args = array(
                'post_type' => 'wishlist',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_wishlist_sharing',
                        'value' => 'public'
                    ),
                    array(
                        'key' => '_wishlist_last_name',
                        'value' => $name[0],
                        'compare' => '='
                    )
                )
            );
            
            $last_ids = get_posts($name_args);
            if ($last_ids) {
                $name_ids = array_merge($name_ids, $last_ids);
            }
        }

        $found_ids = array();
        if ($email_ids) {
            foreach ($email_ids as $post) {
                $found_ids[] = $post->ID;
            }
        }

        if ($name_ids) {
            foreach ($name_ids as $post) {
                $found_ids[] = $post->ID;
            }
        }

        if (count($found_ids)) {
            $args['post__in'] = $found_ids;
        } else {
            $args['post__in'] = array(0);
        }
    }

    query_posts($args);
    woocommerce_wishlists_get_template('find-a-list.php');
    wp_reset_query();

    return ob_get_clean();
}

function shortcode_wc_wishlists_single($atts) {
    ob_start();

    if (isset($_REQUEST['wlid']) && !empty($_REQUEST['wlid'])) {
        global $post;
        $post = get_post($_REQUEST['wlid']);
        if ($post && $post->post_type == 'wishlist') {
            setup_postdata($post);
            woocommerce_wishlists_get_template('view-a-list.php');
        }
    }

    wp_reset_postdata();


    return ob_get_clean();
}

function shortcode_wc_wishlists_my_archive($atts) {
    ob_start();
    if (is_user_logged_in() || (WC_Wishlists_Settings::get_setting('wc_wishlist_guest_enabled', 'enabled') == 'enabled')) {
        woocommerce_wishlists_get_template('my-lists.php');
    } else {
        woocommerce_wishlists_get_template('guest-disabled.php');
    }

    return ob_get_clean();
}

function shortcode_wc_wishlists_edit($atts) {
    ob_start();

    if (is_user_logged_in() || (WC_Wishlists_Settings::get_setting('wc_wishlist_guest_enabled', 'enabled') == 'enabled')) {

        if (isset($_REQUEST['wlid']) && !empty($_REQUEST['wlid'])) {
            global $post;
            $post = get_post($_REQUEST['wlid']);
            if ($post && $post->post_type == 'wishlist') {
                $key = WC_Wishlists_Wishlist::get_the_wishlist_owner($post->ID);
                $user_key = WC_Wishlists_User::get_wishlist_key();
                if ($key == $user_key || current_user_can('manage_woocommerce')) {
                    setup_postdata($post);
                    woocommerce_wishlists_get_template('edit-my-list.php');
                } else {
                    
                }
            }
        }

        wp_reset_postdata();
    } else {
        woocommerce_wishlists_get_template('guest-disabled.php');
    }

    return ob_get_clean();
}

add_shortcode('wc_wishlists_create', 'shortcode_wc_wishlists_create');
add_shortcode('wc_wishlists_search', 'shortcode_wc_wishlists_search');
add_shortcode('wc_wishlists_single', 'shortcode_wc_wishlists_single');
add_shortcode('wc_wishlists_my_archive', 'shortcode_wc_wishlists_my_archive');
add_shortcode('wc_wishlists_edit', 'shortcode_wc_wishlists_edit');

