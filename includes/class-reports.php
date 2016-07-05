<?php
/**
 * An object to fetch autocheck data.
 */

if ( ! class_exists( 'Inventory_Vehicle_Reports' ) ) {
	class Inventory_Vehicle_Reports {

		function __construct() {
			add_action("wp_ajax_autocheck", array($this,"get_autocheck_report"));
			add_action("wp_ajax_nopriv_autocheck", array($this,"get_autocheck_report"));
		}

		function get_autocheck_report() {

			$_dealer_settings = get_option('_dealer_settings');

			if (isset($_dealer_settings['autocheck_id']) && $_dealer_settings['autocheck_id']) {

				$vin = (isset($_GET['vin'])) ? urlencode($_GET['vin']) : '';

				$post_args = array('sid'=>$_dealer_settings['autocheck_id'],'vin'=>$vin);
				$options = array(
				    'http' => array(
				        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				        'method'  => 'POST',
				        'content' => http_build_query($post_args)
				    )
				);
				$context  = stream_context_create($options);
				$result = file_get_contents('http://cardealerwebs.com/AutoCheck/report.php', false, $context);
				if ($result === FALSE) { /* TODO Handle error */}

				echo $result;

			} else {

				echo 'Autocheck ID not set';

			}

			exit();
		}

	}
}

new Inventory_Vehicle_Reports();
