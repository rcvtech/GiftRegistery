<?php

class WC_Wishlists_User {

    private static $key = '';
    private static $product_id_cache = false;
    
    public static function init() {
        global $woocommerce;

        if (is_user_logged_in()) {
            self::$key = get_current_user_id();
            return;
        }

        $needs_saving = false;
        $temp_key = '';
        $store_key = 'wc_wishlists_user';

        $session_value = '';
        if (function_exists('get_product')) {
            $session_value = $woocommerce->session->$store_key;
        } else {
            $session_value = isset($_SESSION[$store_key]) ? $_SESSION[$store_key] : '';
        }

        if (isset($_COOKIE[$store_key]['key'])) {
            $temp_key = $_COOKIE[$store_key]['key'];
        } elseif (!empty($session_value)) {
            $temp_key = $session_value['key'];
        } else {
            $temp_key = uniqid(md5(date('F j, Y @ h:i A')));
            $needs_saving = true;
        }

        //Save the newly generated key
        if ($needs_saving) {
            $data = array('key' => $temp_key);
            if (WC_Wishlists_Settings::get_setting('wc_wishlists_use_cookies', true)) {
                if (function_exists('get_product')) {
                    $woocommerce->session->$store_key = $data;
                } else {
                    $_SESSION[$store_key] = $data;
                }
                setcookie($store_key . '[key]', $temp_key, time() + 3600 * 24 * 30);
            } else {
                if (function_exists('get_product')) {
                    $woocommerce->session->$store_key = $data;
                } else {
                    $_SESSION[$store_key] = $data;
                }
            }
        }

        self::$key = $temp_key;
    }

    public static function get_wishlist_key() {
        if (empty(self::$key)) {
            self::init();
        }

        return self::$key;
    }

    public static function get_wishlists($by_type = false, $key = false) {
        if ($key == false){
            $key = self::get_wishlist_key();
        }
        
        $args = array(
            'post_type' => 'wishlist',
            'post_status' => 'publish',
            // 'orderby' => 'title post_date',
            'orderby' => 'date',
            'nopaging' => true,
            'meta_query' => array(
                array(
                    'key' => '_wishlist_owner',
                    'value' => $key,
                )
            )
        );

        if ($by_type) {
            $args['meta_query'][] = array('key' => '_wishlist_sharing', 'value' => $by_type);
        }

        $posts = get_posts($args);
        $lists = array();
        if ($posts) {
            foreach ($posts as $post) {
                $lists[] = new WC_Wishlists_Wishlist($post->ID);
            }
        }

        return $lists;
    }

    public static function get_wishlist_product_ids() {
        $lists = self::get_wishlists();
        $key = self::get_wishlist_key() . '_wishlist_products';
        $product_ids = array();
        if (self::$product_id_cache) {
            $product_ids = self::$product_id_cache;
        } else {
            $temp = array();

            foreach ($lists as $list) {
                $items = WC_Wishlists_Wishlist_Item_Collection::get_items($list->id);
                if ($items) {
                    foreach ($items as $item) {
                        $temp[$item['product_id']][] = $list->id;
                    }
                }
            }

            foreach ($temp as $product_id => $lists) {
                $product_ids[$product_id] = array_unique($lists);
            }

            self::$product_id_cache = $product_ids;
        }

        return $product_ids;
    }
}

?>