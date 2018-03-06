<?php

/**
 * Template tags
 */

function invp_get_the_vin() {
	return get_post_meta( get_the_ID(), apply_filters( 'invp_prefix_meta_key', 'vin' ), true );
}

function invp_get_the_price() {
	return get_post_meta( get_the_ID(), apply_filters( 'invp_prefix_meta_key', 'price' ), true );
}
