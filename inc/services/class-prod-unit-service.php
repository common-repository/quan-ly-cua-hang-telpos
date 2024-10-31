<?php
namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Prod_Unit_Service extends Base_Service {

	/**
	 * Check product unit is exists.
	 *
	 * @param int $unit_id
	 * @return int
	 */
	public function count_exist( $unit_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_prod_unit WHERE unit_id = %d', $unit_id )
		);
	}
	/**
	 * Check product unit is exists.
	 *
	 * @param string $unit_nm
	 * @param int    $unit_id
	 * @return int
	 */
	public function count_exist_by_unit_nm( $unit_nm, $unit_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_prod_unit WHERE lower(unit_nm) = %s';
		$wheres[] = strtolower( $unit_nm );
		if ( ! empty( $unit_id ) ) {
			$sql     .= ' AND unit_id != %s ';
			$wheres[] = $unit_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}
	/**
	 * Delete product unit.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function delete_prod_unit( int $unit_id ): void {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'woocrm_prod_unit',
			array(
				'unit_id' => $unit_id,
			)
		);
	}
	/**
	 * Update product unit.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function update_prod_unit( array $params, string $user_login ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocrm_prod_unit',
			array(
				'unit_nm'      => $params['unit_nm'],
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'unit_id' => $params['unit_id'],
			),
			array( '%s' )
		);
	}

	/**
	 * Insert product unit.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function insert_prod_unit( array $params, $user_login ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_prod_unit',
			array(
				'unit_nm'      => $params['unit_nm'],
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			),
			array( '%s' )
		);
		return $wpdb->insert_id;
	}
	/**
	 * Get one product unit.
	 *
	 * @param int $unit_id
	 *
	 * @return object
	 */
	public function select_one_prod_unit( int $unit_id ): object {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_prod_unit as a';
		$sql .= ' WHERE unit_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $unit_id ) );
	}
	/**
	 * Get list product unit.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_prod_unit( $params ): array {
		global $wpdb;
		$sql_select  = 'SELECT a.* ';
		$sql_select .= ' ,(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_product as b WHERE b.unit_id = a.unit_id) as cnt ';
		$sql_from    = ' FROM ' . $wpdb->prefix . 'woocrm_prod_unit as a ';
		$sql_where   = ' WHERE 1 = 1 ';
		$wheres      = array();
		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['unit_nm'] ) ) {
			$sql_where .= ' AND a.unit_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['unit_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.unit_id DESC';

		return $this->query_paging( $sql_select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
}
