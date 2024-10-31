<?php
namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Prod_Type_Service extends Base_Service {
	/**
	 * check product type is exists
	 *
	 * @param int $prod_type_id
	 * @return int count of exists
	 */
	public function count_exist( $prod_type_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_prod_type WHERE prod_type_id = %d', $prod_type_id )
		);
	}
	/**
	 * check product type code is exists
	 *
	 * @param string $prod_type_nm
	 * @param int    $prod_type_id
	 * @return int count of exists
	 */
	public function count_exist_by_prod_type_nm( $prod_type_nm, $prod_type_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_prod_type WHERE lower(prod_type_nm) = %s';
		$wheres[] = strtolower( $prod_type_nm );
		if ( ! empty( $prod_type_id ) ) {
			$sql     .= ' AND prod_type_id != %s ';
			$wheres[] = $prod_type_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}
	/**
	 * Delete product type.
	 *
	 * @param int $prod_type_id
	 *
	 * @return void
	 */
	public function delete_prod_type( int $prod_type_id ): void {
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'woocrm_prod_type',
			array(
				'prod_type_id' => $prod_type_id,
			)
		);
	}
	/**
	 * Update product type.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_prod_type( array $params, string $user_login ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocrm_prod_type',
			array(
				'prod_type_nm' => $params['prod_type_nm'],
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'prod_type_id' => $params['prod_type_id'],
			),
			array( '%s' )
		);
	}

	/**
	 * Insert product type.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int id product type after insert
	 */
	public function insert_prod_type( array $params, string $user_login ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_prod_type',
			array(
				'prod_type_nm' => $params['prod_type_nm'],
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
	 * Get one product type.
	 *
	 * @param int $prod_type_id
	 *
	 * @return object
	 */
	public function select_one_prod_type( $prod_type_id ): object {
		global $wpdb;
		$sql  = 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_prod_type as a';
		$sql .= ' WHERE prod_type_id = %d ';

		return $wpdb->get_row( $wpdb->prepare( $sql, $prod_type_id ) );
	}
	/**
	 * Get list product type.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_prod_type( $params ): array {
		global $wpdb;

		$select    = '  SELECT a.*';
		$select   .= ' ,(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_product as b WHERE b.prod_type_id = a.prod_type_id) as cnt ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_prod_type as a ';
		$sql_where = ' WHERE 1 = 1 ';
		$wheres    = array();
		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['prod_type_nm'] ) ) {
			$sql_where .= ' AND a.prod_type_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['prod_type_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.prod_type_id DESC';

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
}
