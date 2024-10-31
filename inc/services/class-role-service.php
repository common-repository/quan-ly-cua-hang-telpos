<?php
namespace Telsky\Woocrm\Services;

use WP_REST_Response;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Role_Service extends Base_Service {
	/**
	 * Select list role
	 *
	 * @return array
	 */
	public function select_list_role(): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_role', array() )
		);
	}
}
