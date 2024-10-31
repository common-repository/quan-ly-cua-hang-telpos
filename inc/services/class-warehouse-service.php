<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Warehouse_Service extends Base_Service {

	/**
	 * Check Exist warehouse
	 *
	 * @param int $wh_id
	 * @return int
	 */
	public function count_exist( $wh_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_warehouse WHERE wh_id = %d',
				$wh_id
			)
		);
	}
	/**
	 * Check exists.
	 *
	 * @param string $wh_cd
	 * @param int    $wh_id
	 *
	 * @return int
	 */
	public function count_exist_by_wh_cd( string $wh_cd, $wh_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_warehouse WHERE wh_cd = %s';
		$wheres[] = $wh_cd;
		if ( ! empty( $wh_id ) ) {
			$sql     .= ' AND wh_id != %s ';
			$wheres[] = $wh_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) ) > 0;
	}
	/**
	 * Delete warehouse
	 *
	 * @param int $wh_id
	 *
	 * @return void
	 */
	public function delete_warehouse( int $wh_id ): void {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'woocrm_warehouse',
			array(
				'wh_id' => $wh_id,
			)
		);
	}
	/**
	 * Update warehouse.
	 *
	 * @param array @params
	 * @param string        $user_login
	 *
	 * @return void
	 */
	public function update_warehouse( array $params, string $user_login ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocrm_warehouse',
			array(
				'wh_cd'        => $params['wh_cd'],
				'wh_nm'        => $params['wh_nm'],
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'wh_id' => $params['wh_id'],
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
	/**
	 * Insert a new warehouse.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function insert_warehouse( array $params, string $user_login ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocrm_warehouse',
			array(
				'wh_cd'        => $params['wh_cd'],
				'wh_nm'        => $params['wh_nm'],
				'remark'       => $params['remark'] ?? null,
				'active'       => $params['active'] ?? 'Y',
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return $wpdb->insert_id;
	}
	/**
	 * Select one warehouse.
	 *
	 * @param int $wh_id
	 *
	 * @return object
	 */
	public function select_one_warehouse( int $wh_id ): object {
		global $wpdb;

		$sql  = 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_warehouse as a';
		$sql .= ' WHERE wh_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $wh_id ) );
	}
	/**
	 * Select list warehouse.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_warehouse( array $params ): array {
		global $wpdb;

		$sql_select = ' SELECT a.* ';
		$sql_select .= ' ,(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_inventory as b WHERE b.wh_id = a.wh_id) as cnt ';
		$sql_from   = ' FROM ' . $wpdb->prefix . 'woocrm_warehouse as a ';
		$sql_where  = ' WHERE 1 = 1 ';
		$wheres     = array();
		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['wh_cd'] ) ) {
			$sql_where .= ' AND a.wh_cd = %s ';
			$wheres[]   = $params['wh_cd'];
		}
		if ( ! empty( $params['wh_nm'] ) ) {
			$sql_where .= ' AND a.wh_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['wh_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.wh_id ASC';

		return $this->query_paging(
			$sql_select,
			$sql_from,
			$sql_where,
			$wheres,
			WOOCRM_PAGE_SIZE,
			empty( $params['current'] ) ? 0 : $params['current']
		);
	}
}
