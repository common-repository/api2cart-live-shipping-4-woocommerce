=== API2Cart Live Shipping 4 Woocommerce ===
Contributors: api2cartdev
Tags: WooCommerce plugin, woocommerce live shipping, live shipping rates, api2cart, woocommerce, WooCommerce Integration
Requires PHP: 5.6
Requires at least: 4.5
Tested up to: 6.6
Stable tag: 1.4
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin allows to use of real-time shipping rates provided by third-party shipping services.
Elevate your eCommerce operations with API2Cart's comprehensive shipping service management capabilities. Create and manage multiple shipping services per store, effortlessly disabling calculations for any service. Additionally, specify custom origin addresses for each shipping service, ensuring seamless shipping from your preferred locations.
To create new live shipping service use API2Cart api method [basket.live_shipping_service.create](https://api2cart.com/docs/#/basket/BasketLiveShippingServiceCreate).

WHAT IS API2Cart?

[API2Cart](https://api2cart.com/) is a unified API to integrate with 40+ shopping carts and marketplaces including Magento, Shopify, BigCommerce, WooCommerce, PrestaShop, Demandware, Amazon, and others.
We are also constantly expanding our list of platforms to meet the needs of our customers.

== Experience the API2Cart Advantage ==
 * **Seamless Integration:** Integrate seamlessly with WooCommerce and eliminate the hassle of managing multiple platforms.
 * **Unified API:** Manage 40+ shopping carts and marketplaces with a single API.
 * **Comprehensive Data Management:** Easily retrieve, add, delete, update, and synchronize store data such as orders, customers, products, baskets, and categories from all or any of the supported platforms. See [all methods and platforms](https://api2cart.com/supported-api-methods/) we support.
 * **Developer-Friendly Tools:** Utilize our [SDK](https://api2cart.com/docs/sdk/) and [detailed documentation](https://api2cart.com/docs/) to connect multiple shopping carts and marketplaces with ease.

Take Your eCommerce Business to New Heights
If you have any questions, feel free to contact us at [manager@api2cart.com](mailto:manager@api2cart.com) or [submit the form](https://api2cart.com/contact-us/).
== Installation ==

1. Upload the `api2cart-live-shipping-4-woocommerce` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does this plugin require the Woocommerce plugin? =

Yes, the Woocommerce plugin must be installed and active.

== Screenshots ==

1. Admin settings

== Changelog ==

= 1.0 =
* First official release

= 1.1 =
* Handle pre-estimate requests

= 1.2 =
* Added support of Shipping Zones (Please make sure you have selected live shipping service in the WooCommerce shipping zone settings);
* The destination address field "state" is now required for countries that have such field in the checkout form.

= 1.3 =
* Added support for integration with Woocommerce via REST API
* Added new REST API endpoints:
  * GET wc-a2c/v1/live-shipping-rates - retrieve all live shipping reates services,
  * POST wc-a2c/v1/live-shipping-rates - create live shipping reates service,
  * DELETE wc-a2c/v1/live-shipping-rates - delete live shipping reates service,
  * GET wc-a2c/v1/live-shipping-rates/configs - get shipping service configs

= 1.4 =
* Improved compatibility with third-party plugins

= 1.4.1 =
* Fix plugin compatibility

= 1.4.2 =
* Fixed compatibility with "WooCommerce Delivery Slots" plugin.
