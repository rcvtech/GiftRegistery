<?php

add_action('wc_wishlists_cron', 'wc_wishlists_do_cron');

function wc_wishlists_do_cron() {
    $instance = WC_Wishlists_Cron::instance();
    $instance->send_price_changes();
}

class WC_Wishlists_Cron {

    private static $instance;

    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new WC_Wishlists_Cron();
        }

        return self::$instance;
    }

    public static function register() {
        self::instance();
        self::$instance->register_jobs();
    }

    public function __construct() {
        
    }

    public function register_jobs() {
        $gmt = microtime(true);
        $scheduled = wp_next_scheduled('wc_wishlists_cron');
        $date = date('F j, Y @ h:i A', $scheduled);

        if (!$scheduled) {
            wp_schedule_event(time(), 'twicedaily', 'wc_wishlists_cron');
        } else {
            
        }
    }

    public function send_price_changes() {

        $args = array(
            'post_type' => 'wishlist',
            'post_status' => 'publish',
            'nopaging' => true
        );

        $posts = get_posts($args);

        $receivers = array();
        foreach ($posts as $post) {
            $post_id = $post->ID;
            $wishlist_subscribers = get_post_meta($post_id, '_wishlist_subscribers', true);
            if (!empty($wishlist_subscribers)) {

                $wishlist = new WC_Wishlists_Wishlist($post_id);
                $wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items($post_id);

                $changes = array();

                if ($wishlist_items) {
                    foreach ($wishlist_items as $item) {
                        $id = isset($item['variation_id']) && !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];

                        if (function_exists('get_product')) {
                            $product = get_product(isset($item['variation_id']) && !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'] );
                        } else {
                            if (isset($item['variation_id'])) {
                                $product = new WC_Product_Variation($item['variation_id']);
                            } else {
                                $product = new WC_Product($item['product_id']);
                            }
                        }

                        $price = $product->get_price_excluding_tax();
                        $wl_price = isset($item['wl_price']) ? $item['wl_price'] : $price;

                        if ($wl_price && $wl_price > $price) {
                            $text = sprintf(__('<a href="%s">%s</a> has been reduced in price! Was %s, now avaiable for %s ', 'wc_wishlist'), get_permalink($product->id), $product->get_title(), $wl_price, $price);
                            $text = apply_filters('woocommerce_wishlist_price_change_message', $text, $id, $price, $wl_price);
                            $changes[$id] = array('title' => $product->get_title(), 'old_price' => $wl_price, 'new_price' => $price, $text => $text);
                        }
                    }
                }

                if ($changes && count($changes)) {
                    foreach ($wishlist_subscribers as $receiver) {
                        if (!isset($receivers[$receiver])) {
                            $receivers[$receiver] = array();
                        }

                        foreach ($changes as $id => $change) {
                            $receivers[$receiver][$id] = $change;
                        }
                    }
                }
            }
        }

        //At this point we have an array of receivers and the assoicated product price change descriptions. 
        if ($receivers && count($receivers)) {
            
            foreach ($receivers as $receiver => $changes) {
                $html = '';
                ob_start();
                
                $email_heading = sprintf( __('%s', 'wc_wishlist'), get_option('blogname') );
                woocommerce_wishlists_get_template('price-change-email.php', 
                        array(
                                'changes' => $changes, 
                                'email_heading' => $email_heading)
                    );
                $html = ob_get_clean();
                
                $fp = fopen(WC_Wishlists_Plugin::plugin_path() . '/logs/' . sanitize_title($receiver) . '.txt', 'a+');
                fwrite($fp, $html);
                fclose($fp);
            }
        }


        return true;
    }

}

?>