<?php
/**
 * Plugin Name: Quản lý cửa hàng TELPOS
 * Plugin URI: https://telsky.vn/huong-dan-su-dung-plugin-telsky-pos/plugin-telsky-pos/
 * Description: Hỗ trợ quản lý nhập hàng, This is a plugin supported woocommerce plugin to contact, order, partner management
 * Author: telsky
 * Author URI: https://telsky.vn
 * Text Domain: telsky
 * Version: 1.0.8
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package woocrm
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

defined( 'WOOCRM_PATH' ) || define( 'WOOCRM_PATH', plugin_dir_path( __FILE__ ) );
defined( 'WOOCRM_PAGE_SIZE' ) || define( 'WOOCRM_PAGE_SIZE', 10 );

require_once plugin_dir_path( __FILE__ ) . 'inc/woocrm-database-functions.php';

register_activation_hook( __FILE__, 'woocrm_activate' );

function woocrm_activate() {
	woocrm_create_table();
	if ( ! get_option( 'telsky_opt_com_nm' ) ) {
		add_option( 'telsky_opt_com_nm', 'CÔNG TY TNHH CÔNG NGHỆ TELSKY' );
	}
	if ( ! get_option( 'telsky_opt_com_email' ) ) {
		add_option( 'telsky_opt_com_email', 'info@telsky.vn' );
	}
	if ( ! get_option( 'telsky_opt_com_phone' ) ) {
		add_option( 'telsky_opt_com_phone', '0977788382' );
	}
	if ( ! get_option( 'telsky_opt_com_address' ) ) {
		add_option( 'telsky_opt_com_address', 'CT4 Thanh Bình Tower, Võ Cường, Bắc Ninh' );
	}
	if ( ! get_option( 'telsky_prefix_code_product' ) ) {
		add_option( 'telsky_prefix_code_product', 'SP' );
	}
	if ( ! get_option( 'telsky_prefix_code_customer' ) ) {
		add_option( 'telsky_prefix_code_customer', 'KH' );
	}
	if ( ! get_option( 'telsky_prefix_code_partner' ) ) {
		add_option( 'telsky_prefix_code_partner', 'NCC' );
	}
	if ( ! get_option( 'telsky_prefix_code_pur' ) ) {
		add_option( 'telsky_prefix_code_pur', 'DNH' );
	}
	if ( ! get_option( 'telsky_prefix_code_sale' ) ) {
		add_option( 'telsky_prefix_code_sale', 'DBH' );
	}
	if ( ! get_option( 'telsky_prefix_code_quo' ) ) {
		add_option( 'telsky_prefix_code_quo', 'BG' );
	}
	if ( ! get_option( 'telsky_opt_warn_quantity' ) ) {
		add_option( 'telsky_opt_warn_quantity', '5' );
	}
}

/**
 * Deactivation hook.
 */
function woocrm_deactivate() {
	// woocrm_drop_table();
}
register_deactivation_hook( __FILE__, 'woocrm_deactivate' );

require_once plugin_dir_path( __FILE__ ) . 'init.php';
require_once plugin_dir_path( __FILE__ ) . 'routes.php';
