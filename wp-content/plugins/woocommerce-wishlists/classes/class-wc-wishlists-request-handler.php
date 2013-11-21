<?php

class WC_Wishlists_Request_Handler {
	public static function process_request() {

		if (isset($_REQUEST['wlaction'])) {


			$action = $_REQUEST['wlaction'];

			WC_Wishlists_Plugin::verify_nonce($action);

			$result = false;

			switch ($action) {
				case 'create-list':
					$result = self::create_list();
					break;
				case 'delete-list':
					$result = self::delete_list();
					break;
				case 'edit-list':
					$result = self::edit_list();
					break;
				case 'edit-lists':
					$result = self::edit_lists();
					break;
				case 'wishlists-add-to-list':
					$result = self::add_to_list();
					break;
				case 'wishlists-remove-from-list':
					$result = self::remove_from_list();
					break;
				case 'manage-list':
					if ($_REQUEST['wlupdateaction'] == 'quantity') {
						$result = self::bulk_update_action();
					} else {
						$result = self::bulk_edit_action();
					}
					break;
				case 'add-cart-item' :
					$result = self::add_to_cart();
					break;
				case 'add-cart-items' :
					$result = self::add_all_to_cart();
					break;
			}

			if ($result !== false) {
				if ($result !== true) {
					wp_redirect(add_query_arg(array('wlm' => uniqid()), $result));
					die();
				}
			}
		}
	}

	public static function last_updated_class($list_id) {
		global $woocommerce;
		$last_updated = $woocommerce->session->wishlists_recently_modified;
		if ($last_updated && in_array($list_id, $last_updated)) {
			return 'flash-update';
		}
	}

	private static function create_list() {
		global $woocommerce;
		$args = $_POST;
		$args['wishlist_owner'] = WC_Wishlists_User::get_wishlist_key();

		$result = WC_Wishlists_Wishlist::create_list($_POST['wishlist_title'], $args);

		if ($result) {
			$session_items = WC_Wishlists_Wishlist_Item_Collection::get_items_from_session();
			if ($session_items && count($session_items)) {
				$moved = 0;
				foreach ($session_items as $wishlist_item_key => $session_data) {
					$moved += WC_Wishlists_Wishlist_Item_Collection::move_item_to_list_from_session($result, $wishlist_item_key);
				}
			}

			if ($moved) {
				$woocommerce->add_message(sprintf(__('Wishlist successfully created. %s items moved', 'wc_wishlist'), $moved));
			} else {
				$woocommerce->add_message(__('Wishlist successfully created', 'wc_wishlist'));
			}
			return WC_Wishlists_Wishlist::get_the_url_edit($result);
		}
	}

	private static function delete_list() {
		global $woocommerce;
		$post_id = isset($_REQUEST['wlid']) ? $_REQUEST['wlid'] : 0;
		$post = get_post($post_id);

		if (!$post || !$post_id || !$post->post_type == 'wishlist') {
			wp_die(__('Unable to locate wishlist', 'wc_wishlist'));
		}

		$wishlist = new WC_Wishlists_Wishlist($post_id);
		if (!is_admin()) {
			$wl_owner = $wishlist->get_wishlist_owner();
			$current_owner_key = WC_Wishlists_User::get_wishlist_key();

			if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
				wp_die(__('You can only manage your own lists', 'wc_wishlist'));
			}
		}

		$result = WC_Wishlists_Wishlist::delete_list($post_id);
		if ($result) {
			$woocommerce->add_message(__('Wishlist successfully deleted', 'wc_wishlist'));
		} else {
			
		}

		return WC_Wishlists_Pages::get_url_for('my-lists');
	}

	private static function edit_list() {
		global $woocommerce;
		$post_id = isset($_POST['wlid']) ? $_POST['wlid'] : 0;
		$post = get_post($post_id);

		if (!$post_id || !$post->post_type == 'wishlist') {
			wp_die(__('Unable to locate wishlist for updating', 'wc_wishlist'));
		}

		$wishlist = new WC_Wishlists_Wishlist($post_id);
		if (current_user_can('manage_woocommerce')) {
			$wl_owner = $wishlist->get_wishlist_owner();
			$current_owner_key = WC_Wishlists_User::get_wishlist_key();

			if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
				wp_die(__('You can only update your own lists', 'wc_wishlist'));
			}
		}


		$args = $_POST;
		$result = WC_Wishlists_Wishlist::update_list($post_id, $args);
		if ($result) {
			$woocommerce->add_message(__('Wishlist successfully updated', 'wc_wishlist'));
		} else {
			
		}

		return WC_Wishlists_Wishlist::get_the_url_edit($result) . '#tab-wl-settings';
	}

	private static function edit_lists() {
		global $woocommerce;
		$result = true;

		$listids = isset($_POST['sharing']) ? $_POST['sharing'] : false;
		if (!$listids) {
			return;
		}

		foreach ($listids as $id => $sharing) {
			$wishlist = new WC_Wishlists_Wishlist($id);

			$wl_owner = WC_Wishlists_Wishlist::get_the_wishlist_owner($wishlist_id);
			$current_owner_key = WC_Wishlists_User::get_wishlist_key();

			if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
				wp_die(__('You can only update your own lists', 'wc_wishlist'));
			}

			$result &= (bool) WC_Wishlists_Wishlist::update_list($id, array('wishlist_sharing' => $sharing));
		}

		if ($result) {
			$woocommerce->add_message(__('Lists successfullly updated', 'wc_wishlist'));
		} else {
			$woocommerce - add_error(__('There was an error updating your lists.  Please refresh the page and try again.', 'wc_wishlist'));
		}

		return WC_Wishlists_pages::get_url_for('my-lists');
	}

	private static function add_to_list() {
		
	}

	private static function remove_from_list() {
		global $woocommerce;

		$wishlist_id = isset($_REQUEST['wlid']) ? $_REQUEST['wlid'] : false;
		$wishlist_item_key = isset($_REQUEST['wishlist-item-key']) ? $_REQUEST['wishlist-item-key'] : false;



		if (!$wishlist_id || !$wishlist_item_key) {
			$woocommerce->add_error(__('Unable to remove item.  Please try again', 'wc_wishlist'));
			return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
		}

		$wl_owner = WC_Wishlists_Wishlist::get_the_wishlist_owner($wishlist_id);
		$current_owner_key = WC_Wishlists_User::get_wishlist_key();

		if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
			wp_die(__('You can only update your own lists', 'wc_wishlist'));
		}

		$result = WC_Wishlists_Wishlist_Item_Collection::remove_item($wishlist_id, $wishlist_item_key);

		if ($result) {
			$woocommerce->add_message(__('Item removed from your list', 'wc_wishlist'));
		}

		return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
	}

	private static function bulk_edit_action() {
		global $woocommerce;

		$wishlist_id = isset($_REQUEST['wlid']) ? $_REQUEST['wlid'] : false;
		if (!$wishlist_id) {
			$woocommerce->add_error(__('Unable to edit list.  Please try again', 'wc_wishlist'));
			return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
		}

		$bulk_action = isset($_REQUEST['wlupdateaction']) ? $_REQUEST['wlupdateaction'] : false;
		if (!$bulk_action) {
			return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
		}

		$wl_owner = WC_Wishlists_Wishlist::get_the_wishlist_owner($wishlist_id);
		$current_owner_key = WC_Wishlists_User::get_wishlist_key();

		if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
			wp_die(__('You can only update your own lists', 'wc_wishlist'));
		}

		if ($bulk_action == 'remove') {
			$items = isset($_REQUEST['wlitem']) ? $_REQUEST['wlitem'] : array();
			$result = 0;
			foreach ($items as $wishlist_item_key) {
				$result += WC_Wishlists_Wishlist_Item_Collection::remove_item($wishlist_id, $wishlist_item_key);
			}

			if ($result) {
				$woocommerce->add_message(sprintf(__('%s Items removed from your list', 'wc_wishlist'), $result));
			} else {
				$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
			}
		} elseif ($bulk_action == 'create') {

			$items = isset($_REQUEST['wlitem']) ? $_REQUEST['wlitem'] : array();
			$result = 0;
			foreach ($items as $wishlist_item_key) {
				$result += WC_Wishlists_Wishlist_Item_Collection::move_item_to_session($wishlist_id, $wishlist_item_key);
			}

			if ($result) {
				$session_items = WC_Wishlists_Wishlist_Item_Collection::get_items_from_session();
				//$woocommerce->add_message(sprintf(__('%s Items ready to move to a new list', 'wc_wishlist'), count($session_items) ));
			} else {
				$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
			}

			return WC_Wishlists_Pages::get_url_for('create-a-list');
		} elseif ($bulk_action == 'add-to-cart') {
			$items = isset($_REQUEST['wlitem']) ? $_REQUEST['wlitem'] : array();
			$result = 0;

			foreach ($items as $wishlist_item_key) {
				$result += (self::add_to_cart($wishlist_id, $wishlist_item_key, true) !== false);
			}

			if ($result) {
				$woocommerce->add_message(sprintf(__('%s have been added to the cart', 'wc_wishlist'), $result));
			} else {
				$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
			}

			return self::get_add_to_cart_redirect_url($wishlist_id);
		} else {
			$destination_id = $bulk_action;
			$destination_list = new WC_Wishlists_Wishlist($destination_id);

			if ($destination_list->get_wishlist_owner() == WC_Wishlists_User::get_wishlist_key()) {
				$items = isset($_REQUEST['wlitem']) ? $_REQUEST['wlitem'] : array();
				$result = 0;
				foreach ($items as $wishlist_item_key) {
					$result += WC_Wishlists_Wishlist_Item_Collection::move_item($wishlist_id, $destination_id, $wishlist_item_key);
				}

				if ($result) {
					$woocommerce->add_message(sprintf(__('%s Items successfully moved to %s <a class="button" href="%s">Edit List</a>', 'wc_wishlist'), $result, esc_html(get_the_title($destination_id)), WC_Wishlists_Wishlist::get_the_url_edit($destination_id)));
				} else {
					$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
				}
			}
		}


		return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
	}

	private static function bulk_update_action() {
		global $woocommerce;

		$wishlist_id = isset($_REQUEST['wlid']) ? $_REQUEST['wlid'] : false;
		if (!$wishlist_id) {
			$woocommerce->add_error(__('Unable to edit list.  Please try again', 'wc_wishlist'));
			return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
		}

		$wl_owner = WC_Wishlists_Wishlist::get_the_wishlist_owner($wishlist_id);
		$current_owner_key = WC_Wishlists_User::get_wishlist_key();

		if ($wl_owner != $current_owner_key && !current_user_can('manage_woocommerce')) {
			wp_die(__('You can only update your own lists', 'wc_wishlist'));
		}

		$result = false;
		if (isset($_POST['cart'])) {

			$items = isset($_REQUEST['wlitem']) ? $_REQUEST['wlitem'] : array();
			$result = 0;

			foreach ($items as $key) {
				if (isset($_POST['cart'][$key])) {
					$data = $_POST['cart'][$key];
					$result += WC_Wishlists_Wishlist_Item_Collection::update_item_quantity($wishlist_id, $key, $data['qty']);
				}
			}
		}

		if ($result) {
			$woocommerce->add_message(sprintf(__('%s Items updated', 'wc_wishlist'), $result));
		} else {
			$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
		}

		return WC_Wishlists_Wishlist::get_the_url_edit($wishlist_id);
	}

	private static function add_all_to_cart() {
		global $woocommerce;
		$wishlist_id = filter_input(INPUT_GET, 'wlid', FILTER_SANITIZE_NUMBER_INT);
		$items = WC_Wishlists_Wishlist_Item_Collection::get_items($wishlist_id);
		if ($items) {
			$result = false;
			foreach ($items as $wishlist_item_key => $data) {
				$result += (self::add_to_cart($wishlist_id, $wishlist_item_key, true) !== false);
			}

			if ($result) {
				$woocommerce->add_message(sprintf(__('%s have been added to the cart', 'wc_wishlist'), $result));
			} else {
				$woocommerce->add_error(sprintf(__('Please select at least one item before applying an action', 'wc_wishlist'), $result));
			}
		}

		$url = WC_Wishlists_Wishlist::get_the_url_view($wishlist_id, true);
		if (isset($_GET['preview'])) {
			return add_query_arg(array('preview' => 'true'), $url);
		} else {
			return $url;
		}
	}

	private static function add_to_cart($wishlist_id = false, $wishlist_item_key = false, $suppress_messages = false) {
		global $woocommerce;
		$result = false;

		if (!$wishlist_id && !$wishlist_item_key) {
			$wishlist_id = filter_input(INPUT_GET, 'wlid', FILTER_SANITIZE_NUMBER_INT);
			$wishlist_item_key = isset($_GET['wishlist-item-key']) ? filter_input(INPUT_GET, 'wishlist-item-key', FILTER_SANITIZE_STRIPPED) : false;
		}

		if (!$wishlist_id) {
			$woocommerce->add_error(__('Action failed. Please refresh the page and retry.', 'woocommerce'));
			return;
		}

		$wishlist = new WC_Wishlists_Wishlist($wishlist_id);
		if (!$wishlist) {
			$woocommerce->add_error(__('Action failed. Please refresh the page and retry.', 'woocommerce'));
			return;
		}

		$wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items($wishlist->id);

		if ($wishlist_item_key) {
			if (sizeof($wishlist_items > 0) && isset($wishlist_items[$wishlist_item_key])) {

				$wishlist_item = $wishlist_items[$wishlist_item_key];
				if (isset($wishlist_item['wl_price'])) {
					unset($wishlist_item['wl_price']);
				}

				$core_keys = array('product_id', 'variation_id', 'variation', 'quantity', 'data', 'date');
				$add_on_data = array();
				$cart_item = array();

				foreach ($wishlist_item as $key => $value) {
					if (!in_array($key, $core_keys)) {
						$add_on_data[$key] = $value;
					} else {
						$cart_item[$key] = $value;
					}
				}

				$wishlist_prefix = WC_Wishlists_Settings::get_setting('wc_wishlists_cart_label', __('Wishlist', 'wc_wishlist'));

				$add_on_data['wishlist-data']['list'] = array(
				    'name' => $wishlist_prefix,
				    'value' => $wishlist->id,
				    'display' => get_the_title($wishlist->id),
				    'price' => false
				);

				$add_on_data['wishlist-data']['item'] = array(
				    'name' => false,
				    'value' => $wishlist_item_key,
				    'display' => false,
				    'price' => false
				);
				
				$add_on_data['wishlist-data']['customer'] = array(
				    'name' => false,
				    'value' => $wishlist->get_wishlist_owner(),
				    'display' => false,
				    'price' => false
				);

				$add_on_data = apply_filters('woocommerce_copy_cart_item_data', $add_on_data, (int) $wishlist_item['product_id'], $wishlist_item);
				$quantity = 1;

				if ($woocommerce->cart->add_to_cart((int) $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'], $add_on_data)) {

					if (!$suppress_messages) {
						$message = sprintf(__('Product successfully added to your cart.', 'woocommerce'));
						$woocommerce->add_message(apply_filters('woocommerce_add_to_cart_message', $message));
					}

					$result = self::get_add_to_cart_redirect_url($wishlist->id);
				} else {
					$woocommerce->add_error(__('Unable to add product to the cart. Please try again', 'wc_wishlist'));
					$result = false;
				}
			}
		}

		return $result;
	}

	private static function get_add_to_cart_redirect_url($wishlist_id) {
		global $wp_query;

		$wishlist = new WC_Wishlists_Wishlist($wishlist_id);


		if (is_page(WC_Wishlists_Pages::get_page_id('edit-my-list'))) {
			$w_url = WC_Wishlists_Wishlist::get_the_url_edit($wishlist->id);
		} else {
			$wishlist_sharing = $wishlist->get_wishlist_sharing();
			$w_url = '';
			if ($wishlist_sharing == 'Public') {
				$w_url = WC_Wishlists_Wishlist::get_the_url_view($wishlist->id);
			} elseif ($wishlist_sharing == 'Shared') {
				if (WC_Wishlists_User::get_wishlist_key() != $wishlist->get_wishlist_owner()) {
					$w_url = WC_Wishlists_Wishlist::get_the_url_view($wishlist->id, true);
				} else {
					$w_url = WC_Wishlists_Wishlist::get_the_url_view($wishlist->id);
				}
			} else {
				$w_url = WC_Wishlists_Wishlist::get_the_url_view($wishlist->id);
			}
		}

		$c_url = apply_filters('add_to_cart_from_wishlist_redirect_url', false);
		// If has custom URL redirect there
		if ($c_url) {
			$result = $c_url;
		} else {
			$result = $w_url;
		}

		return $result;
	}

}

?>