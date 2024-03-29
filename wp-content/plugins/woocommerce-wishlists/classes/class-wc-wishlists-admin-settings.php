<?php
class WC_Wishlists_Settings_Admin {
	public static $instance;

	public static function instance() {
		if (!self::$instance) {
			$instance = new WC_Wishlists_Settings_Admin();
		}

		return $instance;
	}

	public function __construct() {


		$this->current_tab = ( isset($_GET['tab']) ) ? $_GET['tab'] : 'general';
		$this->settings_tabs = array(
		    'wc_wishlists' => __('Wishlists', 'wc_wishlist')
		);

		add_action('woocommerce_settings_tabs', array(&$this, 'on_add_tab'), 10);

		// Run these actions when generating the settings tabs.
		foreach ($this->settings_tabs as $name => $label) {
			add_action('woocommerce_settings_tabs_' . $name, array(&$this, 'settings_tab_action'), 10);
			add_action('woocommerce_update_options_' . $name, array(&$this, 'save_settings'), 10);
		}

		add_action('woocommerce_update_options_wc_wishlists_main', array(&$this, 'save_settings'), 10);
		//add_action('woocommerce_update_options_wc_wishlists_email', array(&$this, 'save_settings'), 10);
		// Add the settings fields to each tab.
		add_action('woocommerce_wishlists_options_settings', array(&$this, 'add_settings_fields'), 10);
		add_action('woocommerce_admin_field_editor', array(&$this, 'on_editor_field'));

		add_action('woocommerce_admin_field_wishlist_styles', array(&$this, 'wishlist_styles_setting'));
	}

	/*
	 * Admin Functions
	 */

	/* ----------------------------------------------------------------------------------- */
	/* Admin Tabs */
	/* ----------------------------------------------------------------------------------- */
	function on_add_tab() {
		foreach ($this->settings_tabs as $name => $label) :
			$class = 'nav-tab';
			if ($this->current_tab == $name)
				$class .= ' nav-tab-active';
			echo '<a href="' . admin_url('admin.php?page=woocommerce&tab=' . $name) . '" class="' . $class . '">' . $label . '</a>';
		endforeach;
	}

	/**
	 * settings_tab_action()
	 *
	 * Do this when viewing our custom settings tab(s). One function for all tabs.
	 */
	function settings_tab_action() {
		global $woocommerce_settings;

		// Determine the current tab in effect.
		$current_tab = $this->get_tab_in_view(current_filter(), 'woocommerce_settings_tabs_');

		$current_section = ( empty($_REQUEST['section']) ) ? 'main' : sanitize_text_field(urldecode($_REQUEST['section']));


		$main_url = add_query_arg('section', 'main', admin_url('admin.php?page=woocommerce_settings&tab=wc_wishlists'));
		$email_url = add_query_arg('section', 'email', admin_url('admin.php?page=woocommerce_settings&tab=wc_wishlists'));

		/*
		 * Future place of the email alerts settings
		  $links = array(
		  'main' => '<a href="' . $main_url . '" class="#class#">' . __('Wishlist Options', 'wc_wishlist') . '</a>',
		  'email' => '<a href="' . $email_url . '" class="#class#">' . __('Wishlist Email Options', 'wc_wishlist') . '</a>'
		  );
		 */

		$links = array(
		    'main' => '<a href="' . $main_url . '" class="#class#">' . __('Wishlist Options', 'wc_wishlist') . '</a>'
		);

		foreach ($links as $section => &$link) {
			$current = ( $section == $current_section ) ? 'current' : '';
			$link = str_replace('#class#', $current, $link);
		}
		// echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode(' | </li><li>', $links) . '</li></ul><br class="clear" /></div>';

		do_action('woocommerce_wishlists_options_settings');
		// Display settings for this tab (make sure to add the settings to the tab).
		woocommerce_admin_fields($woocommerce_settings[$current_tab . '_' . $current_section]);
	}

	/**
	 * add_settings_fields()
	 *
	 * Add settings fields for each tab.
	 */
	function add_settings_fields() {
		global $woocommerce_settings;

		// Load the prepared form fields.
		$this->init_form_fields();
		$this->init_email_fields();

		if (is_array($this->fields)) :
			foreach ($this->fields as $k => $v) :
				$woocommerce_settings[$k] = $v;
			endforeach;
		endif;
	}

	/**
	 * get_tab_in_view()
	 *
	 * Get the tab current in view/processing.
	 */
	function get_tab_in_view($current_filter, $filter_base) {
		return str_replace($filter_base, '', $current_filter);
	}

	/**
	 * init_form_fields()
	 *
	 * Prepare form fields to be used in the various tabs.
	 */
	function init_form_fields() {
		$shop_order_status = get_terms( 'shop_order_status', array('hide_empty' => false) );
		$status_options = array();
		foreach($shop_order_status as $s){
			$status_options[$s->slug] = $s->slug;
		}
		
		// Define main settings			
		$this->fields['wc_wishlists_main'] = apply_filters('woocommerce_wishlists_options_settings_fields', array(
		    array(
			'name' => __('Wishlist Options', 'wc_wishlist'),
			'type' => 'title',
			'desc' => '',
			'id' => 'wc_wishlists_options'
		    ),
		    array(
			'name' => __('Allow Guests to Create Lists', 'wc_wishlists'),
			'desc' => '',
			'id' => 'wc_wishlist_guest_enabled',
			'type' => 'select',
			'std' => 'enabled',
			'default' => 'enabled',
			'options' => array('enabled' => 'Yes', 'disabled' => 'No')
		    ),
		    array(
			'name' => __('Message for Guests', 'wc_wishlists'),
			'desc' => __('Only appears when Allow Guest to Create Lists is false ', 'wc_wishlists'),
			'id' => 'wc_wishlist_guest_disabled_message',
			'css' => 'width:100%;height:75px',
			'type' => 'textarea',
			'default' => __('Please login or register for an account to create a wishlist', 'wc_wishlists'),
			'std' => __('Please login or register for an account to create a wishlist', 'wc_wishlists')
		    ),
		    array(
			'name' => __('Redirect to Wishlist', 'wc_wishlists'),
			'desc' => '',
			'id' => 'woocommerce_wishlist_redirect_after_add_to_cart',
			'type' => 'select',
			'std' => 'yes',
			'default' => 'yes',
			'options' => array('yes' => 'Yes', 'no' => 'No')
		    ),
		    array(
			'name' => __('Button / Link Type', 'wc_wishlists'),
			'desc' => '',
			'id' => 'wc_wishlist_use_button',
			'type' => 'select',
			'std' => 'button',
			'default' => 'button',
			'options' => array('button' => 'Button', 'link' => 'Link')
		    ),
		    array(
			'name' => __('Icon ', 'wc_wishlists'),
			'desc' => __('Only appears when button/link type is set to link', 'wc_wishlists'),
			'id' => 'wc_wishlist_icon',
			'type' => 'select',
			'std' => 'present',
			'default' => 'present',
			'options' => array('' => 'None', 'star' => 'Star', 'present' => '<img src="' . WC_Wishlists_Plugin::plugin_url() . '/assets/images/present.png" />Present')
		    ),
		    array(
			'name' => __('Button / Link Text', 'wc_wishlists'),
			'type' => 'text',
			'desc' => '',
			'css' => '',
			'default' => __('Add to wishlist', 'wc_wishlist'),
			'std' => __('Add to wishlist', 'wc_wishlist'),
			'id' => 'wc_wishlist_button_text'
		    ),
		    array(
			'name' => __('Cart Label', 'wc_wishlists'),
			'type' => 'text',
			'desc' => '',
			'css' => '',
			'default' => __('From Wishlist', 'wc_wishlist'),
			'std' => __('From Wishlist', 'wc_wishlist'),
			'id' => 'wc_wishlists_cart_label'
		    ),
		    array('type' => 'sectionend', 'id' => 'wc_wishlists_options'),
		    array(
			'name' => __('Wishlist Processing Options', 'wc_wishlist'),
			'type' => 'title',
			'desc' => __('Choose behaivor after an item has been ordered from a wishlist', 'wc_wishlist'),
			'id' => 'wc_wishlists_processing_options'
		    ),
		    array(
			'name' => __('Automatic Removal ', 'wc_wishlists'),
			'desc' => __('Automatically remove items from a wishlist?', 'wc_wishlists'),
			'id' => 'wc_wishlist_processing_autoremove',
			'type' => 'select',
			'std' => 'no',
			'default' => 'no',
			'options' => array('no' => 'No', 'yes' => 'Yes')
		    ),
		     array(
			'name' => __('Automatic Removal on Status ', 'wc_wishlists'),
			'desc' => __('Choose the status the order must reach for the item to be removed from the list', 'wc_wishlists'),
			'id' => 'wc_wishlist_processing_autoremove_status',
			'type' => 'select',
			'std' => 'completed',
			'default' => 'completed',
			'options' => $status_options
		    ),
		    
		    array('type' => 'sectionend', 'id' => 'wc_wishlists_processing_options'),
		    array(
			'name' => __('Wishlist Sharing Options', 'wc_wishlist'),
			'type' => 'title',
			'desc' => '',
			'id' => 'wc_wishlists_sharing_options'
		    ),
		    array(
			'name' => __('Social Options', 'wc_wishlist'),
			'desc' => __('Facebook', 'wc_wishlist'),
			'id' => 'wc_wishlists_sharing_facebook',
			'default' => 'yes',
			'std' => 'yes',
			'type' => 'checkbox',
			'checkboxgroup' => 'start'
		    ),
		    array(
			'desc' => __('Twitter', 'wc_wishlist'),
			'id' => 'wc_wishlists_sharing_twitter',
			'default' => 'yes',
			'std' => 'yes',
			'type' => 'checkbox',
			'checkboxgroup' => ''
		    ),
		    array(
			'desc' => __('Email', 'wc_wishlist'),
			'id' => 'wc_wishlists_sharing_email',
			'default' => 'yes',
			'std' => 'yes',
			'type' => 'checkbox',
			'checkboxgroup' => ''
		    ),
		    array(
			'desc' => __('Pinterest', 'wc_wishlist'),
			'id' => 'wc_wishlists_sharing_pinterest',
			'default' => 'yes',
			'std' => 'yes',
			'type' => 'checkbox',
			'checkboxgroup' => 'end'
		    ),
		    array('type' => 'sectionend', 'id' => 'wc_wishlists_sharing_options'),
		    array(
			'name' => __('Wishlist Style Options', 'wc_wishlist'),
			'type' => 'title',
			'desc' => '',
			'id' => 'wc_wishlists_style_options'
		    ),
		    array(
			'name' => __('Custom Buttons', 'wc_wishlists'),
			'desc' => __('Enable Custom Wishlist Button Colors', 'wc_wishlist'),
			'id' => 'wc_wishlists_use_custom_button_colors',
			'default' => 'no',
			'std' => 'yes',
			'type' => 'checkbox'
		    ),
		    array(
			'type' => 'wishlist_styles'
		    ),
		    array(
			'name' => __('Custom CSS', 'wc_wishlists'),
			'class' => 'wide',
			'css' => 'width:100%;min-height:100px',
			'desc' => __('Enter in any custom styles you would like here.', 'wc_wishlists'),
			'id' => 'wc_wishlist_custom_css',
			'type' => 'textarea',
			'default' => ''
		    ),
		    array('type' => 'sectionend', 'id' => 'wc_wishlists_style_options'),
		    array(
			'name' => __('Wishlist Page Options', 'wc_wishlist'),
			'type' => 'title',
			'desc' => 'The following pages need selecting so that Wishlists knows where they are. These pages should have been created upon installation of the plugin, if not you will need to create them.',
			'id' => 'wc_wishlists_page_options'
		    ),
		    array(
			'name' => __('My Lists Page', 'wc_wishlist'),
			'desc' => sprintf(__('This is where users will view their lists. Page contents: [wc_wishlists_my_archive]', 'wc_wishlist'), '<a target="_blank" href="options-permalink.php">', '</a>'),
			'id' => 'wc_wishlists_page_id_my-lists',
			'type' => 'single_select_page',
			'std' => '',
			'class' => 'chosen_select_nostd',
			'css' => 'min-width:300px;',
			'desc_tip' => true,
		    ),
		    array(
			'name' => __('Create a List', 'wc_wishlist'),
			'desc' => sprintf(__('This is where users will create new lists. Page contents: [wc_wishlists_create]', 'wc_wishlist'), '<a target="_blank" href="options-permalink.php">', '</a>'),
			'id' => 'wc_wishlists_page_id_create-a-list',
			'type' => 'single_select_page',
			'std' => '',
			'class' => 'chosen_select_nostd',
			'css' => 'min-width:300px;',
			'desc_tip' => true,
		    ),
		    array(
			'name' => __('Edit a List', 'wc_wishlist'),
			'desc' => sprintf(__('This is where users will edit their list. Page contents: [wc_wishlists_edit]', 'wc_wishlist'), '<a target="_blank" href="options-permalink.php">', '</a>'),
			'id' => 'wc_wishlists_page_id_edit-my-list',
			'type' => 'single_select_page',
			'std' => '',
			'class' => 'chosen_select_nostd',
			'css' => 'min-width:300px;',
			'desc_tip' => true,
		    ),
		    array(
			'name' => __('Find a List', 'wc_wishlist'),
			'desc' => sprintf(__('This is where you can search for lists. Page contents: [wc_wishlists_search]', 'wc_wishlist'), '<a target="_blank" href="options-permalink.php">', '</a>'),
			'id' => 'wc_wishlists_page_id_find-a-list',
			'type' => 'single_select_page',
			'std' => '',
			'class' => 'chosen_select_nostd',
			'css' => 'min-width:300px;',
			'desc_tip' => true,
		    ),
		    array(
			'name' => __('View a List', 'wc_wishlist'),
			'desc' => sprintf(__('This is where public and protected lists are viewed. Page contents: [wc_wishlists_single]', 'wc_wishlist'), '<a target="_blank" href="options-permalink.php">', '</a>'),
			'id' => 'wc_wishlists_page_id_view-a-list',
			'type' => 'single_select_page',
			'std' => '',
			'class' => 'chosen_select_nostd',
			'css' => 'min-width:300px;',
			'desc_tip' => true,
		    ),
		    array('type' => 'sectionend', 'id' => 'wc_wishlists_page_options'))
		);
	}

	function init_email_fields() {
		return;
		/*
		 * Future place for email subscription settings. 
		  $body_default = $this->get_default_email_body();

		  // Define main settings
		  $this->fields['wc_wishlists_email'] = apply_filters('woocommerce_wishlists_options_settings_fields', array(
		  array(
		  'name' => __('Email Templates', 'wc_wishlist'),
		  'type' => 'title',
		  'desc' => '',
		  'id' => 'wc_wishlists_email_templates'
		  ),
		  array(
		  'name' => __('Subject', 'wc_wishlists'),
		  'type' => 'text',
		  'desc' => '',
		  'css' => 'width:50%;',
		  'default' => __('A wishlist has been shared with you', 'wc_wishlist'),
		  'id' => 'wc_wishlist_sharing_email_subject'
		  ),
		  array(
		  'name' => __('Email Body', 'wc_wishlists'),
		  'css' => 'width:50%;height:350px;',
		  'desc' => __('', 'wc_wishlists'),
		  'id' => 'wc_wishlist_sharing_email_body',
		  'type' => 'textarea',
		  'default' => $body_default
		  ),
		  array(
		  'type' => 'wishlist_email_tags'
		  ),
		  array('type' => 'sectionend', 'id' => 'wc_wishlists_email_templates')
		  )
		  );
		 */
	}

	/**
	 * save_settings()
	 *
	 * Save settings in a single field in the database for each tab's fields (one field per tab).
	 */
	function save_settings() {
		global $woocommerce, $woocommerce_settings;

		// Make sure our settings fields are recognised.
		$this->add_settings_fields();

		$current_tab = $this->get_tab_in_view(current_filter(), 'woocommerce_update_options_');

		include_once( $woocommerce->plugin_path() . '/admin/settings/settings-save.php' );

		woocommerce_update_options($woocommerce_settings['wc_wishlists_main']);
		//woocommerce_update_options( $woocommerce_settings[ 'wc_wishlists_email' ] );

		$primary = (!empty($_POST['wishlist_frontend_button_css_colors']) ) ? woocommerce_format_hex($_POST['wishlist_frontend_button_css_colors']) : '';
		$link = (!empty($_POST['wishlist_frontend_link_css_colors']) ) ? woocommerce_format_hex($_POST['wishlist_frontend_link_css_colors']) : '';

		$old_colors = get_option('wishlist_frontend_css_colors');

		$colors = array(
		    'primary' => $primary,
		    'link' => $link
		);

		update_option('wishlist_frontend_css_colors', $colors);
	}

	/** Helper functions ***************************************************** */
	/**
	 * Gets a setting
	 */
	public function setting($key) {
		return get_option($key);
	}

	public function wishlist_styles_setting() {
		global $woocommerce;
		?><tr valign="top" class="woocommerce_frontend_css_colors">
			<th scope="row" class="titledesc">
		                <label><?php _e('Wishlist Button Color', 'woocommerce'); ?></label>
			</th>
			<td class="forminp"><?php
		// Get settings
		$colors = array_map('esc_attr', (array) get_option('wishlist_frontend_css_colors'));
		// Defaults
		if (empty($colors['primary'])) {
			$colors['primary'] = '#095eed';
		}
		// Show inputs
		$this->frontend_css_color_picker(__('Button', 'wc_wishlists'), 'wishlist_frontend_button_css_colors', $colors['primary'], __('Choose the color for the background of the button when link type is set to button.', 'wc_wishlist'));
		?>

				<?php
				// Defaults
				if (empty($colors['link'])) {
					$colors['link'] = '#ffffff';
				}
				// Show inputs
				$this->frontend_css_color_picker(__('Text', 'wc_wishlists'), 'wishlist_frontend_link_css_colors', $colors['link'], __('Choose the color for the font of the button when link type is set to button.', 'wc_wishlist'));
				?>

			</td>
		</tr>
				<?php
			}

			public function frontend_css_color_picker($name, $id, $value, $desc = '') {
				global $woocommerce;

				echo '<div class="color_box"><strong><img class="help_tip" data-tip="' . esc_attr($desc) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" /> ' . esc_html($name) . '</strong>
   		<input name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" type="text" value="' . esc_attr($value) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr($id) . '" class="colorpickdiv"></div>
    </div>';
			}

			/**
			 * Get the custom admin field: editor
			 */
			public function on_editor_field($value) {
				$content = get_option($value['id']);
				?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
			<td class="forminp">
		<?php wp_editor($content, $value['id']); ?>
			</td>
		</tr>
		<?php
	}

	public function selected($value, $compare, $arg = true) {
		if (!$arg) {
			echo '';
		} else if ((string) $value == (string) $compare) {
			echo 'selected="selected"';
		}
	}

}
?>