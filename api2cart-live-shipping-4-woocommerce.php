<?php

/*
Plugin Name: API2Cart Live Shipping 4 Woocommerce
Description: This Plugin adds possibility to use API2Cart live shipping services
Version: 1.4.2
Author: API2Cart
Author URI: https://api2cart.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /lang
Text Domain: a2c_ls
*/

/*
API2Cart Live Shipping 4 Woocommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

API2Cart Live Shipping 4 Woocommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with API2Cart webhook helper. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LIVE_SHIPPING_SERVICE_VERSION' ) ) {
	define( 'LIVE_SHIPPING_SERVICE_VERSION', '1.4.1' );
	define( 'LIVE_SHIPPING_SERVICE_POST_TYPE', 'live_shipping_method' );

	require_once 'app' . DIRECTORY_SEPARATOR . 'A2c_Live_Shipping_Exception.php';
	require_once 'includes' . DIRECTORY_SEPARATOR . 'class-a2c-live-shipping-rest-api-controller.php';

	/**
	 * @var A2c_Live_Shipping_Exception|null $a2c_live_shipping_exception
	 */
	$a2c_live_shipping_exception = null;

	/**
	 * Register routes.
	 *
	 * @since 1.3.0
	 */
	function register_rest_api_routes_a2c_ls() {
		$restApiController = new A2C_Live_Shipping_V1_REST_API_Controller();
		$restApiController->register_routes();
	}

	add_action( 'rest_api_init', 'register_rest_api_routes_a2c_ls' );

	function a2c_live_shipping_get_cache_files() {
		$siteId = get_current_blog_id();

		$default_blog_files = array(
			realpath( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'A2c_Live_Shipping_Service_Hash.php' ),
			realpath( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'A2c_Live_Shipping_Services.php' ),
		);

		if ( $siteId === 1 ) {
			return $default_blog_files;
		} else {
			$blogCacheDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'blogs' . DIRECTORY_SEPARATOR . $siteId;
			$appDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

			if (!is_writable($appDir)) {
				throw new A2c_Live_Shipping_Exception( __( 'Can\'t generate shipping services cache. Dir "' . preg_replace('/^.*\/(plugins(.*))$/', '$2', $appDir) . '" is not writable. ', 'a2c_ls' ) );
			}

			if ( ! file_exists( $blogCacheDir ) ) {
				mkdir( $blogCacheDir, 0755, true );
			}

			$files = array();

			foreach ( $default_blog_files as $file ) {
				$path    = $blogCacheDir . DIRECTORY_SEPARATOR . basename( $file );
				$files[] = $path;

				if ( ! file_exists( $path ) ) {
					file_put_contents( $path, "<?php\r\n\r\nif ( ! defined( 'ABSPATH' ) ) {\r\n\texit;\r\n}\r\n\r\n" );
				}
			}

			return $files;
		}
	}

	function a2c_live_shipping_validate_WooCommerce() {
		if ( class_exists( 'WooCommerce' ) ) {
			$version = WooCommerce::instance()->version;
		}

		if ( empty( $version ) || version_compare( $version, '2.7' ) === - 1 ) {
			throw new A2c_Live_Shipping_Exception( __( 'Woocommerce 2.7+ is required. ', 'a2c_ls' ) );
		}

		foreach ( a2c_live_shipping_get_cache_files() as $file ) {
			if ( ! is_writable( $file ) ) {
				throw new A2c_Live_Shipping_Exception( __( 'Can\'t generate shipping services cache. File "' . preg_replace('/^.*\/(plugins(.*))$/', '$2', $file) . '" is not writable. ', 'a2c_ls' ) );
			}
		}

		return true;
	}

	function a2c_live_shipping_services_add( $services ) {
		$new_services = array();
		$secret       = get_option( 'live_shipping_service_secret' );

		list ($hashFile, $servicesFile) = a2c_live_shipping_get_cache_files();

		require $hashFile;

		/**
		 * @var WP_Post[]
		 */
		$servicePosts = get_posts( [ 'numberposts' => - 1, 'post_type' => LIVE_SHIPPING_SERVICE_POST_TYPE ] );
		$dataToHash   = '';

		foreach ( $servicePosts as $servicePost ) {
			$dataToHash .= json_encode( $servicePost );
		}

		$hash = hash_hmac( 'sha256', $dataToHash . file_get_contents( $servicesFile ), $secret );

		if ( empty( $_a2c_live_shipping_services_hash ) || $_a2c_live_shipping_services_hash !== $hash ) {
			$classes = array();

			foreach ( $servicePosts as $shipping_service_post ) {
				$id   = 'live_shipping_' . $shipping_service_post->ID;
				$data = json_decode( $shipping_service_post->post_content, true );

				if ( isset( $data['config'] ) ) {
					$config = $data['config'];
				} else {
					$config = array();
				}

				$settings = [
					'title'        => $shipping_service_post->post_title,
					'description'  => $shipping_service_post->post_excerpt,
					'callback_url' => $data['callback_url'],
					'secret'       => $secret,
					'config'       => $config,
				];

				add_option( $id . '_settings', $settings );

				$classes[] = <<<EOT
class A2c_Live_Shipping_Service_{$shipping_service_post->ID} extends A2c_Live_Shipping_Service
{
    public \$id = '{$id}';
}
EOT;
			}

			$defaultContent      = "<?php\r\n\r\nif ( ! defined( 'ABSPATH' ) ) {\r\n\texit;\r\n}\r\n\r\n";
			$servicesFileContent = $defaultContent . implode( "\r\n\r\n", $classes );
			$hash                = hash_hmac( 'sha256', $dataToHash . $servicesFileContent, $secret );
			$hashFileContent     = $defaultContent . '$_a2c_live_shipping_services_hash=\'' . addslashes( $hash ) . '\';';

			file_put_contents( $hashFile, $hashFileContent );
			file_put_contents( $servicesFile, $servicesFileContent );
		}

		require_once $servicesFile;

		foreach ( $servicePosts as $shipping_service_post ) {
			$new_services[ 'live_shipping_' . $shipping_service_post->ID ] = 'A2c_Live_Shipping_Service_' . $shipping_service_post->ID;
		}

		return $services + $new_services;
	}

	function a2c_live_shipping_migrate( $currentVersion )
	{
		switch ($currentVersion) {
			case '1.1':
				foreach ( get_posts( [ 'numberposts' => - 1, 'post_type' => LIVE_SHIPPING_SERVICE_POST_TYPE, ] ) as $shipping_service_post ) {
					$post = $shipping_service_post->to_array();

					$data = json_decode($post['post_content'], true);

					$data['config']['requiredDestAddrFields'][] = 'state';

					$post['post_content'] = json_encode($data);

					wp_update_post($post);

					delete_option('live_shipping_' . $post['ID'] . '_settings');
				}

				break;
		}

		update_option('live_shipping_service_version', LIVE_SHIPPING_SERVICE_VERSION);
	}

	function a2c_live_shipping_init()
	{
		$version_in_db = get_option( 'live_shipping_service_version' );

		if (empty($version_in_db)) {
			a2c_live_shipping_activate();
		} elseif ( LIVE_SHIPPING_SERVICE_VERSION !== $version_in_db ) {
			a2c_live_shipping_migrate($version_in_db);
		}

		global $a2c_live_shipping_exception;

		try {
			a2c_live_shipping_validate_WooCommerce();

			register_post_type(
				LIVE_SHIPPING_SERVICE_POST_TYPE,
				array(
					'public'              => false,
					'hierarchical'        => false,
					'has_archive'         => false,
					'exclude_from_search' => false,
					'rewrite'             => false,
					'query_var'           => false,
					'delete_with_user'    => false,
					'_builtin'            => true,
				)
			);

			require_once 'app' . DIRECTORY_SEPARATOR . 'A2c_Live_Shipping_Service.php';
			add_filter( 'woocommerce_shipping_methods', 'a2c_live_shipping_services_add' );

		} catch ( A2c_Live_Shipping_Exception $a2c_live_shipping_exception ) {
		}
	}

	function a2c_live_shipping_error()
	{
		global $a2c_live_shipping_exception;

		if ( $a2c_live_shipping_exception !== null ) {
			echo '
				<div class="error notice">
					<p>API2Cart Live Shipping 4 Woocommerce notice: <b>' . $a2c_live_shipping_exception->getMessage() . '</b></p>
				</div>
			';
		}
	}

	function a2c_live_shipping_activate() {
		global $a2c_live_shipping_exception;

		try {
			a2c_live_shipping_validate_WooCommerce();
		} catch ( A2c_Live_Shipping_Exception $a2c_live_shipping_exception ) {
			die ( $a2c_live_shipping_exception->getMessage() );
		}

		if ( is_multisite() && is_network_admin() ) {
			$sites = get_sites();

			foreach ( $sites as $site ) {
				_activatePlugin( $site->blog_id, true );
			}

			restore_current_blog();
		} else {
			_activatePlugin();
		}
	}

	function a2c_live_shipping_deactivate() {
		if ( is_multisite() && is_network_admin() ) {
			$sites      = get_sites();
			$pluginName = isset( $GLOBALS['plugin'] ) ? $GLOBALS['plugin'] : '';

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$activePlugins = (array) get_option( 'active_plugins', array() );

				if ( ( $key = array_search( $pluginName, $activePlugins ) ) !== false ) {
					unset( $activePlugins[ $key ] );
					update_option( 'active_plugins', $activePlugins );
				}

				update_option( 'live_shipping_service_active', false );
			}

			restore_current_blog();
		} else {
			update_option( 'live_shipping_service_active', false );
		}
	}

	function a2c_live_shipping_uninstall()
	{
		/**
		 * @global $wpdb wpdb Database Access Abstraction Object
		 */
		global $wpdb;

		foreach (
			get_posts( [
				'numberposts' => - 1,
				'post_type'   => LIVE_SHIPPING_SERVICE_POST_TYPE,
			] ) as $shipping_service_post
		) {
			$id = 'live_shipping_' . $shipping_service_post->ID;

			$wpdb->query("DELETE FROM `{$wpdb->prefix}woocommerce_shipping_zone_methods` WHERE `method_id` = '{$id}'");
		}

		$wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'posts` WHERE `post_type` = "' . LIVE_SHIPPING_SERVICE_POST_TYPE . '"' );
		delete_option( 'live_shipping_service_version' );
		delete_option( 'live_shipping_service_secret' );
		delete_option( 'live_shipping_service_active' );
	}

	/**
	 * @param int  $siteId      Site Id
	 * @param bool $isMultisite Is Multisite Enabled
	 */
	function _activatePlugin($siteId = 1, $isMultisite = false)
	{
		if ($isMultisite) {
			switch_to_blog($siteId);
		}

		update_option('live_shipping_service_version', LIVE_SHIPPING_SERVICE_VERSION);
		update_option('live_shipping_service_active', true);

		if (empty(get_option('live_shipping_service_secret'))) {
			update_option('live_shipping_service_secret', wp_generate_password(50, true, true));
		}
	}

	register_activation_hook( __FILE__, 'a2c_live_shipping_activate' );
	register_uninstall_hook( __FILE__, 'a2c_live_shipping_uninstall' );
	register_deactivation_hook( __FILE__, 'a2c_live_shipping_deactivate' );

	add_action( 'plugins_loaded', 'a2c_live_shipping_init', 10, 3 );
	add_action( 'admin_notices', 'a2c_live_shipping_error' );

}
