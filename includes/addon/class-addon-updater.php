<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Inventory_Presser_Addon_Updater' ) ) {
	/**
	 * Allows plugins to use their own update API.
	 *
	 * This class is provided by Easy Digital Downloads and is originally
	 * named EDD_SL_Plugin_Updater.
	 *
	 * @author  Easy Digital Downloads
	 * @version 1.8.0
	 * @link    https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/EDD_SL_Plugin_Updater.php
	 */
	class Inventory_Presser_Addon_Updater {
		private $api_url     = '';
		private $api_data    = array();
		private $name        = '';
		private $slug        = '';
		private $version     = '';
		private $wp_override = false;
		private $cache_key   = '';

		private $health_check_timeout = 5;

		/**
		 * Class constructor.
		 *
		 * @uses plugin_basename()
		 * @uses hook()
		 *
		 * @param string $_api_url     The URL pointing to the custom API endpoint.
		 * @param string $_plugin_file Path to the plugin file.
		 * @param array  $_api_data    Optional data to send with API calls.
		 */
		public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {

			global $edd_plugin_data;

			$this->api_url     = trailingslashit( $_api_url );
			$this->api_data    = $_api_data;
			$this->name        = plugin_basename( $_plugin_file );
			$this->slug        = basename( $_plugin_file, '.php' );
			$this->version     = $_api_data['version'];
			$this->wp_override = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;
			$this->beta        = ! empty( $this->api_data['beta'] ) ? true : false;
			$this->cache_key   = 'edd_sl_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) );

			$edd_plugin_data[ $this->slug ] = $this->api_data;

			/**
			 * Fires after the $edd_plugin_data is setup.
			 *
			 * @since x.x.x
			 *
			 * @param array $edd_plugin_data Array of EDD SL plugin data.
			 */
			do_action( 'post_edd_sl_plugin_updater_setup', $edd_plugin_data );

			// Set up hooks.
			$this->init();
		}

		/**
		 * Set up WordPress filters to hook into WP's update process.
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 */
		public function init() {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
			remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10 );
			add_action( 'after_plugin_row_' . $this->name, array( $this, 'show_update_notification' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'show_changelog' ) );

			/**
			 * Changes "Automatic update is unavailable for this plugin." to
			 * "License key is missing at Vehicles → Options" if the problem is
			 * a missing license key for one of our add-ons.
			 */
			add_filter( 'site_transient_update_plugins', array( $this, 'show_missing_license_notice' ), 10, 2 );
		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * This function dives into the update API just when WordPress creates its update array,
		 * then adds a custom API call and injects the custom plugin data retrieved from the API.
		 * It is reassembled from parts of the native WordPress plugin update code.
		 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
		 *
		 * @uses api_request()
		 *
		 * @param  array $_transient_data Update array build by WordPress.
		 * @return array Modified update array with custom plugin data.
		 */
		public function check_update( $_transient_data ) {

			global $pagenow;
			if ( ! is_object( $_transient_data ) ) {
				$_transient_data = new stdClass();
			}

			if ( 'plugins.php' == $pagenow && is_multisite() ) {
				return $_transient_data;
			}

			if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
				return $_transient_data;
			}

			$current = $this->get_repo_api_data();
			if ( false !== $current && is_object( $current ) && isset( $current->new_version ) ) {
				if ( version_compare( $this->version, $current->new_version, '<' ) ) {
					$_transient_data->response[ $this->name ] = $current;
				} else {
					// Populating the no_update information is required to support auto-updates in WordPress 5.5.
					$_transient_data->no_update[ $this->name ] = $current;
				}
			}
			$_transient_data->last_checked           = time();
			$_transient_data->checked[ $this->name ] = $this->version;

			return $_transient_data;
		}

		/**
		 * Get repo API data from store.
		 * Save to cache.
		 *
		 * @return \stdClass
		 */
		public function get_repo_api_data() {
			$version_info = $this->get_cached_version_info();

			if ( false === $version_info ) {
				$version_info = $this->api_request(
					'plugin_latest_version',
					array(
						'slug' => $this->slug,
						'beta' => $this->beta,
					)
				);
				if ( ! $version_info ) {
					return false;
				}

				// This is required for your plugin to support auto-updates in WordPress 5.5.
				$version_info->plugin = $this->name;
				$version_info->id     = $this->name;

				$this->set_version_info_cache( $version_info );
			}

			return $version_info;
		}

		/**
		 * Changes "Automatic update is unavailable for this plugin." to
		 * "License key is missing at Vehicles → Options" if the problem is
		 * a missing license key for one of our add-ons.
		 *
		 * Does nothing for the WordPress Updates page. The message this method
		 * helps change appears only on the Plugins page.
		 *
		 * @param  object $value
		 * @param  mixed  $transient
		 * @return object
		 */
		public function show_missing_license_notice( $value, $transient ) {
			// Store a list of strings to avoid outputting JavaScript for the same plugin more than once.
			static $handled_plugin_slugs = array();

			if ( empty( $value->response ) ) {
				return $value;
			}

			// Is there a response from inventorypresser.com in this transient?
			foreach ( $value->response as $plugin_path => $update_response ) {
				if ( ! is_object( $update_response ) || empty( $update_response->slug ) ) {
					continue;
				}

				if ( strlen( $update_response->url ) < strlen( Inventory_Presser_Addon_License::STORE_URL )
					|| Inventory_Presser_Addon_License::STORE_URL != substr( $update_response->url, 0, strlen( Inventory_Presser_Addon_License::STORE_URL ) )
				) {
					continue;
				}

				// This is a plugin from inventorypresser.com.
				// Is the problem a missing license key?
				if ( ! empty( $update_response->msg )
					&& 'No license key has been provided.' == $update_response->msg
					&& ! in_array( $update_response->slug, $handled_plugin_slugs )
					&& ( ! is_multisite() || is_blog_admin() )
				) {
					/**
					 * Yes, output JavaScript that will change
					 * "Automatic update is unavailable for this plugin." to
					 * "License key is missing at Vehicles → Options"
					 */
					add_action(
						'admin_print_footer_scripts',
						function () use ( $update_response ) {
							?><script type="text/javascript"><!--
jQuery(document).ready(function(){
	jQuery('#<?php echo $update_response->slug; ?>-update .update-message p em').html( 'License key is missing at Vehicles → Options');
});
--></script>
							<?php
						}
					);

					$handled_plugin_slugs[] = $update_response->slug;
				}
			}
			return $value;
		}

		/**
		 * show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise!
		 *
		 * @param string $file
		 * @param array  $plugin
		 */
		public function show_update_notification( $file, $plugin ) {
			/**
			 * If this is a multi-site install, only show the update on the
			 * network plugins page.
			 */
			if ( is_multisite() && ! is_network_admin() ) {
				return;
			}

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( $this->name != $file ) {
				return;
			}

			// Remove our filter on the site transient.
			remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );
			$update_cache = get_site_transient( 'update_plugins' );
			$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();
			if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->name ] ) ) {

				$version_info = $this->get_repo_api_data();

				if ( false === $version_info ) {
					$version_info = $this->api_request(
						'plugin_latest_version',
						array(
							'slug' => $this->slug,
							'beta' => $this->beta,
						)
					);

					// Since we disabled our filter for the transient, we aren't running our object conversion on banners, sections, or icons. Do this now:
					if ( isset( $version_info->banners ) && ! is_array( $version_info->banners ) ) {
						$version_info->banners = $this->convert_object_to_array( $version_info->banners );
					}

					if ( isset( $version_info->sections ) && ! is_array( $version_info->sections ) ) {
						$version_info->sections = $this->convert_object_to_array( $version_info->sections );
					}

					if ( isset( $version_info->icons ) && ! is_array( $version_info->icons ) ) {
						$version_info->icons = $this->convert_object_to_array( $version_info->icons );
					}

					if ( isset( $version_info->contributors ) && ! is_array( $version_info->contributors ) ) {
						$version_info->contributors = $this->convert_object_to_array( $version_info->contributors );
					}

					$this->set_version_info_cache( $version_info );
				}

				if ( ! is_object( $version_info ) ) {
					return;
				}

				if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {
					$update_cache->response[ $this->name ] = $version_info;
				} else {
					$update_cache->no_update[ $this->name ] = $version_info;
				}

				$update_cache->last_checked           = time();
				$update_cache->checked[ $this->name ] = $this->version;

				set_site_transient( 'update_plugins', $update_cache );
			} else {
				$version_info = $update_cache->response[ $this->name ];
			}

			// Restore our filter
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

			if ( ! empty( $update_cache->response[ $this->name ] )
				&& version_compare( $this->version, $version_info->new_version, '<' )
			) {
				// build a plugin list row, with update notification
				printf(
					'<tr class="plugin-update-tr active" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">'
					. '<td colspan="4" class="plugin-update colspanchange">'
					. '<div class="update-message notice inline notice-warning notice-alt"><p>',
					$this->slug,
					$file
				);

				$changelog_link = self_admin_url(
					sprintf(
						'index.php?edd_sl_action=view_plugin_changelog&plugin=%s&slug=%s&TB_iframe=true&width=772&height=911',
						$this->name,
						$this->slug
					)
				);

				printf(
					'%s %s %s <a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s %s %s %s %s">%s %s %s</a>',
					__( 'There is a new version of', 'inventory-presser' ),
					esc_html( $version_info->name ),
					__( 'available.', 'inventory-presser' ),
					esc_url( $changelog_link ),
					__( 'View', 'inventory-presser' ),
					esc_html( $version_info->name ),
					__( 'version', 'inventory-presser' ),
					esc_html( $version_info->new_version ),
					__( 'details', 'inventory-presser' ),
					__( ' View version', 'inventory-presser' ),
					esc_html( $version_info->new_version ),
					__( 'details', 'inventory-presser' )
				);

				// Do we have a license key? Include "update now" link if so.
				if ( ! empty( $this->api_data['license'] ) ) {
					printf(
						' %s <a href="%s" class="update-link" aria-label="%s %s %s">%s</a>.',
						__( 'or', 'inventory-presser' ),
						esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) ),
						__( 'Update', 'inventory-presser' ),
						esc_html( $version_info->name ),
						__( 'now', 'inventory-presser' ),
						__( 'update now', 'inventory-presser' )
					);
				} else {
					echo '.';
				}

				do_action( "in_plugin_update_message-{$file}", $plugin, $version_info );
				echo ' <em></em></p></div></td></tr>';
			}
		}

		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @uses api_request()
		 *
		 * @param  mixed  $_data
		 * @param  string $_action
		 * @param  object $_args
		 * @return object $_data
		 */
		public function plugins_api_filter( $_data, $_action = '', $_args = null ) {
			if ( $_action != 'plugin_information' ) {
				return $_data;
			}

			if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {
				return $_data;
			}

			$to_send = array(
				'slug'   => $this->slug,
				'is_ssl' => is_ssl(),
				'fields' => array(
					'banners' => array(),
					'reviews' => false,
					'icons'   => array(),
				),
			);

			// Get the transient where we store the api request for this plugin for 24 hours
			$edd_api_request_transient = $this->get_cached_version_info();

			// If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
			if ( empty( $edd_api_request_transient ) ) {

				$api_response = $this->api_request( 'plugin_information', $to_send );

				// Expires in 3 hours
				$this->set_version_info_cache( $api_response );

				if ( false !== $api_response ) {
					$_data = $api_response;
				}
			} else {
				$_data = $edd_api_request_transient;
			}

			// Convert sections into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
				$_data->sections = $this->convert_object_to_array( $_data->sections );
			}

			// Convert banners into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
				$_data->banners = $this->convert_object_to_array( $_data->banners );
			}

			// Convert icons into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
				$_data->icons = $this->convert_object_to_array( $_data->icons );
			}

			// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
				$_data->contributors = $this->convert_object_to_array( $_data->contributors );
			}

			if ( ! isset( $_data->plugin ) ) {
				$_data->plugin = $this->name;
			}

			return $_data;
		}

		/**
		 * Convert some objects to arrays when injecting data into the update API
		 *
		 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
		 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
		 *
		 * @since 3.6.5
		 *
		 * @param stdClass $data
		 *
		 * @return array
		 */
		private function convert_object_to_array( $data ) {
			$new_data = array();
			foreach ( $data as $key => $value ) {
				$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
			}
			return $new_data;
		}

		/**
		 * Disable SSL verification in order to prevent download update failures
		 *
		 * @param  array  $args
		 * @param  string $url
		 * @return object $array
		 */
		public function http_request_args( $args, $url ) {
			$verify_ssl = $this->verify_ssl();
			if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
				$args['sslverify'] = $verify_ssl;
			}
			return $args;
		}

		/**
		 * Calls the API and, if successfull, returns the object delivered by the API.
		 *
		 * @uses get_bloginfo()
		 * @uses wp_remote_post()
		 * @uses is_wp_error()
		 *
		 * @param  string $_action The requested action.
		 * @param  array  $_data   Parameters for the API action.
		 * @return false|object
		 */
		private function api_request( $_action, $_data ) {
			global $wp_version, $edd_plugin_url_available;

			$verify_ssl = $this->verify_ssl();

			// Do a quick status check on this domain if we haven't already checked it.
			$store_hash = md5( $this->api_url );
			if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
				$test_url_parts = parse_url( $this->api_url );

				$scheme = ! empty( $test_url_parts['scheme'] ) ? $test_url_parts['scheme'] : 'http';
				$host   = ! empty( $test_url_parts['host'] ) ? $test_url_parts['host'] : '';
				$port   = ! empty( $test_url_parts['port'] ) ? ':' . $test_url_parts['port'] : '';

				if ( empty( $host ) ) {
					$edd_plugin_url_available[ $store_hash ] = false;
				} else {
					$test_url                                = $scheme . '://' . $host . $port;
					$response                                = wp_remote_get(
						$test_url,
						array(
							'timeout'   => $this->health_check_timeout,
							'sslverify' => $verify_ssl,
						)
					);
					$edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
				}
			}

			if ( false === $edd_plugin_url_available[ $store_hash ] ) {
				return false;
			}

			$data = array_merge( $this->api_data, $_data );

			if ( $data['slug'] != $this->slug ) {
				return false;
			}

			if ( $this->api_url == trailingslashit( home_url() ) ) {
				return false; // Don't allow a plugin to ping itself
			}

			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => ! empty( $data['license'] ) ? $data['license'] : '',
				'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
				'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
				'version'    => isset( $data['version'] ) ? $data['version'] : false,
				'slug'       => $data['slug'],
				'author'     => $data['author'],
				'url'        => home_url(),
				'beta'       => ! empty( $data['beta'] ),
			);

			$request = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => $verify_ssl,
					'body'      => $api_params,
				)
			);

			if ( ! is_wp_error( $request ) ) {
				$request = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( $request && isset( $request->sections ) ) {
				$request->sections = maybe_unserialize( $request->sections );
			} else {
				$request = false;
			}

			if ( $request && isset( $request->banners ) ) {
				$request->banners = maybe_unserialize( $request->banners );
			}

			if ( $request && isset( $request->icons ) ) {
				$request->icons = maybe_unserialize( $request->icons );
			}

			if ( ! empty( $request->sections ) ) {
				foreach ( $request->sections as $key => $section ) {
					$request->$key = (array) $section;
				}
			}

			return $request;
		}

		/**
		 * If available, show the changelog for sites in a multisite install.
		 */
		public function show_changelog() {
			global $edd_plugin_data;

			if ( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' != $_REQUEST['edd_sl_action'] ) {
				return;
			}

			if ( empty( $_REQUEST['plugin'] ) ) {
				return;
			}

			if ( empty( $_REQUEST['slug'] ) ) {
				return;
			}

			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_die( __( 'You do not have permission to install plugin updates', 'inventory-presser' ), __( 'Error', 'inventory-presser' ), array( 'response' => 403 ) );
			}

			$data         = $edd_plugin_data[ $_REQUEST['slug'] ];
			$version_info = $this->get_cached_version_info();

			if ( false === $version_info ) {
				$api_params = array(
					'edd_action' => 'get_version',
					'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
					'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
					'slug'       => $_REQUEST['slug'],
					'author'     => $data['author'],
					'url'        => home_url(),
					'beta'       => ! empty( $data['beta'] ),
				);

				$verify_ssl = $this->verify_ssl();
				$request    = wp_remote_post(
					$this->api_url,
					array(
						'timeout'   => 15,
						'sslverify' => $verify_ssl,
						'body'      => $api_params,
					)
				);

				if ( ! is_wp_error( $request ) ) {
					$version_info = json_decode( wp_remote_retrieve_body( $request ) );
				}

				if ( ! empty( $version_info ) && isset( $version_info->sections ) ) {
					$version_info->sections = maybe_unserialize( $version_info->sections );
				} else {
					$version_info = false;
				}

				if ( ! empty( $version_info ) ) {
					foreach ( $version_info->sections as $key => $section ) {
						$version_info->$key = (array) $section;
					}
				}

				$this->set_version_info_cache( $version_info );

				// Delete the unneeded option
				delete_option( md5( 'edd_plugin_' . sanitize_key( $_REQUEST['plugin'] ) . '_' . $this->beta . '_version_info' ) );
			}

			if ( isset( $version_info->sections ) ) {
				$sections = $this->convert_object_to_array( $version_info->sections );
				if ( ! empty( $sections['changelog'] ) ) {
					echo '<div style="background:#fff;padding:10px;">' . wp_kses_post( $sections['changelog'] ) . '</div>';
				}
			}

			exit;
		}

		/**
		 * Gets the plugin's cached version information from the database.
		 *
		 * @param  string $cache_key
		 * @return boolean|string
		 */
		public function get_cached_version_info( $cache_key = '' ) {
			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}

			$cache = get_option( $cache_key );

			if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
				return false; // Cache is expired
			}

			// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.

			$cache['value'] = json_decode( $cache['value'] );
			if ( ! empty( $cache['value']->icons ) ) {
				$cache['value']->icons = (array) $cache['value']->icons;
			}

			return $cache['value'];
		}

		/**
		 * Adds the plugin version information to the database.
		 *
		 * @param string $value
		 * @param string $cache_key
		 */
		public function set_version_info_cache( $value = '', $cache_key = '' ) {

			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}

			$data = array(
				'timeout' => strtotime( '+3 hours', time() ),
				'value'   => json_encode( $value ),
			);

			update_option( $cache_key, $data, 'no' );

			// Delete the duplicate option
			delete_option( 'edd_api_request_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) ) );
		}

		/**
		 * Returns if the SSL of the store should be verified.
		 *
		 * @since  1.6.13
		 * @return bool
		 */
		private function verify_ssl() {
			 return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
		}
	}
}
