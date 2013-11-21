<?php
/*
  Plugin Name: WooCommerce WishLists
  Plugin URI: http://woothemes.com/woocommerce
  Description:  WooCommerce Wishlists allows you to create public and personal wishlists.
  Version: 1.3.2
  Author: Lucas Stark
  Author URI: http://lucasstark.com http://www.61pixels.com
  Requires at least: 3.1
  Tested up to: 3.3

  Text Domain: wc_wishlist
  Domain Path: /lang/

  Copyright: © 2009-2013 Lucas Stark
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Required functions
 */
if (!function_exists('woothemes_queue_update'))
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update(plugin_basename(__FILE__), '6bd20993ea96333eab6931ec2adc6d63', '171144');


if (is_woocommerce_active()) {

	class WC_Wishlists_Plugin {
		/**
		 * @var string
		 */
		var $version = '1.2.10';

		/**
		 * @var array
		 */
		private $_body_classes = array();

		/**
		 * EduPress Recruiter Constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Define constants
			define('WC_WISHLISTS_VERSION', $this->version);

			load_plugin_textdomain('wc_wishlist', false, dirname(plugin_basename(__FILE__)) . '/lang/');

			$this->includes();

			// Installation
			if (is_admin() && !defined('DOING_AJAX')) {
				$this->install();
			}

			add_filter('post_link', array(&$this, 'post_link'), 10, 2);
			add_filter('post_type_archive_link', array(&$this, 'post_type_archive_link'), 10, 2);
			add_filter('post_type_link', array(&$this, 'post_link'), 10, 2);

			add_action('woocommerce_init', array(&$this, 'init'), 0);
			add_action('init', array(&$this, 'init_taxonomy'), 11);

			add_action('template_redirect', array(&$this, 'process_request'), 9);
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_assets'));
			add_action('template_redirect', array(&$this, 'add_session_message'));

			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('wp_head', array($this, 'wp_head'));


			add_action('woocommerce_single_product_summary', array(&$this, 'bind_wishlist_button'), 0);
			add_action('woocommerce_after_my_account', array(&$this, 'add_lists_to_account_page'));
		}

		public function includes() {
			/* Regular function includes */
			include 'woocommerce-wishlists-functions.php';
			include 'woocommerce-wishlists-api.php';

			/* Include Models */
			include 'classes/models/class-wc-wishlists-wishlist.php';
			include 'classes/models/class-wc-wishlists-wishlist-item-collection.php';


			/* Include Class Files */
			include 'classes/class-wc-wishlists-user.php';
			include 'classes/class-wc-wishlists-pages.php';
			include 'classes/class-wc-wishlists-settings.php';
			include 'classes/class-wc-wishlists-request-handler.php';
			include 'classes/class-wc-wishlists-messages.php';

			include 'shortcodes/shortcodes-init.php';

			include 'classes/class-wc-wishlist-cart.php';
			
			//Cron jobs - next release will have price change notifications.  Leaving the stubs in here for now.
			//include 'classes/class-wc-wishlists-cron.php';
			
			WC_Wishlists_Cart::register();
			
			if (is_admin()) {

				include 'classes/class-wc-wishlists-admin-settings.php';

				include 'classes/class-wc-wishlists-admin-wishlist.php';

				//Activate the settings tab in the WooCommerce Settings area.
				WC_Wishlists_Settings_Admin::instance();
				WC_Wishlists_Wishlist_Admin::instance();
			}
		}

		public function init() {
			global $woocommerce;
			if (!is_admin()) {
				$woocommerce->session->wishlists_recently_modified = array();
				//Setup the wishlists user, loads or creates the correct wishlist key.
				WC_Wishlists_User::init();
			}

			//Next release will contain price change notifications.
			//WC_Wishlists_Cron::register();
		}

		public function process_request() {
			global $woocommerce;
			if (is_page()) {
				if (is_page(WC_Wishlists_Pages::get_page_id('view-a-list')) && empty($_GET['wlid'])) {

					$woocommerce->add_error(__('Please select a list first', 'wc_wishlist'));

					wp_redirect(get_permalink(WC_Wishlists_Pages::get_page_id('find-a-list')));

					die();
				}

				if (is_page(WC_Wishlists_Pages::get_page_id('edit-my-list')) && empty($_GET['wlid'])) {

					$woocommerce->add_error(__('Please select a list first', 'wc_wishlist'));

					wp_redirect(get_permalink(WC_Wishlists_Pages::get_page_id('my-lists')));

					die();
				}
			}

			//Allow the request handler to handle any front end wishlist actions, such as creating a list, editing, etc...
			WC_Wishlists_Request_Handler::process_request();
		}

		public function enqueue_assets() {
			wp_enqueue_script('woocommerce-wishlists', self::plugin_url() . '/assets/js/woocommerce-wishlists.js', array('jquery'));
			wp_enqueue_script('bootstrap-modal', self::plugin_url() . '/assets/js/bootstrap-modal.js', array('jquery'));

			$wishlist_params = array(
			    'root_url' => untrailingslashit(get_site_url()),
			);

			wp_localize_script('woocommerce-wishlists', 'wishlist_params', apply_filters('woocommerce_wishlist_params', $wishlist_params));

			wp_enqueue_style('woocommerce-wishlists', self::plugin_url() . '/assets/css/woocommerce-wishlists.css');
		}

		public function bind_wishlist_button() {
			global $product;



			if ($product->is_in_stock() && $product->product_type != 'external') {
				add_action('woocommerce_after_add_to_cart_button', array(&$this, 'add_to_wishlist_button'));
			} else {

			}
		}

		public function add_to_wishlist_button() {
			global $add_to_wishlist_args;
			if (WC_Wishlists_Settings::get_setting('wc_wishlist_lists_enabled', 'enabled') == 'enabled' || WC_Wishlists_Settings::get_setting('wc_wishlist_registries_enabled', 'enabled') == 'enabled') {
				if (is_user_logged_in() || (WC_Wishlists_Settings::get_setting('wc_wishlist_guest_enabled', 'enabled') == 'enabled')) {
					$add_to_wishlist_args = array();
					$add_to_wishlist_args['btn_class'] = array();

					$add_to_wishlist_id = false;
					$lists = WC_Wishlists_User::get_wishlists();
					if ($lists) {
						//if (count($lists) > 1) {
						$add_to_wishlist_args['btn_class'][] = 'wl-add-to';
						$add_to_wishlist_args['single_id'] = '';
						add_action('wp_footer', array(&$this, 'add_to_wishlist_options'));
					} else {
						$add_to_wishlist_args['btn_class'][] = 'wl-add-to';
						$add_to_wishlist_args['btn_class'][] = 'wl-add-to-single';
						$add_to_wishlist_args['single_id'] = '';
					}

					if (WC_Wishlists_Settings::get_setting('wc_wishlist_use_button', 'button') == 'button') {
						$add_to_wishlist_args['btn_class'][] = 'wl-add-but';
						$add_to_wishlist_args['btn_class'][] = 'button';
					} else {
						$add_to_wishlist_args['btn_class'][] = ' wl-add-link';
					}

					$add_to_wishlist_args['btn_class'][] = WC_Wishlists_Settings::get_setting('wc_wishlist_icon', '');

					woocommerce_wishlists_get_template('add-to-wishlist-link.php');
				}
			}
		}

		public function add_to_wishlist_options() {
			woocommerce_wishlists_get_template('add-to-wishlist-modal.php');
		}

		public function add_to_wishlist_shop_options() {
			woocommerce_wishlists_get_template('add-to-wishlist-shop-modal.php');
		}

		public function admin_menu() {

		}

		public function add_lists_to_account_page() {
			woocommerce_wishlists_get_template('my-account-lists.php');
		}

		/**
		 * Install upon activation.
		 *
		 * @access public
		 * @return void
		 */
		function install() {
			include 'classes/class-wc-wishlists-installer.php';
			register_activation_hook(__FILE__, array('WC_Wishlists_Installer', 'activate'));
			WC_Wishlists_Installer::check_install();
		}

		/** Register the taxonomy * */
		public function init_taxonomy() {
			if (current_user_can('manage_woocommerce')) {
				$show_in_menu = 'woocommerce';
			} else {
				$show_in_menu = true;
			}

			register_post_type('wishlist', array(
			    'labels' => array(
				'name' => __('Wishlists', 'wc_wishlist'),
				'singular_name' => __('Wishlist', 'wc_wishlist'),
				'add_new' => __('Add Wishlist', 'wc_wishlist'),
				'add_new_item' => __('Add New Wishlist', 'wc_wishlist'),
				'edit' => __('Edit', 'woocommerce'),
				'edit_item' => __('Edit Wishlist', 'wc_wishlist'),
				'new_item' => __('New Wishlist', 'wc_wishlist'),
				'view' => __('View Wishlist', 'wc_wishlist'),
				'view_item' => __('View Wishlist', 'wc_wishlist'),
				'search_items' => __('Search Wishlists', 'wc_wishlist'),
				'not_found' => __('No Wishlists found', 'wc_wishlist'),
				'not_found_in_trash' => __('No Wishlists found in trash', 'wc_wishlist'),
				'parent' => __('Parent Wishlists', 'wc_wishlist')
			    ),
			    'description' => __('This is where customer wishlists are stored.', 'wc_wishlist'),
			    'show_ui' => true,
			    'capability_type' => 'post',
			    'capabilities' => array(
				'publish_posts' => 'manage_woocommerce',
				'edit_posts' => 'manage_woocommerce',
				'edit_others_posts' => 'manage_woocommerce',
				'delete_posts' => 'manage_woocommerce',
				'delete_others_posts' => 'manage_woocommerce',
				'read_private_posts' => 'manage_woocommerce',
				'edit_post' => 'manage_woocommerce',
				'delete_post' => 'manage_woocommerce',
				'read_post' => 'manage_woocommerce'
			    ),
			    'public' => false,
			    'publicly_queryable' => true,
			    'exclude_from_search' => false,
			    'hierarchical' => false,
			    'rewrite' => false,
			    'query_var' => false,
			    'supports' => array('title', 'editor'),
			    'has_archive' => true,
			    'show_in_nav_menus' => false,
			    'menu_icon' => self::plugin_url() . '/assets/images/wishlist-settings-icon.png',
			    'show_in_menu' => $show_in_menu
				)
			);
		}

		public function post_link($url, $post) {
			if ($post->post_type == 'wishlist') {
				$url = WC_Wishlists_Pages::get_url_for('view-a-list') . '?wlid=' . $post->id;
			}
			return $url;
		}

		public function post_type_archive_link($link, $post_type) {
			if ($post_type == 'wishlist') {
				$link = WC_Wishlists_Pages::get_url_for('wishlists');
			}
			return $link;
		}

		/** Helper functions ***************************************************** */
		/**
		 * Get the plugin url.
		 *
		 * @access public
		 * @return string
		 */
		public static function plugin_url() {
			return plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
		}

		/**
		 * Get the plugin path.
		 *
		 * @access public
		 * @return string
		 */
		public static function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		/** Nonces and Form Utitlity *************************************************************** */
		public static function action_field($action) {
			return '<input type="hidden" name="wlaction" value="' . esc_attr($action) . '" />';
		}

		/**
		 * Return a nonce field.
		 *
		 * @access public
		 * @param mixed $action
		 * @param bool $referer (default: true)
		 * @param bool $echo (default: true)
		 * @return void
		 */
		static function nonce_field($action, $referer = true, $echo = true) {
			return wp_nonce_field('wc-wishlists-' . $action, '_n', $referer, $echo);
		}

		/**
		 * Return a url with a nonce appended.
		 *
		 * @access public
		 * @param mixed $action
		 * @param string $url (default: '')
		 * @return string
		 */
		static function nonce_url($action, $url = '') {
			return add_query_arg('_n', wp_create_nonce('wc-wishlists-' . $action), $url);
		}

		/**
		 * Check a nonce and sets woocommerce error in case it is invalid.
		 *
		 * To fail silently, set the error_message to an empty string
		 *
		 * @access public
		 * @param string $name the nonce name
		 * @param string $action then nonce action
		 * @param string $method the http request method _POST, _GET or _REQUEST
		 * @param string $error_message custom error message, or false for default message, or an empty string to fail silently
		 * @return bool
		 */
		static function verify_nonce($action, $method = '_POST', $error_message = false) {
			global $woocommerce;

			$name = '_n';
			$action = 'wc-wishlists-' . $action;

			if ($error_message === false)
				$error_message = __('Action failed. Please refresh the page and retry.', 'wc-wishlists');

			if (!in_array($method, array('_GET', '_POST', '_REQUEST'))) {
				$method = '_POST';
			}

			if (isset($_REQUEST[$name]) && wp_verify_nonce($_REQUEST[$name], $action)) {
				return true;
			}

			if ($error_message) {
				$woocommerce->add_error($error_message);
			}

			return false;
		}

		public function add_session_message() {
			global $woocommerce;
			$session_items = WC_Wishlists_Wishlist_Item_Collection::get_items_from_session();
			if (!is_page(WC_Wishlists_Pages::get_page_id('create-a-list'))) {
				if ($session_items && count($session_items)) {
					$woocommerce->add_message(sprintf(__('You have %s items ready to move to a new list.  <a href="%s">Create a list</a>', 'wc_wishlist'), count($session_items), WC_Wishlists_Pages::get_url_for('create-a-list')));
				}
			} else {
				if ($session_items && count($session_items)) {
					$woocommerce->add_message(sprintf(__('%s items ready to move to a new list.', 'wc_wishlist'), count($session_items), WC_Wishlists_Pages::get_url_for('create-a-list')));
				}
			}
		}

		/** Body Classes ********************************************************* */
		public function wp_head() {
			global $woocommerce, $post;
			if ($post) {
				if ((WC_Wishlists_Pages::is_wishlist_page($post->post_name) || is_product())) {

					if (WC_Wishlists_Settings::get_setting('wc_wishlists_use_custom_button_colors', 'no') == 'yes') {

						$colors = array_map('esc_attr', (array) get_option('wishlist_frontend_css_colors'));
						// Defaults
						if (empty($colors['primary'])) {
							$colors['primary'] = '#f7f6f7';
						}

						if (empty($colors['link'])) {
							$colors['link'] = '#fff';
						}

						$font = woocommerce_format_hex($colors['link']);
						$a = woocommerce_format_hex($colors['primary']);
						$b = woocommerce_format_hex($this->adjustBrightness($colors['primary'], -35));
						$c = woocommerce_format_hex($this->adjustBrightness($colors['primary'], -50));
						?>
						<style type="text/css">
							#wl-wrapper .wl-add-but {
								background: <?php echo $b; ?>;
								background: -webkit-gradient(linear,left top,left bottom,from(<?php echo $a; ?>),to(<?php echo $b; ?>));
								background: -webkit-linear-gradient(<?php echo $a; ?>,<?php echo $b; ?>);
								background: -moz-linear-gradient(center top,<?php echo $a; ?> 0%,<?php echo $b; ?> 100%);
								background: -moz-gradient(center top,<?php echo $a; ?> 0%,<?php echo $b; ?> 100%);
								border-color: <?php echo $c; ?>;
								color: <?php echo $font; ?>;
								text-shadow: 0 -1px 0 rgba(0,0,0,0.6);
							}

							#wl-wrapper .wl-add-but:hover {
								background: <?php echo $c; ?>;
								background: -webkit-gradient(linear,left top,left bottom,from(<?php echo $c; ?>),to(<?php echo $c; ?>));
								background: -webkit-linear-gradient(<?php echo $a; ?>,<?php echo $c; ?>);
								background: -moz-linear-gradient(center top,<?php echo $a; ?> 0%,<?php echo $c; ?> 100%);
								background: -moz-gradient(center top,<?php echo $a; ?> 0%,<?php echo $c; ?> 100%);
								border-color: <?php echo $c; ?>;
								color: #ffffff;
								text-shadow: 0 -1px 0 rgba(0,0,0,0.6);
							}

						</style>
						<?php
					} //end is custom colors enabled

					if (WC_Wishlists_Settings::get_setting('wc_wishlist_custom_css')) {
						echo '<style type="text/css">' . WC_Wishlists_Settings::get_setting('wc_wishlist_custom_css') . '</style>';
					}

					if (isset($_REQUEST['wlid']) && !empty($_REQUEST['wlid'])) {
						$wishlist = new WC_Wishlists_Wishlist($_GET['wlid']);
						$maybe_image_url = WC_Wishlists_Wishlist_Item_Collection::get_first_image($wishlist->id);

						$is_users_list = $wishlist->get_wishlist_owner() == WC_Wishlists_User::get_wishlist_key();
						$fb_message = $is_users_list ? __('Check out my wishlist at ', 'wc_wishlist') . get_bloginfo( 'name' ) . ' ' : __('Found an interesting list of products at ', 'wc_wishlist') . get_bloginfo( 'name' ) . ' ';

						if ($maybe_image_url) {
							echo '<meta property="og:type" content="blog"/>';
							echo '<meta property="og:title" content="' . $fb_message . '"/>';
							echo '<meta property="og:image" content="' . $maybe_image_url . '"/>';
						}
					}
				}
			}
		}

		function adjustBrightness($hex, $steps) {
			// Steps should be between -255 and 255. Negative = darker, positive = lighter
			$steps = max(-255, min(255, $steps));

			// Format the hex color string
			$hex = str_replace('#', '', $hex);
			if (strlen($hex) == 3) {
				$hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
			}

			// Get decimal values
			$r = hexdec(substr($hex, 0, 2));
			$g = hexdec(substr($hex, 2, 2));
			$b = hexdec(substr($hex, 4, 2));

			// Adjust number of steps and keep it inside 0 to 255
			$r = max(0, min(255, $r + $steps));
			$g = max(0, min(255, $g + $steps));
			$b = max(0, min(255, $b + $steps));

			$r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
			$g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
			$b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

			return '#' . $r_hex . $g_hex . $b_hex;
		}

	}

	$GLOBALS['wishlists'] = new WC_Wishlists_Plugin();
}