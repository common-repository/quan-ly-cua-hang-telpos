<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Pur_Debt_Service extends Base_Service {


	/**
	 * Sum totoal remain debt.
	 *
	 * @return int
	 */
	public function sum_total_debt_remain(): ?int {
		global $wpdb;
		$sql  = ' SELECT (IFNULL(SUM(total_value), 0) - IFNULL(SUM(discount_val), 0) - IFNULL((SELECT SUM(total_payed) FROM ' . $wpdb->prefix . 'woocrm_pur_debt), 0)) as remain ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order';
		return $wpdb->get_var( $wpdb->prepare( $sql, array() ) );
	}

	/**
	 * Insert pur debt by pur order.
	 *
	 * @param array $params
	 *
	 * @return int pur debt id
	 */
	public function insert_debt_by_pur_order( array $params ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_pur_debt',
			array(
				'pay_date'     => $params['pay_date'],
				'partner_id'   => $params['partner_id'],
				'total_payed'  => $params['total_payed'],
				'user_id'      => $params['user_id'],
				'remark'       => $params['remark'] ?? null,
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $params['created_user'],
				'updated_user' => $params['updated_user'],
			)
		);
		$debt_id = $wpdb->insert_id;

		$wpdb->insert(
			$wpdb->prefix . 'woocrm_pur_debt_item',
			array(
				'debt_id'     => $debt_id,
				'pur_id'      => $params['pur_id'],
				'total_payed' => $params['total_payed'],
			)
		);

		return $debt_id;
	}
	/**
	 * Insert purchase debt.
	 *
	 * @param array $params Is parameter request.
	 *
	 * @return int The purchase id after insert.
	 */
	public function insert_pur_debt( array $params = array(), string $user_login = '' ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_pur_debt',
			array(
				'pay_date'     => $params['pay_date'],
				'partner_id'   => $params['partner_id'],
				'total_payed'  => $params['total_payed'],
				'user_id'      => $params['user_id'],
				'remark'       => $params['remark'] ?? null,
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			)
		);
		$debt_id = $wpdb->insert_id;

		$total = $params['total_payed'];

		$sql  = 'SELECT a.pur_id, a.total_value, SUM(b.total_payed) as total_payed';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order as a';
		$sql .= ' LEFT JOIN ' . $wpdb->prefix . 'woocrm_pur_debt_item as b ON b.pur_id = a.pur_id ';
		$sql .= ' WHERE a.partner_id = %d ';
		$sql .= ' GROUP BY a.pur_id';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params['partner_id'] ) );

		foreach ( $rows as $row ) {
			if ( $row->total_value > $row->total_payed && $total > 0 ) {

				$payment = 0;
				if ( $total - ( $row->total_value - $row->total_payed ) >= 0 ) {
					$payment = $row->total_value - $row->total_payed;
				}
				if ( $total - ( $row->total_value - $row->total_payed ) < 0 ) {
					$payment = $total;
				}
				$wpdb->insert(
					$wpdb->prefix . 'woocrm_pur_debt_item',
					array(
						'debt_id'     => $debt_id,
						'pur_id'      => $row->pur_id,
						'total_payed' => $payment,
					)
				);
				$total = $total - $payment;
			}
		}
		return $debt_id;
	}

	/**
	 * Delete purchase debt.
	 *
	 * @param int $debt_id
	 *
	 * @return void
	 */
	public function delete_pur_debt( int $debt_id ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocrm_pur_debt_item', array( 'debt_id' => $debt_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocrm_pur_debt', array( 'debt_id' => $debt_id ) );
	}

	/**
	 * @param array $params
	 *
	 * return array
	 */
	public function select_list_payed( $params ): array {
		global $wpdb;

		$select    = 'SELECT a.*, b.pay_date, c.pur_cd, d.display_name as user_nm ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_pur_debt_item as a ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_pur_order as c ON c.pur_id = a.pur_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_pur_debt as b ON b.debt_id = a.debt_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'users as d ON d.ID = b.user_id ';
		$sql_where = ' WHERE b.partner_id = %d ORDER BY a.debt_id DESC';
		$wheres[]  = $params['partner_id'];

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
	/**
	 * @param int $partner_id
	 *
	 * @return int
	 */
	public function select_total_value( int $partner_id ): int {
		global $wpdb;

		$sql  = 'SELECT (SUM(a.total_value) - SUM(a.discount_val))';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order as a';
		$sql .= ' WHERE a.partner_id = %d ';
		$sql .= ' GROUP BY a.partner_id';
		return $wpdb->get_var( $wpdb->prepare( $sql, $partner_id ) ) ?? 0;
	}
	/**
	 * @param int $partner_id
	 *
	 * @return int
	 */
	public function select_total_payed( int $partner_id ): int {
		global $wpdb;

		$sql  = 'SELECT SUM(a.total_payed)';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_debt as a';
		$sql .= ' WHERE a.partner_id = %d ';
		$sql .= ' GROUP BY a.partner_id';
		return $wpdb->get_var( $wpdb->prepare( $sql, $partner_id ) ) ?? 0;
	}
	/**
	 * @param int $pur_id
	 * @return int
	 */
	public function select_total_payed_by_pur_order( int $pur_id ): int {
		global $wpdb;

		$sql  = 'SELECT SUM(a.total_payed)';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_debt_item as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_pur_debt as b ON b.debt_id = a.debt_id ';
		$sql .= ' WHERE a.pur_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $sql, array( $pur_id ) ) ) ?? 0;
	}
	/**
	 * check exists purchase debt with ID
	 *
	 * @param int $debt_id
	 * @return int
	 */
	public function count_exists( $debt_id ): int {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_pur_debt WHERE debt_id = %d', $debt_id ) );
	}
}
