<?php

class WC_Wishlists_Wishlist {

    public $post;
    public $id;

    public function __construct($id) {
        $this->id = $id;
        $this->post = get_post($id);
    }

    public function the_title() {
        echo get_the_title($this->id);
    }

    public function the_content() {
        global $post;
        setup_postdata($this->post);
        the_content();
        wp_reset_postdata();
    }

    public function the_url_edit() {
        echo self::get_the_url_edit($this->id);
    }

    public function the_url_view($sharing = false) {
        echo self::get_the_url_view($this->id, $sharing);
    }

    public function the_url_delete() {
        echo self::get_the_url_delete($this->id);
    }

    public function get_wishlist_sharing() {
        return self::get_the_wishlist_sharing($this->id);
    }

    public static function get_the_wishlist_sharing($id) {
        return get_post_meta($id, '_wishlist_sharing', true);
    }

    public function get_wishlist_sharing_key() {
        return self::get_the_wishlist_sharing_key($this->id);
    }

    public static function get_the_wishlist_sharing_key($id) {
        return get_post_meta($id, '_wishlist_sharing_key', true);
    }

    public function get_wishlist_owner() {
        return self::get_the_wishlist_owner($this->id);
    }

    public static function get_the_wishlist_owner($id) {
        return get_post_meta($id, '_wishlist_owner', true);
    }

    public static function get_the_url_edit($id) {
        return add_query_arg(array('wlid' => $id), (WC_Wishlists_Pages::get_url_for('edit-my-list')));
    }

    public static function get_the_url_view($id, $sharing = false) {
        if ($sharing && self::get_the_wishlist_sharing($id) == 'Shared') {
            return add_query_arg(array('wlid' => $id, 'wlkey' => self::get_the_wishlist_sharing_key($id)), (WC_Wishlists_Pages::get_url_for('view-a-list')));
        } else {
            return add_query_arg(array('wlid' => $id), (WC_Wishlists_Pages::get_url_for('view-a-list')));
        }
    }

    public static function get_the_url_delete($id) {
        return WC_Wishlists_Plugin::nonce_url('delete-list', add_query_arg(array('wlid' => $id, 'wlaction' => 'delete-list'), (WC_Wishlists_Pages::get_url_for('my-lists'))));
    }

    public static function create_list($title, $args = array()) {

        global $current_user;
        get_currentuserinfo();

        $defaults = array(
            'wishlist_title' => 'Wishlist',
            'wishlist_description' => '',
            'wishlist_type' => 'list',
            'wishlist_sharing' => 'Private',
            'wishlist_status' => is_user_logged_in() ? 'active' : 'temporary',
            'wishlist_owner' => WC_Wishlists_User::get_wishlist_key(),
            'wishlist_owner_email' => is_user_logged_in() ? $current_user->user_email : '',
            'wishlist_owner_notifications' => false,
            'wishlist_first_name' => is_user_logged_in() ? $current_user->user_firstname : '',
            'wishlist_last_name' => is_user_logged_in() ? $current_user->user_lastname : '',
            'wishlist_items' => array(),
            'wishlist_subscribers' => array(is_user_logged_in() ? $current_user->user_email : ''),
        );

        $args = wp_parse_args($args, $defaults);

        $args = apply_filters('wc_wishlists_create_list_args', $args);

        $wishlist_data = array(
            'post_type' => 'wishlist',
            'post_title' => $title ? $title : 'New List ' . date('Y-m-d h:i:s'),
            'post_content' => $args['wishlist_description'],
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'post_excerpt' => '',
            'post_author' => is_int($args['wishlist_owner']) ? $args['wishlist_owner'] : 1
        );

        $wishlist_id = wp_insert_post($wishlist_data);
        if (!$wishlist_id || is_wp_error($wishlist_id)) {

            if (is_wp_error($wishlist_id)) {
                WC_Wishlists_Messages::add_wp_error($wishlist_id);
            }

            WC_Wishlists_Mesages::add_error(WC_Wishlists_Messages::get_text('error_creating_list'));
            return false;
        } elseif ($wishlist_id && $wishlist_id > 0) {

            update_post_meta($wishlist_id, '_wishlist_status', $args['wishlist_status']);
            update_post_meta($wishlist_id, '_wishlist_sharing', $args['wishlist_sharing']);
            update_post_meta($wishlist_id, '_wishlist_type', $args['wishlist_type']);

            update_post_meta($wishlist_id, '_wishlist_owner', $args['wishlist_owner']);
            update_post_meta($wishlist_id, '_wishlist_email', $args['wishlist_owner_email']);
            update_post_meta($wishlist_id, '_wishlist_owner_notifications', $args['wishlist_owner_notifications']);

            update_post_meta($wishlist_id, '_wishlist_first_name', $args['wishlist_first_name']);
            update_post_meta($wishlist_id, '_wishlist_last_name', $args['wishlist_last_name']);

            update_post_meta($wishlist_id, '_wishlist_subscribers', apply_filters('wc_wishlists_default_subscribers', $args['wishlist_subscribers'], $wishlist_id));
            update_post_meta($wishlist_id, '_wishlist_items', apply_filters('wc_wishlists_default_items', $args['wishlist_items'], $wishlist_id));

            update_post_meta($wishlist_id, '_wishlist_sharing_key', uniqid(md5(date('Y-m-d h:i:s'))));

            do_action('wc_wishlists_created', $wishlist_id, $args);
            
            return $wishlist_id;
        }
    }

    public static function update_list($post_id, $args = array()) {
        global $woocommerce, $current_user;
        get_currentuserinfo();
        $post = get_post($post_id);

        $defaults = array(
            'wishlist_title' => get_the_title($post_id),
            'wishlist_description' => $post->post_content,
            'wishlist_type' => get_post_meta($post_id, '_wishlist_type', true),
            'wishlist_sharing' => get_post_meta($post_id, '_wishlist_sharing', true),
            'wishlist_status' => get_post_meta($post_id, '_wishlist_status', true),
            'wishlist_owner' => get_post_meta($post_id, '_wishlist_owner', true),
            'wishlist_owner_email' => get_post_meta($post_id, '_wishlist_email', true),
            'wishlist_owner_notifications' => get_post_meta($post_id, '_wishlist_owner_notifications', true),
            'wishlist_first_name' => get_post_meta($post_id, '_wishlist_first_name', true),
            'wishlist_last_name' => get_post_meta($post_id, '_wishlist_last_name', true),
            'wishlist_items' => get_post_meta($post_id, '_wishlist_items', true),
            'wishlist_subscribers' => get_post_meta($post_id, '_wishlist_subscribers', true),
        );

        $args = wp_parse_args($args, $defaults);

        $args = apply_filters('wc_wishlists_udpate_list_args', $args);

        $wishlist_data = array(
            'ID' => $post_id,
            'post_title' => $args['wishlist_title'],
            'post_content' => $args['wishlist_description'],
        );

        $wishlist_id = wp_update_post($wishlist_data);
        if (!$wishlist_id || is_wp_error($wishlist_id)) {

            if (is_wp_error($wishlist_id)) {
                WC_Wishlists_Messages::add_wp_error($wishlist_id);
            }

            $woocommerce->add_error(__('Unable to update list', 'wc_wishlist'));
            return false;
        } elseif ($wishlist_id && $wishlist_id > 0) {

            update_post_meta($wishlist_id, '_wishlist_sharing', $args['wishlist_sharing']);
            update_post_meta($wishlist_id, '_wishlist_type', $args['wishlist_type']);

            update_post_meta($wishlist_id, '_wishlist_email', $args['wishlist_owner_email']);
            update_post_meta($wishlist_id, '_wishlist_owner_notifications', $args['wishlist_owner_notifications']);

            update_post_meta($wishlist_id, '_wishlist_first_name', $args['wishlist_first_name']);
            update_post_meta($wishlist_id, '_wishlist_last_name', $args['wishlist_last_name']);

            update_post_meta($wishlist_id, '_wishlist_subscribers', apply_filters('wc_wishlists_update_subscribers', $args['wishlist_subscribers'], $wishlist_id));
            update_post_meta($wishlist_id, '_wishlist_items', apply_filters('wc_wishlists_update_items', $args['wishlist_items'], $wishlist_id));


            do_action('wc_wishlists_updated', $wishlist_id, $args);

            $arr = $woocommerce->session->wishlists_recently_modified;
            array_push($arr, $wishlist_id);
            $woocommerce->session->wishlists_recently_modified = $arr;

            return $wishlist_id;
        }
    }

    public static function update_owner($wishlist_id, $new_owner, $old_owner) {
        global $current_user;
        get_currentuserinfo();

        update_post_meta($wishlist_id, '_wishlist_owner', $new_owner, $old_owner);
        update_post_meta($wishlist_id, '_wishlist_status', 'active');
        $oe = get_post_meta($wishlist_id, '_wishlist_owner_email', true);
        if (empty($oe)) {
            update_post_meta($wishlist_id, '_wishlist_owner_email', $current_user->user_email);
        }
        
        $os = get_post_meta($wishlist_id, '_wishlist_subscribers', true);
        if (empty($os)) {
            update_post_meta($wishlist_id, '_wishlist_subscribers', $current_user->user_email);
        }
    }

    public static function delete_list($id) {
        $delete = apply_filters('wc_wishlists_before_delete', true, $id);
        $result = false;
        if ($delete) {
            $result = wp_delete_post($id, true);
        }
        do_action('wc_wishlists_deleted', $id);
        return $result;
    }

}

?>