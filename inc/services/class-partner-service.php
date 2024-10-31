<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Partner_Service extends Base_Service {

	/**
	 * Count exists by partner code.
	 *
	 * @param string $partner_cd
	 * @param int    $partner_id
	 *
	 * @return int count exists.
	 */
	public function count_exist_by_partner_cd( string $partner_cd, $partner_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_partner WHERE partner_cd = %s';
		$wheres[] = $partner_cd;
		if ( ! empty( $partner_id ) ) {
			$sql     .= ' AND partner_id != %s ';
			$wheres[] = $partner_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}

	/**
	 * Check is exists partner.
	 *
	 * @param int $partner_id
	 * @return int
	 */
	public function count_exist( $partner_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_partner WHERE partner_id = %d', $partner_id )
		);
	}

	/**
	 * Delete partner.
	 *
	 * @param int $partner_id
	 *
	 * @return void
	 */
	public function delete_partner( int $partner_id ): void {
		global $wpdb;
		$debts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "woocrm_pur_debt WHERE partner_id = %s", $partner_id ) );
		if ( ! empty( $debts ) ) {
			foreach( $debts as $item ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "woocrm_pur_debt_item WHERE debt_id = %d", [ $item->debt_id ] ) );
			}
			$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "woocrm_pur_debt WHERE partner_id = %d", $partner_id ) );
		}
		$orders = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "woocrm_pur_order WHERE partner_id = %d ", $partner_id ) );
		if ( ! empty( $orders ) ) {
			foreach( $orders as $item ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "woocrm_pur_item WHERE pur_id = %d", [ $item->pur_id ] ) );
			}
			$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "woocrm_pur_order WHERE partner_id = %d", $partner_id ) );
		}
		$wpdb->delete( $wpdb->prefix . 'woocrm_partner', array( 'partner_id' => $partner_id ) );
	}

	/**
	 * Update partner.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_partner( $params, $user_login ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocrm_partner',
			array(
				'partner_cd'   => $params['partner_cd'],
				'partner_nm'   => $params['partner_nm'],
				'address'      => $params['address'],
				'phone'        => $params['phone'],
				'fax'          => $params['fax'],
				'email'        => $params['email'],
				'active'       => $params['active'],
				'remark'       => $params['remark'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'partner_id' => $params['partner_id'],
			),
			array( '%s' )
		);
	}

	/**
	 * Insert partner.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return int partner id after insert.
	 */
	public function insert_partner( $params, $user_login ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocrm_partner',
			array(
				'partner_cd'   => $params['partner_cd'],
				'partner_nm'   => $params['partner_nm'],
				'address'      => $params['address'],
				'phone'        => $params['phone'],
				'fax'          => $params['fax'] ?? null,
				'email'        => $params['email'] ?? null,
				'active'       => $params['active'] ?? 'Y',
				'remark'       => $params['remark'] ?? null,
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
	 * Get one partner.
	 *
	 * @param int $partner_id
	 *
	 * @return object
	 */
	public function select_one_partner( int $partner_id ) {
		 global $wpdb;

		$sql  = 'SELECT a.* FROM ' . $wpdb->prefix . 'woocrm_partner as a';
		$sql .= ' WHERE partner_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $partner_id ) );
	}
	/**
	 * Get list partner.
	 *
	 * @param array $params
	 *
	 * return array
	 */
	public function select_list_partner( $params ): array {
		global $wpdb;

		$select    = '  SELECT a.*';
        $select   .= ' , (SELECT (IFNULL(SUM(o.total_value), 0) - IFNULL((SELECT SUM(d.total_payed) FROM ' . $wpdb->prefix . 'woocrm_pur_debt as d WHERE d.partner_id = a.partner_id), 0)) ';
        $select   .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order AS o WHERE o.partner_id = a.partner_id) as total_remain ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_partner as a ';
		$sql_where = ' WHERE 1 = 1 ';
		$wheres    = array();
		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['partner_cd'] ) ) {
			$sql_where .= ' AND a.partner_cd = %s ';
			$wheres[]   = $params['partner_cd'];
		}
		if ( ! empty( $params['partner_nm'] ) ) {
			$sql_where .= ' AND a.partner_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['partner_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['phone'] ) ) {
			$sql_where .= ' AND a.phone = %s ';
			$wheres[]   = $params['phone'];
		}
		if ( ! empty( $params['email'] ) ) {
			$sql_where .= ' AND a.email LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['email'] ) ) ) . '%';
		}
        if ( ! empty( $params['total_remain'] ) ) {
            $sql_where .= ' AND (SELECT (IFNULL(SUM(o.total_value), 0) - IFNULL(SUM(o.discount_val), 0) - IFNULL((SELECT SUM(d.total_payed) FROM ' . $wpdb->prefix . 'woocrm_pur_debt as d WHERE d.partner_id = a.partner_id), 0)) ';
            $sql_where .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order AS o WHERE o.partner_id = a.partner_id) >= %d ';
            $wheres[]  = $params['total_remain'];
        }
        $sql_where .= ' ORDER BY a.partner_id DESC';

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}

}
