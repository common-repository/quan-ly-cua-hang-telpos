<?php
/**
 *  Woo CRM Initializer
 *
 * @since   1.0.0
 * @package woocrm
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Admin Menu.
 *
 * @return void
 */
function woocrm_init() {
	add_menu_page( __( 'QL Bán hàng', 'telsky' ), __( 'QL Bán hàng', 'telsky' ), 'read', 'woocrm', 'woocrm_admin_page', 'dashicons-admin-post', '1.0' );
}
add_action( 'admin_menu', 'woocrm_init' );

/**
 * Init Admin Page.
 *
 * @return void
 */
function woocrm_admin_page() {
	require_once plugin_dir_path( __FILE__ ) . 'templates/app.php';
}

add_action( 'admin_enqueue_scripts', 'woocrm_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function woocrm_admin_enqueue_scripts() {
	wp_enqueue_style( 'woocrm-style', plugin_dir_url( __FILE__ ) . 'build/index.css' );
	wp_enqueue_script( 'woocrm-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element' ), '1.0.37', true );
	$user_role = array();
	if ( is_user_logged_in() ) {
		$user_role = get_user_meta( get_current_user_id(), 'telsky_user_role' );
	}
    $current_user = wp_get_current_user();
	wp_localize_script(
		'woocrm-script',
		'wpApiSettings',
		array(
			'root'           => esc_url_raw( rest_url() ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'access_token'   => get_user_meta( get_current_user_id(), 'telsky_api_auth_bearer_token', true ),
			'user_id'        => get_current_user_id(),
			'base_url'       => rest_url( '/woocrm/v1/' ),
			'roles'          => $user_role,
			'is_check_admin' => get_option( 'telsky_api_is_check_with_admin' ),
            'username'        => $current_user->user_login
		),
	);
	$lang = include WOOCRM_PATH . 'i18n/lang.php';
	wp_localize_script(
		'woocrm-script',
		'lang',
		$lang
	);
}



