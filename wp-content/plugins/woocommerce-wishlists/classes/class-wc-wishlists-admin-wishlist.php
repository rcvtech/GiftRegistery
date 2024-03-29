<?php
class WC_Wishlists_Wishlist_Admin {
	public static $instance;

	public static function instance() {
		if (!self::$instance) {
			$instance = new WC_Wishlists_Wishlist_Admin();
		}

		return $instance;
	}

	public function __construct() {

		add_filter('manage_edit-wishlist_columns', array(&$this, 'add_columns'));
		add_filter('manage_edit-wishlist_sortable_columns', array(&$this, 'sortable_columns'));
		add_action('manage_wishlist_posts_custom_column', array(&$this, 'render_columns'), 10, 2);

		add_action('restrict_manage_posts', array($this, 'custom_filters'));

		add_action('load-edit.php', array(&$this, 'edit_wishlist_load'));

		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'), 99);

		add_action('delete_post', array(&$this, 'on_delete_post'));
		add_action('save_post', array(&$this, 'on_save_post'), 10, 2);

		add_action('add_meta_boxes', array(&$this, 'add_metaboxes'));

		add_filter('post_updated_messages', array(&$this, 'updated_messages'));
	}

	public function edit_wishlist_load() {
		add_filter('request', array(&$this, 'filter_sort_request'));
		add_filter('pre_get_posts', array($this, 'custom_filters_parse_query'), 99);
		add_filter('posts_where', array($this, 'custom_search_where'), 99, 2);
		add_filter('posts_join', array($this, 'custom_search_join'), 99, 2);
		add_filter('posts_groupby', array($this, 'custom_search_group'), 99, 2);
	}

	public function enqueue_scripts() {
		global $woocommerce, $pagenow, $post, $wp_query;

		$screen = get_current_screen();
		if (in_array($screen->id, array('edit-wishlist', 'wishlist'))) {
			wp_enqueue_style('woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css');
			wp_enqueue_style('woocommerce-wishlists-admin', WC_Wishlists_Plugin::plugin_url() . '/assets/css/woocommerce-wishlists-admin.css');

			if ($post) {

				wp_enqueue_script('jquery-blockui');
				wp_enqueue_script('woocommerce_admin');
				wp_enqueue_script('woocommerce-wishlists-admin', WC_Wishlists_Plugin::plugin_url() . '/assets/js/woocommerce-wishlists-admin.js', array('jquery'));

				$params = array(
				    'wishlist_item_nonce' => wp_create_nonce("wishlist-item"),
				    'remove_item_notice' => __('Are you sure you want to remove the selected items?', 'wc_wishlist'),
				    'i18n_select_items' => __('Please select some items.', 'woocommerce'),
				    'wc_plugin_url' => $woocommerce->plugin_url(),
				    'plugin_url' => WC_Wishlists_Plugin::plugin_url(),
				    'ajax_url' => admin_url('admin-ajax.php'),
				    'post_id' => $post->ID
				);

				wp_localize_script('woocommerce-wishlists-admin', 'woocommerce_wishlist_writepanel_params', $params);
			}
		}
	}

	public function filter_sort_request($vars) {
		if (isset($vars['post_type']) && $vars['post_type'] == 'wishlist') {

			if (isset($vars['orderby']) && $vars['orderby'] == '_wishlist_sharing') {

				$vars = array_merge(
					$vars, array('meta_key' => '_wishlist_sharing', 'orderby' => 'meta_value')
				);
			}
		}

		return $vars;
	}

	public function custom_filters() {
		global $typenow, $wp_query, $wpdb;

		if ($typenow == 'wishlist') {
			$wishlist_type = isset($_REQUEST['wishlist_status']) ? $_REQUEST['wishlist_status'] : '';
			$wishlist_sharing = isset($_REQUEST['wishlist_sharing']) ? $_REQUEST['wishlist_sharing'] : '';
			?>

			<select name="wishlist_status" id="wishlist_type">
				<option value="">Show All Types</option>
				<option <?php selected($wishlist_type, 'active'); ?> value="active">Permanent</option>
				<option <?php selected($wishlist_type, 'temporary'); ?> value="temporary">Temporary List</option>
			</select>

			<select name="wishlist_sharing" id="wishlist_sharing">
				<option value="">Show All Sharing Types</option>
				<option <?php selected($wishlist_sharing, 'Public'); ?> value="Public">Public</option>
				<option <?php selected($wishlist_sharing, 'Shared'); ?> value="Shared">Shared</option>
				<option <?php selected($wishlist_sharing, 'Private'); ?> value="Private">Private</option>
			</select>

			<?php
		}
	}

	public function custom_filters_parse_query($query) {
		global $pagenow, $wpdb;

		$q_vars = &$query->query_vars;
		if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == 'wishlist') {

			$include_filter = false;
			$sharing_search = false;
			if (isset($_REQUEST['wishlist_sharing']) && !empty($_REQUEST['wishlist_sharing'])) {
				$wishlist_sharing = $_REQUEST['wishlist_sharing'];

				$include1 = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts p 
						INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
							WHERE post_type = 'wishlist' AND meta_key = '_wishlist_sharing' AND meta_value = %s", $wishlist_sharing));

				$include_filter = empty($include1) ? array(-1) : $include1;
				$sharing_search = true;
			}

			if (isset($_REQUEST['wishlist_status']) && !empty($_REQUEST['wishlist_status'])) {
				$wishlist_status = $_REQUEST['wishlist_status'];

				$include2 = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts p 
						INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
							WHERE post_type = 'wishlist' AND meta_key = '_wishlist_status' AND meta_value = %s", $wishlist_status));


				if ($sharing_search) {
					$include2 = empty($include2) ? array(-1) : $include2;
					$include_filter = array_intersect($include_filter, $include2);
				} else {
					$include_filter = empty($include2) ? array(-1) : $include2;
				}
			}

			if ($include_filter !== false) {

				if (empty($include_filter)) {
					$include_filter = array(-1);
				}

				$query->query_vars['post__in'] = array_map('intval', $include_filter);
			}
		}

		return $query;
	}

	public function custom_search_where($where, $query) {
		global $wpdb;

		if (is_search() && $query->get('post_type') == 'wishlist') {
			// put the custom fields into an array
			$customs = array('_wishlist_first_name', '_wishlist_last_name', '_wishlist_email');
			$term = get_search_query();
			$term = strtolower($wpdb->prepare("'%%%s%%'", like_escape($term)));

			$query = '1 = 0';
			foreach ($customs as $custom) {
				$query .= " OR (";
				$query .= "(wlm.meta_key = '$custom')";
				$query .= " AND (LOWER(wlm.meta_value)  LIKE {$term})";
				$query .= ")";
			}

			$where = " AND ({$query}) AND ($wpdb->posts.post_status = 'publish') ";
		}

		return($where);
	}

	public function custom_search_join($join, $query) {
		global $wpdb;

		if (is_search() && $query->get('post_type') == 'wishlist') {
			$join .= " INNER JOIN $wpdb->postmeta wlm ON {$wpdb->posts}.ID = wlm.post_id";
		}

		return $join;
	}

	public function custom_search_group($groupby, $query) {
		global $wpdb;
		
		if (is_search() && $query->get('post_type') == 'wishlist') {

			$mygroupby = "{$wpdb->posts}.ID";

			if (preg_match("/$mygroupby/", $groupby)) {
				// grouping we need is already there
				return $groupby;
			}

			if (!strlen(trim($groupby))) {
				// groupby was empty, use ours
				return $mygroupby;
			}
			
			// wasn't empty, append ours
			return $groupby . ", " . $mygroupby;
		}
		
		return $group;
	}

	public function add_columns($columns) {

		$columns = array(
		    'cb' => '<input type="checkbox" />',
		    'title' => __('Title', 'wc_wishlist'),
		    'status' => __('Type', 'wc_wishlist'),
		    'sharing' => __('Sharing Status', 'wc_wishlist'),
		    'user' => __('User', 'wc_wishlist'),
		    'email' => __('Email on List', 'wc_wishlist'),
		    'name' => __('Name on List', 'wc_wishlist'),
		    'products' => __('Products', 'wc_wishlist'),
		    'date' => __('Date', 'wc_wishlist'), 
		);


		return $columns;
	}

	public function sortable_columns($columns) {
		$columns['status'] = '_wishlist_status';
		$columns['sharing'] = '_wishlist_sharing';
		return $columns;
	}

	public function render_columns($column, $post_id) {
		global $post;

		$data = array(
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

		switch ($column) {

			case 'sharing' :
				echo $data['wishlist_sharing'];
				break;
			case 'status' :
				echo ($data['wishlist_status'] == 'temporary' ? 'Temporary List' : 'Permanent');
				break;
			case 'email' :
				echo '<a href="mailto:' . $data['wishlist_owner_email'] . '">' . $data['wishlist_owner_email'] . '</a>';
				break;
			case 'name' :
				echo $data['wishlist_first_name'] . ' ' . $data['wishlist_last_name'];
				break;
			case 'products' :
				echo count($data['wishlist_items']);
				break;
			case 'user' : 
				if ($data['wishlist_status'] == 'active') {
					$user_id = (int)$data['wishlist_owner'];
					$user_info = get_userdata( $user_id );
					if ($user_info){
						printf('<a href="%s">%s</a>',  get_edit_user_link($user_id), $user_info->display_name);
					}else {
						echo ' - ';
					}
				}else {
					echo ' - ';
				}
				break;
			default:
				break;
		}
	}

	public function updated_messages($message) {
		global $post;
		$post_ID = $post->ID;

		$messages['wishlist'] = array(
		    0 => '', // Unused. Messages start at index 1.
		    1 => sprintf(__('Wishlist updated. <a href="%s">View Wishlist</a>'), esc_url(WC_Wishlists_Wishlist::get_the_url_view($post_ID))),
		    2 => __('Custom field updated.'),
		    3 => __('Custom field deleted.'),
		    4 => __('Wishlist updated.'),
		    /* translators: %s: date and time of the revision */
		    5 => isset($_GET['revision']) ? sprintf(__('Wishlist restored to revision from %s'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
		    6 => sprintf(__('Wishlist published. <a href="%s">View Wishlist</a>'), esc_url(WC_Wishlists_Wishlist::get_the_url_view($post_ID))),
		    7 => __('Wishlist saved.'),
		    8 => sprintf(__('Wishlist submitted. <a target="_blank" href="%s">Preview Wishlist</a>'), esc_url(add_query_arg('preview', 'true', WC_Wishlists_Wishlist::get_the_url_view($post_ID)))),
		    9 => sprintf(__('Wishlist scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Wishlist</a>'),
			    // translators: Publish box date format, see http://php.net/date
			    date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(WC_Wishlists_Wishlist::get_the_url_view($post_ID))),
		    10 => sprintf(__('Wishlist draft updated. <a target="_blank" href="%s">Preview Wishlist</a>'), esc_url(add_query_arg('preview', 'true', WC_Wishlists_Wishlist::get_the_url_view($post_ID)))),
		);

		return $messages;
	}

	public function on_delete_post($id) {

		$key = WC_Wishlists_User::get_wishlist_key() . '_wishlist_products';
		unset($_SESSION[$key]);

		do_action('wc_wishlists_deleted', $id);
	}

	public function on_save_post($post_id, $post) {
		if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return;
		}

		if ($post->post_type != 'wishlist') {
			return $post_id;
		}

		if (!WC_Wishlists_Plugin::verify_nonce('wishlist-action')) {
			return $post_id;
		}

		$args = $_POST;

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

		update_post_meta($post_id, '_wishlist_sharing', $args['wishlist_sharing']);
		update_post_meta($post_id, '_wishlist_type', $args['wishlist_type']);

		update_post_meta($post_id, '_wishlist_email', $args['wishlist_owner_email']);
		update_post_meta($post_id, '_wishlist_owner_notifications', $args['wishlist_owner_notifications']);

		update_post_meta($post_id, '_wishlist_first_name', $args['wishlist_first_name']);
		update_post_meta($post_id, '_wishlist_last_name', $args['wishlist_last_name']);

		update_post_meta($post_id, '_wishlist_subscribers', apply_filters('wc_wishlists_update_subscribers', $args['wishlist_subscribers'], $post_id));
		update_post_meta($post_id, '_wishlist_items', apply_filters('wc_wishlists_update_items', $args['wishlist_items'], $post_id));

		do_action('wc_wishlists_updated', $post_id, $args);


		if (isset($args['wishlist_item_quantity']) && count($args['wishlist_item_quantity'])) {

			foreach ($args['wishlist_item_quantity'] as $item_id => $quantity) {
				WC_Wishlists_Wishlist_Item_Collection::update_item_quantity($post_id, $item_id, $quantity);
			}
		}
	}

	function add_metaboxes() {
		add_meta_box('wc_wishlists_viewing', __('View Wishlist', 'wc_wishlist'), array(&$this, 'viewing_metabox'), 'wishlist', 'side', 'high');
		add_meta_box('wc_wishlists_items', __('Items', 'wc_wishlist'), array(&$this, 'items_metabox'), 'wishlist', 'advanced', 'default');
		add_meta_box('wc_wishlists_nameinfo', __('Name and Information', 'wc_wishlist'), array(&$this, 'nameinfo_metabox'), 'wishlist', 'advanced', 'default');
		add_meta_box('wc_wishlists_sharing', __('Sharing', 'wc_wishlist'), array(&$this, 'sharing_metabox'), 'wishlist', 'advanced', 'default');
	}

	public function viewing_metabox($post) {
		$wishlist = new WC_Wishlists_Wishlist($post->ID);
		$sharing = $wishlist->get_wishlist_sharing();
		?>

		<div class="wl-admin-wrapper">

			<ul>
				<li>
					<a href="<?php echo $wishlist->get_the_url_view($post->ID); ?>" target="_blank"><?php _e('Preview', 'wc_wishlist'); ?></a>
				</li>
				<li>
					<a href="<?php echo $wishlist->get_the_url_edit($post->ID); ?>" target="_blank"><?php _e('Manage List', 'wc_wishlist'); ?></a>
				</li>
			</ul>

		</div>

		<?php
	}

	public function sharing_metabox($post) {
		$wishlist = new WC_Wishlists_Wishlist($post->ID);
		$sharing = $wishlist->get_wishlist_sharing();
		?>
		<?php echo WC_Wishlists_Plugin::nonce_field('wishlist-action'); ?>

		<div class="wl-admin-wrapper">
			<p class="form-row">
				<strong><?php _e('Privacy Settings', 'wc_wishlist'); ?></strong>
			<table class="wl-rad-table">
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_pub" value="Public" <?php checked('Public', $sharing); ?>></td>
					<td><label for="rad_pub"><?php _e('Public', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Anyone can search for and see this list. You can also share using a link.', 'wc_wishlist'); ?></span></label></td>
				</tr>
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_shared" value="Shared" <?php checked('Shared', $sharing); ?>></td>
					<td><label for="rad_shared"><?php _e('Shared', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Only people with the link can see this list. It will not appear in public search results.', 'wc_wishlist'); ?></span></label></td>
				</tr>
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_priv" value="Private" <?php checked('Private', $sharing); ?>></td>
					<td><label for="rad_priv"><?php _e('Private', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Only you can see this list.', 'wc_wishlist'); ?></span></label></td>
				</tr>
			</table>
		</p>  
		</div>
		<?php
	}

	public function nameinfo_metabox($post) {
		$wishlist = new WC_Wishlists_Wishlist($post->ID);
		?>
		<div class="wl-admin-wrapper">
			<p class="no-marg"><?php _e('Enter a name you would like associated with this list.  If your list is public, users can find it by searching for this name.', 'wc_wishlist'); ?></p>
			<p class="form-row form-row-half">
				<label for="wishlist_first_name"><?php _e('First Name', 'wc_wishlist'); ?></label>
				<input type="text" name="wishlist_first_name" id="wishlist_first_name" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_first_name', true)); ?>" />
			</p>
			<p class="form-row form-row-half">
				<label for="wishlist_first_name"><?php _e('Last Name', 'wc_wishlist'); ?></label>
				<input type="text" name="wishlist_last_name" id="wishlist_last_name" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_last_name', true)); ?>" />
			</p>
			<p class="form-row form-row-full">
				<label for="wishlist_owner_email"><?php _e('Email Assoicated with the List', 'wc_wishlist'); ?></label>
				<input type="text" name="wishlist_owner_email" id="wishlist_owner_email" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_email', true)); ?>" />
			</p>
		</div>
		<?php
	}

	public function items_metabox($post) {
		global $woocommerce;
		include_once $woocommerce->plugin_path() . '/classes/class-wc-cart.php';

		$cart = new WC_Cart();

		$wishlist = new WC_Wishlists_Wishlist($post->ID);


		$current_owner_key = WC_Wishlists_User::get_wishlist_key();
		$sharing = $wishlist->get_wishlist_sharing();
		$sharing_key = $wishlist->get_wishlist_sharing_key();
		$wl_owner = $wishlist->get_wishlist_owner();

		$wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items($post->ID);

		$treat_as_registry = false;
		?>
		<div class="wl-admin-wrapper">
			<div id="woocommerce-wishlist-items" class="woocommerce_order_items_wrapper">
				<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
					<thead>
						<tr>
							<th><input type="checkbox" class="check-column" style="width:auto;" /></th>
							<th class="item" colspan="2" style=""><?php _e('Item', 'woocommerce'); ?></th>
							<th class="quantity"><?php _e('Qty', 'woocommerce'); ?></th>
							<th class="line_cost"><?php _e('Cost', 'woocommerce'); ?>&nbsp;</th>
						</tr>
					</thead>
					<tbody id="order_items_list">
						<?php
						foreach ($wishlist_items as $item_id => $item) {
							$_product = $item['data'];
							if ($_product->exists() && $item['quantity'] > 0) {
								?>
								<tr class="item <?php if (!empty($class)) echo $class; ?>" data-order_item_id="<?php echo $item_id; ?>">
									<td class="check-column" >
										<input type="checkbox" name="wlitem[]" value="<?php echo $item_id; ?>" style="width:auto;" />
									</td>
									<td class="thumb" style="text-align:left;">
										<a href="<?php echo esc_url(admin_url('post.php?post=' . absint($_product->id) . '&action=edit')); ?>" class="tips" data-tip="<?php
				echo '<strong>' . __('Product ID:', 'woocommerce') . '</strong> ' . absint($item['product_id']);

				if ($item['variation_id']) :
					echo '<br/><strong>' . __('Variation ID:', 'woocommerce') . '</strong> ' . absint($item['variation_id']);
				endif;

				if ($_product->get_sku()) :
					echo '<br/><strong>' . __('Product SKU:', 'woocommerce') . '</strong> ' . esc_html($_product->get_sku());
				endif;
								?>"><?php echo $_product->get_image('shop_thumbnail', array('title' => '')); ?></a>
									</td>
									<td class="name" style="text-align:left;">

										<?php if ($_product->get_sku()) echo esc_html($_product->get_sku()) . ' &ndash; '; ?>

										<a target="_blank" href="<?php echo esc_url(admin_url('post.php?post=' . absint($_product->id) . '&action=edit')); ?>"><?php printf('<a href="%s">%s</a>', esc_url(get_permalink(apply_filters('woocommerce_in_cart_product_id', $item['product_id']))), $_product->get_title()); ?></a>
										<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr($item_id); ?>" />

										<?php
										if (isset($_product->variation_data)) :
											echo '<br/>' . woocommerce_get_formatted_variation($_product->variation_data, true);
										endif;
										?>
									</td>
									<!-- Quantity inputs -->
									<td class="quantity" width="1%">
										<input type="number" step="<?php echo apply_filters('woocommerce_quantity_input_step', '1', $_product); ?>" min="0" autocomplete="off" name="wishlist_item_quantity[<?php echo $item_id; ?>]" placeholder="0" value="<?php echo esc_attr($item['quantity']); ?>" size="4" class="quantity" />
									</td>
									<!-- Product price -->
									<td class="line_cost" width="1%">
										<?php
										$product_price = ( get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes' ) ? $_product->get_price_excluding_tax() : $_product->get_price();
										echo apply_filters('woocommerce_cart_item_price_html', woocommerce_price($product_price), $item, $item_id);
										?>
									</td>
								</tr>
								<?php
							}
						}
						?> 
					</tbody>
				</table>		
			</div>

			<p class="wl_bulk_actions">
				<select>
					<option value=""><?php _e('Actions', 'woocommerce'); ?></option>
					<optgroup label="<?php _e('Edit', 'woocommerce'); ?>">
						<option value="delete"><?php _e('Delete Lines', 'woocommerce'); ?></option>
					</optgroup>
				</select>

				<button type="button" class="button do_wl_bulk_action wc-reload" title="<?php _e('Apply', 'woocommerce'); ?>"><span><?php _e('Apply', 'woocommerce'); ?></span></button>
			</p>
		</div>
		<?php
	}

}
?>
