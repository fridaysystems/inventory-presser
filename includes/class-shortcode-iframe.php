<?php
defined( 'ABSPATH' ) or exit;

/**
 * Inventory_Presser_Shortcode_Iframe
 * 
 * Creates a shortcode that makes it easy to create iframes. We've found this
 * useful on most sites for a financing application that is hosted on a separate
 * domain.
 */
class Inventory_Presser_Shortcode_Iframe
{
	/**
	 * add
	 * 
	 * Adds two shortcodes
	 *
	 * @return void
	 */
	function add()
	{
		add_shortcode( 'iframe', array( $this, 'content_but_wrong') );
		add_shortcode( 'invp-iframe', array( $this, 'content') );
		add_shortcode( 'invp_iframe', array( $this, 'content') );
	}

	/**
	 * hooks
	 * 
	 * Adds hooks that power the shortcode
	 *
	 * @return void
	 */
	function hooks()
	{
		add_action( 'init', array( $this, 'add' ) );
	}

	function content_but_wrong( $atts )
	{
		_doing_it_wrong(
			__FUNCTION__,
			__( 'The [iframe] shortcode was deprecated in version 12.2.0. Use [invp_iframe] instead. See https://inventorypresser.com/docs/shortcodes/', 'inventory-presser' ),
			'12.2.0'
		);
		return $this->content( $atts );
	}

	/**
	 * content
	 * 
	 * Creates the HTML content of the shortcode
	 *
	 * @param  array $atts
	 * @return string HTML that renders an iframe that expands to the height of its content
	 */
	function content( $atts ) {

		$atts = shortcode_atts( array(
			'width'       => '100%',
			'height'      => '5000',
			'scrolling'   => 'yes',
			'src'         => '',
			'class'       => 'iframe-class',
			'frameborder' => '0',
			'title'       => '',
		), $atts );

		//Stock number may arrive in a querystring variable with key 'stock'
		if( isset( $_GET['stock'] ) ) {
			$atts['src'] = esc_url( add_query_arg( 'stock', sanitize_text_field( $_GET['stock'] ), $atts['src'] ) );
		}

		$html = '<iframe';
		foreach( $atts as $attr => $value ) {

			//ignore some attributes
			$ignored_atts = array(
				'onclick',
				'onload',
				'onpageshow',
				'same_height_as',
			);
			if( in_array( strtolower( $attr), $ignored_atts ) ) { continue; }

			if ( $value != '' ) { // adding all attributes
				$html .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
			} else { // adding empty attributes
				$html .= ' ' . esc_attr( $attr );
			}
		}
		$html .= '></iframe>';

		if ( isset( $atts["same_height_as"] ) ) {
			$html .= sprintf( '<script>
				document.addEventListener("DOMContentLoaded", function(){
					var target_element, iframe_element;
					iframe_element = document.querySelector("iframe.%s");
					target_element = document.querySelector("%s");
					iframe_element.style.height = target_element.offsetHeight + "px";
				});
				</script>',
				esc_attr( $atts["class"] ),
				esc_attr( $atts["same_height_as"] )
			);
		}

		return $html;
	}
}
