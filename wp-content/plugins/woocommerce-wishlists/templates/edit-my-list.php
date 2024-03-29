<?php $wishlist = new WC_Wishlists_Wishlist($_GET['wlid']); ?>

<?php
$current_owner_key = WC_Wishlists_User::get_wishlist_key();
$sharing = $wishlist->get_wishlist_sharing();
$sharing_key = $wishlist->get_wishlist_sharing_key();
$wl_owner = $wishlist->get_wishlist_owner();

$wishlist_items = WC_Wishlists_Wishlist_Item_Collection::get_items($wishlist->id, true);

$treat_as_registry = false;
?>

<?php
if ($wl_owner != WC_Wishlists_User::get_wishlist_key() && !current_user_can('manage_woocommerce')) :

	die();

endif;
?>

<?php do_action('woocommerce_wishlists_before_wrapper'); ?>
<div id="wl-wrapper" class="product woocommerce"> <!-- product class so woocommerce stuff gets applied in tabs -->

	<?php if (function_exists('wc_print_messages')) : ?>
		<?php wc_print_messages(); ?>
	<?php else : ?>
		<?php woocommerce_show_messages(); ?>
	<?php endif; ?>
	
	<div class="wl-intro">
		<h2 class="entry-title"><?php $wishlist->the_title(); ?></h2>
		<div class="wl-intro-desc">
			<?php $wishlist->the_content(); ?>
		</div>
		<?php if ($sharing == 'Public' || $sharing == 'Shared') : ?>
			<div class="wl-share-url"><strong><?php _e('Wishlist URL:', 'wc_wishlist'); ?> </strong><?php echo $wishlist->the_url_view($sharing == 'Shared'); ?></div>
		<?php endif; ?>
		<?php if ($sharing == 'Public' || $sharing == 'Shared') : ?>
			<?php if ($wishlist_items && count($wishlist_items)) : ?>
		                <div class="wl-meta-share">
					<?php woocommerce_wishlists_get_template('wishlist-sharing-menu.php', array('id' => $wishlist->id)); ?>
		                </div>
			<?php endif; ?>
		<?php endif; ?>

		<p><a class="wlconfirm" href="<?php $wishlist->the_url_delete(); ?>"><?php _e('Delete list', 'wc_wishlist'); ?></a>
			<?php if (($sharing == 'Public' || $sharing == 'Shared') && count($wishlist_items)) : ?>
				| <a href="<?php $wishlist->the_url_view(); ?>&preview=true"><?php _e('Preview List', 'wc_wishlist'); ?></a>
			<?php endif; ?>
		</p>
	</div>

	<div class="wl-tab-wrap woocommerce-tabs">

		<ul class="wl-tabs tabs">
			<li class="wl-items-tab"><a href="#tab-wl-items"><?php _e('List Items', 'wc_wishlist'); ?></a></li>
			<li class="wl-settings-tab"><a href="#tab-wl-settings"><?php _e('Settings', 'wc_wishlist'); ?></a></li>
		</ul>

		<div class="wl-panel panel" id="tab-wl-items">
			<?php if (sizeof($wishlist_items) > 0) : ?>
				<form action="<?php $wishlist->the_url_edit(); ?>" method="post" class="wl-form" id="wl-items-form">
					<input type="hidden" name="wlid" value="<?php echo $wishlist->id; ?>" />
					<?php WC_Wishlists_Plugin::nonce_field('manage-list'); ?>
					<?php echo WC_Wishlists_Plugin::action_field('manage-list'); ?>
					<input type="hidden" name="wlmovetarget" id="wlmovetarget" value="0" />

					<div class="wl-row">
						<table width="100%" cellpadding="0" cellspacing="0" class="wl-actions-table">
							<tbody>
								<tr>
									<td>
										<select class="wl-sel move-list-sel" name="wlupdateaction" id="wleditaction1">
											<option selected="selected"><?php _e('Actions', 'wc_wishlist'); ?></option>
											<option value="add-to-cart"><?php _e('Add to Cart', 'wc_wishlist'); ?></option>
											<option value="quantity"><?php _e('Update Quantities', 'wc_wishlist'); ?></option>
											<option value="remove"><?php _e('Remove from List', 'wc_wishlist'); ?></option>
											<optgroup label="<?php _e('Move to another List', 'wc_wishlist'); ?>">
												<?php $lists = WC_Wishlists_User::get_wishlists(); ?>
												<?php if ($lists && count($lists)) : ?>
													<?php foreach ($lists as $list) : ?>
														<?php if ($list->id != $wishlist->id) : ?>
															<option value="<?php echo $list->id; ?>"><?php $list->the_title(); ?> ( <?php echo $wishlist->get_wishlist_sharing(); ?> )</option>
														<?php endif; ?>
													<?php endforeach; ?>
												<?php endif; ?>
												<option value="create"><?php _e('+ Create A New List', 'wc_wishlist'); ?></option>
											</optgroup>
										</select>
									<td>
										<button class="button small wl-but wl-add-to btn-apply"><?php _e('Apply Action', 'wc_wishlist'); ?></button>
									</td>
								</tr>
							</tbody>
						</table>
					</div><!-- wl-row wl-clear -->

					<table class="shop_table cart wl-table manage" cellspacing="0">
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" name="" value="" /></th>
								<th class="product-remove">&nbsp;</th>
								<th class="product-thumbnail">&nbsp;</th>
								<th class="product-name"><?php _e('Product', 'wc_wishlist'); ?></th>
								<th class="product-price"><?php _e('Price', 'wc_wishlist'); ?></th>
								<th class="product-quantity ctr"><?php _e('Qty', 'wc_wishlist'); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($wishlist_items as $wishlist_item_key => $item) {
								$_product = $item['data'];
								if ($_product->exists() && $item['quantity'] > 0) {
									?>
									<tr class="cart_table_item">
										<td class="check-column" >
											<input type="checkbox" name="wlitem[]" value="<?php echo $wishlist_item_key; ?>" />
										</td>
										<td class="product-remove">
											<a href="<?php echo woocommerce_wishlist_url_item_remove($wishlist->id, $wishlist_item_key); ?>" class="remove wlconfirm" title="<?php _e('Remove this item from your wishlist', 'wc_wishlist'); ?>" data-message="<?php esc_attr(_e('Are you sure you would like to remove this item from your list?', 'wc_wishlist')); ?>">&times;</a>
										</td>

										<!-- The thumbnail -->
										<td class="product-thumbnail">
											<?php
											printf('<a href="%s">%s</a>', esc_url(get_permalink(apply_filters('woocommerce_in_cart_product_id', $item['product_id']))), $_product->get_image());
											?>
										</td>

										<td class="product-name">
											<?php
											printf('<a href="%s">%s</a>', esc_url(get_permalink(apply_filters('woocommerce_in_cart_product_id', $item['product_id']))), 
												apply_filters('woocommerce_in_wishlist_product_title', $_product->get_title(), $_product));

											// Meta data
											echo $woocommerce->cart->get_item_data($item);

											// Availability
											$availability = $_product->get_availability();

											if ($availability['availability']) :
												echo apply_filters('woocommerce_stock_html', '<p class="stock ' . esc_attr($availability['class']) . '">' . esc_html($availability['availability']) . '</p>', $availability['availability']);
											endif;
											?>
										</td>

										<!-- Product price -->
										<td class="product-price">
											<?php
											$product_price = ( get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes' ) ? $_product->get_price_excluding_tax() : $_product->get_price();
											echo apply_filters('woocommerce_cart_item_price_html', woocommerce_price($product_price), $item, $wishlist_item_key);
											?>
										</td>

										<!-- Quantity inputs -->
										<td class="product-quantity">
											<?php
											if ($_product->is_sold_individually()) {
												$product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $wishlist_item_key);
											} else {

												$step = apply_filters('woocommerce_quantity_input_step', '1', $_product);
												$min = apply_filters('woocommerce_quantity_input_min', '', $_product);
												$max = apply_filters('woocommerce_quantity_input_max', $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(), $_product);

												$product_quantity = sprintf('<div class="quantity"><input type="text" name="cart[%s][qty]" step="%s" min="%s" max="%s" value="%s" size="4" title="' . _x('Qty', 'Product quantity input tooltip', 'woocommerce') . '" class="input-text qty text" maxlength="12" /></div>', $wishlist_item_key, $step, $min, $max, esc_attr($item['quantity']));
											}

											echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $wishlist_item_key);
											?>
										</td>
										<?php if ($treat_as_registry) : ?>
											<td class="product-quantity">
												<?php $quantity_purchased = apply_filters('woocommerce_wishlist_item_purchased_quantity', isset($item['quantity_purchased']) ? $item['quantity_purchased'] : 0, $wishlist_item_key); ?>
												<?php
												$quantity_remaining = (int) $item['quantity'] - (int) $quantity_purchased;
												$quantity_remaining = $quantity_remaining > 0 ? absint($quantity_remaining) : 0;
												?>
												<?php echo apply_filters('woocommerce_wishlist_item_needs_quantity', $quantity_remaining, $wishlist_item_key); ?>
											</td>
										<?php endif; ?>
										<td class="product-purchase">
											<?php if ($_product->is_in_stock()) : ?>
												<a href="<?php echo woocommerce_wishlist_url_item_add_to_cart($wishlist->id, $wishlist_item_key, $wishlist->get_wishlist_sharing() == 'Shared' ? $wishlist->get_wishlist_sharing_key() : false, 'edit' ); ?>" class="button alt"><?php _e('Add to Cart', 'wc_wishlist'); ?></a>
											<?php endif; ?>
										</td>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
					<div class="wl-row">
						<table width="100%" cellpadding="0" cellspacing="0" class="wl-actions-table">
							<tbody>
								<tr>
									<td>
										<select class="wl-sel move-list-sel" name="wleditaction2" id="wleditaction2">
											<option selected="selected"><?php _e('Actions', 'wc_wishlist'); ?></option>
											<option value="quantity"><?php _e('Update Quantities', 'wc_wishlist'); ?></option>
											<option value="add-to-cart"><?php _e('Add to Cart', 'wc_wishlist'); ?></option>
											<option value="remove"><?php _e('Remove from List', 'wc_wishlist'); ?></option>
											<optgroup label="<?php _e('Move to another list', 'wc_wishlist'); ?>">
												<?php $lists = WC_Wishlists_User::get_wishlists(); ?>
												<?php if ($lists && count($lists)) : ?>
													<?php foreach ($lists as $list) : ?>
														<?php if ($list->id != $wishlist->id) : ?>
															<option value="<?php echo $list->id; ?>"><?php $list->the_title(); ?> ( <?php echo $wishlist->get_wishlist_sharing(); ?> )</option>
														<?php endif; ?>
													<?php endforeach; ?>
												<?php endif; ?>
												<option value="create"><?php _e('+ Create A New List', 'wc_wishlist'); ?></option>
											</optgroup>
										</select>
									</td>
									<td>
										<button class="button small wl-but wl-add-to btn-apply"><?php _e('Apply Action', 'wc_wishlist'); ?></button>
									</td>
								</tr>
							</tbody>
						</table>

						<div class="wl-clear"></div>
					</div><!-- wl-row wl-clear -->
				</form>

			<?php else : ?>
				<?php $shop_url = get_permalink(woocommerce_get_page_id('shop')); ?>
				<?php _e('You do not have anything in this list.', 'wc_wishlist'); ?> <a href="<?php echo $shop_url; ?>"><?php _e('Go Shopping', 'wc_wishlist'); ?></a>.

			<?php endif; ?>


		</div><!-- /tab-wl-items -->





		<div class="wl-panel panel" id="tab-wl-settings">
			<div class="wl-form">
				<form  action="" enctype="multipart/form-data" method="post">
					<input type="hidden" name="wlid" value="<?php echo $wishlist->id; ?>" />
					<?php echo WC_Wishlists_Plugin::action_field('edit-list'); ?>
					<?php echo WC_Wishlists_Plugin::nonce_field('edit-list'); ?>

<!-- <p class="form-row">
<strong>What kind of list is this?<abbr class="required" title="required">*</abbr></strong>
            <table class="wl-rad-table">
                    <tr>
                            <td><input type="radio" name="wishlist_type" id="rad_wishlist" value="wishlist" checked="checked"></td>
                            <td><label for="rad_wishlist">Wishlist <span class="wl-small">- Allows you to add products to a list for later.</span></label></td>
                    </tr>
                    <tr>
                            <td><input type="radio" name="wishlist_type" id="rad_reg" value="registry"></td>
                            <td><label for="rad_reg">Registry <span class="wl-small">- Registries allow you to request a specific number of items and users can purchase items which will update the list for others to know what has been purchased.</span></label></td>
                    </tr>
            </table>
</p> -->
					<p class="form-row form-row-wide">
						<label for="wishlist_title"><?php _e('Name your list', 'wc_wishlist'); ?> <abbr class="required" title="required">*</abbr></label>
						<input type="text" name="wishlist_title" id="wishlist_title" class="input-text" value="<?php echo esc_attr($wishlist->post->post_title); ?>" />
					</p>
					<p class="form-row form-row-wide">
						<label for="wishlist_description"><?php _e('Describe your list', 'wc_wishlist'); ?></label>
						<textarea name="wishlist_description" id="wishlist_description"><?php echo esc_textarea($wishlist->post->post_content); ?></textarea>
					</p>
					<hr />
					<p class="form-row">
						<strong><?php _e('Privacy Settings', 'wc_wishlist'); ?> <abbr class="required" title="required">*</abbr></strong>
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
					<p><?php _e('Enter a name you would like associated with this list.  If your list is public, users can find it by searching for this name.', 'wc_wishlist'); ?></p>

					<p class="form-row form-row-first">
						<label for="wishlist_first_name"><?php _e('First Name', 'wc_wishlist'); ?></label>
						<input type="text" name="wishlist_first_name" id="wishlist_first_name" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_first_name', true)); ?>" class="input-text" />
					</p>

					<p class="form-row form-row-last">
						<label for="wishlist_first_name"><?php _e('Last Name', 'wc_wishlist'); ?></label>
						<input type="text" name="wishlist_last_name" id="wishlist_last_name" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_last_name', true)); ?>" class="input-text" />
					</p>

					<p class="form-row form-row-first">
						<label for="wishlist_owner_email"><?php _e('Email Associated with the List', 'wc_wishlist'); ?></label>
						<input type="text" name="wishlist_owner_email" id="wishlist_owner_email" value="<?php echo esc_attr(get_post_meta($wishlist->id, '_wishlist_email', true)); ?>" class="input-text" />
					</p>
					<div class="wl-clear"></div>

					<p class="form-row">
						<input type="submit" class="button alt" name="update_wishlist" value="<?php _e('Save Changes', 'wc_wishlist'); ?>">
					</p>
				</form>
				<div class="wl-clear"></div>
			</div><!-- /wl form -->

		</div><!-- /tab-wl-settings panel -->
	</div><!-- /wishlist-wrapper -->

	<?php woocommerce_wishlists_get_template('wishlist-email-form.php', array('wishlist' => $wishlist)); ?>
</div>

<?php do_action('woocommerce_wishlists_after_wrapper'); ?>