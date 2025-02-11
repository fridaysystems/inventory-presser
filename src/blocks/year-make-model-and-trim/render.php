<?php
/**
 * All of the parameters passed to the function where this file is being required are accessible in this scope:
 *
 * @param array    $attributes     The array of attributes for this block.
 * @param string   $content        Rendered block output. ie. <InnerBlocks.Content />.
 * @param WP_Block $block          The instance of the WP_Block class that represents the block being rendered.
 *
 * @package inventory-presser
 */

?>
<h1 <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
	<?php

	/**
	 * The wp_kses_post function is used to ensure any HTML that is not allowed in a post will be escaped.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_kses_post/
	 * @see https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/#escaping-securing-output
	 */
	echo wp_kses_post( invp_get_the_year() . ' ' . invp_get_the_make() . ' ' . invp_get_the_model() . ' ' . invp_get_the_trim() );
	?>
</h1>