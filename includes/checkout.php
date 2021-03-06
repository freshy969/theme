<?php

/**
 * Link to terms page
 * @return [type] [description]
 */
function affwp_edd_terms_agreement() {
	global $edd_options;
 
	if ( isset( $edd_options['show_agree_to_terms'] ) ) : ?>
	
	<fieldset id="edd_terms_agreement">
		<label for="edd_agree_to_terms">
			I agree to the <?php echo '<a id="load-refund-policy" target="_blank" href="#affwp-refund-policy">refund policy</a>'; ?>
		</label>
		<input name="edd_agree_to_terms" class="required" type="checkbox" id="edd_agree_to_terms" value="1" />
	</fieldset>
	
	<?php endif;
}
remove_action( 'edd_purchase_form_before_submit', 'edd_terms_agreement' );
add_action( 'edd_purchase_form_before_submit', 'affwp_edd_terms_agreement' );

/**
 * Terms and conditions
 */
function affwp_show_refund_policy() {
	if ( ! function_exists( 'edd_is_checkout' ) || ! edd_is_checkout() )
		return;

	$post = affwp_get_post_by_title( 'refund policy', 'page' );

	?>

	<div id="affwp-refund-policy" class="entry-content">
		<h1><?php echo $post->post_title; ?></h1>
		<?php echo wpautop( $post->post_content, true ); ?>
	</div>

	<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#load-refund-policy").fancybox({
					type: 'inline',
					padding: 32,
					maxWidth: 720,
					helpers: {
					    overlay: {
					      locked: false
					    }
					  },
					 openEffect	: 'fade',
					 closeEffect	: 'fade'
				});
			});
		</script>

	<?php
}
add_action( 'wp_footer', 'affwp_show_refund_policy' );

/**
 * Force account creation if developer license
 *
 * @since 1.1.7
 */
function affwp_force_account_creation( $ret ) {

	if ( edd_item_in_cart( affwp_get_affiliatewp_id(), array( 'price_id' => 2 ) ) )
		$ret = (bool) true;

	return $ret;
}
add_filter( 'edd_no_guest_checkout', 'affwp_force_account_creation' );

