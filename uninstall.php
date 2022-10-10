<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

require_once 'includes/class-invp.php';
INVP::add_hooks();
INVP::delete_all_data();
