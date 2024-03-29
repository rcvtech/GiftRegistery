<?php
global $current_user;
get_currentuserinfo();
?>
<?php do_action('woocommerce_wishlists_before_wrapper'); ?>
<div id="wl-wrapper" class="woocommerce">
	<?php if (function_exists('wc_print_messages')) : ?>
		<?php wc_print_messages(); ?>
	<?php else : ?>
		<?php woocommerce_show_messages(); ?>
	<?php endif; ?>
	<div class="wl-form">
		<form  action="" enctype="multipart/form-data" method="post">

			<?php echo WC_Wishlists_Plugin::action_field('create-list'); ?>
			<?php echo WC_Wishlists_Plugin::nonce_field('create-list'); ?>

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
				<label for="wishlist_title"><?php _e('Name your list', 'wc_wishlist'); ?><abbr class="required" title="required">*</abbr></label>
				<input type="text" name="wishlist_title" id="wishlist_title" class="input-text" value="" />
			</p>
			<p class="form-row form-row-wide">
				<label for="wishlist_description"><?php _e('Describe your list', 'wc_wishlist'); ?></label>
				<textarea name="wishlist_description" id="wishlist_description"></textarea>
			</p>
			<hr />
			<p class="form-row">
				<strong><?php _e('Privacy Settings', 'wc_wishlist'); ?><abbr class="required" title="required">*</abbr></strong>
			<table class="wl-rad-table">
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_pub" value="Public" checked="checked"></td>
					<td><label for="rad_pub"><?php _e('Public', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Anyone can search for and see this list. You can also share using a link.', 'wc_wishlist'); ?></span></label></td>
				</tr>
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_shared" value="Shared"></td>
					<td><label for="rad_shared"><?php _e('Shared', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Only people with the link can see this list. It will not appear in public search results.', 'wc_wishlist'); ?></span></label></td>
				</tr>
				<tr>
					<td><input type="radio" name="wishlist_sharing" id="rad_priv" value="Private"></td>
					<td><label for="rad_priv"><?php _e('Private', 'wc_wishlist'); ?> <span class="wl-small">- <?php _e('Only you can see this list.', 'wc_wishlist'); ?></span></label></td>
				</tr>
			</table>
			</p>
			<p><?php _e('Enter a name you would like associated with this list.  If your list is public, users can find it by searching for this name.', 'wc_wishlist'); ?></p>
			<p class="form-row form-row-first">
				<label for="wishlist_first_name"><?php _e('First Name', 'wc_wishlist'); ?></label>
				<?php if (is_user_logged_in()) : ?>
					<input type="text" name="wishlist_first_name" id="wishlist_first_name" class="input-text" value="<?php echo esc_attr($current_user->user_firstname); ?>" />
				<?php else : ?>
					<input type="text" name="wishlist_first_name" id="wishlist_first_name" class="input-text" value="" />
				<?php endif; ?>
			</p>
			<p class="form-row form-row-last">
				<label for="wishlist_first_name"><?php _e('Last Name', 'wc_wishlist'); ?></label>
				<?php if (is_user_logged_in()) : ?>
					<input type="text" name="wishlist_last_name" id="wishlist_last_name" class="input-text" value="<?php echo esc_attr($current_user->user_lastname); ?>" />

				<?php else : ?>
					<input type="text" name="wishlist_last_name" id="wishlist_last_name" class="input-text" value="" />
				<?php endif; ?>
			</p>
			<p class="form-row form-row-first">
				<label for="wishlist_owner_email"><?php _e('Email Associated with the List', 'wc_wishlist'); ?></label>
				<input type="text" name="wishlist_owner_email" id="wishlist_owner_email" value="<?php echo (is_user_logged_in() ? $current_user->user_email : ''); ?>" class="input-text" />
			</p>
			<div class="wl-clear"></div>
			<p class="form-row">
				<input type="submit" class="button alt" name="update_wishlist" value="<?php _e('Create List', 'wc_wishlist'); ?>">
			</p>
		</form>
	</div><!-- /wl form -->
</div><!-- /wishlist-wrapper -->
<?php do_action('woocommerce_wishlists_after_wrapper'); ?>