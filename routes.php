<?php

require_once WOOCRM_PATH . 'inc/class-utility.php';
require_once WOOCRM_PATH . 'inc/class-hook.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-auth.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-controller.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-prod-type.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-prod-unit.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-product.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-quotation.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-customer.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-warehouse.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-partner.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-user.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-inventory.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-pur-order.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-sale-order.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-dashboard.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-pur-debt.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-sale-debt.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-role.php';
require_once WOOCRM_PATH . 'inc/class-woocrm-setting.php';
require_once WOOCRM_PATH . 'inc/services/class-base-service.php';
require_once WOOCRM_PATH . 'inc/services/class-inventory-service.php';
require_once WOOCRM_PATH . 'inc/services/class-pur-order-service.php';
require_once WOOCRM_PATH . 'inc/services/class-sale-order-service.php';
require_once WOOCRM_PATH . 'inc/services/class-customer-service.php';
require_once WOOCRM_PATH . 'inc/services/class-partner-service.php';
require_once WOOCRM_PATH . 'inc/services/class-prod-type-service.php';
require_once WOOCRM_PATH . 'inc/services/class-prod-unit-service.php';
require_once WOOCRM_PATH . 'inc/services/class-product-service.php';
require_once WOOCRM_PATH . 'inc/services/class-quotation-service.php';
require_once WOOCRM_PATH . 'inc/services/class-warehouse-service.php';
require_once WOOCRM_PATH . 'inc/services/class-pur-debt-service.php';
require_once WOOCRM_PATH . 'inc/services/class-sale-debt-service.php';
require_once WOOCRM_PATH . 'inc/services/class-role-service.php';

new Telsky\Woocrm\Woocrm_Auth();
new Telsky\Woocrm\Woocrm_Hook();

// Function to register our new routes from the controller.
function prefix_register_rest_routes() {
	new Telsky\Woocrm\Controller\Woocrm_Prod_Unit();
	new Telsky\Woocrm\Controller\Woocrm_Customer();
	new Telsky\Woocrm\Controller\Woocrm_Dashboard();
	new Telsky\Woocrm\Controller\Woocrm_Inventory();
	new Telsky\Woocrm\Controller\Woocrm_Partner();
	new Telsky\Woocrm\Controller\Woocrm_Prod_Type();
	new Telsky\Woocrm\Controller\Woocrm_Product();
	new Telsky\Woocrm\Controller\Woocrm_Pur_Order();
	new Telsky\Woocrm\Controller\Woocrm_Quotation();
	new Telsky\Woocrm\Controller\Woocrm_Sale_Order();
	new Telsky\Woocrm\Controller\Woocrm_User();
	new Telsky\Woocrm\Controller\Woocrm_Warehouse();
	new Telsky\Woocrm\Controller\Woocrm_Pur_Debt();
	new Telsky\Woocrm\Controller\Woocrm_Sale_Debt();
	new Telsky\Woocrm\Controller\Woocrm_Role();
	new Telsky\Woocrm\Controller\Woocrm_Setting();
}

add_action( 'rest_api_init', 'prefix_register_rest_routes' );
