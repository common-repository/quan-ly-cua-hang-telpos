<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Sale_Debt_Service extends Base_Service {


	/**
	 * Sum totoal remain debt.
	 *
	 * @return int
	 */
	public function sum_total_debt_remain(): ?int {
		global $wpdb;
		$sql  = ' SELECT (IFNULL(SUM(total_value), 0) - IFNULL(SUM(discount_val), 0) - IFNULL((SELECT SUM(total_payed) FROM ' . $wpdb->prefix . 'woocrm_sale_debt), 0)) as remain ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_order';
		return $wpdb->get_var( $wpdb->prepare( $sql, array() ) );
	}

	/**
	 * Insert sale debt by sale order.
	 *
	 * @param array $params
	 *
	 * @return int sale debt id
	 */
	public function insert_debt_by_sale_order( array $params ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_sale_debt',
			array(
				'pay_date'     => $params['pay_date'],
				'customer_id'  => $params['customer_id'],
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
			$wpdb->prefix . 'woocrm_sale_debt_item',
			array(
				'debt_id'     => $debt_id,
				'sale_id'     => $params['sale_id'],
				'total_payed' => $params['total_payed'],
			)
		);

		return $debt_id;
	}

	/**
	 * Insert sale debt.
	 *
	 * @param array $params Is parameter request.
	 *
	 * @return int The sale id after insert.
	 */
	public function insert_sale_debt( array $params = array(), string $user_login = '' ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_sale_debt',
			array(
				'pay_date'     => $params['pay_date'],
				'customer_id'  => $params['customer_id'],
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

		$sql  = 'SELECT a.sale_id, a.total_value, SUM(b.total_payed) as total_payed';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_order as a';
		$sql .= ' LEFT JOIN ' . $wpdb->prefix . 'woocrm_sale_debt_item as b ON b.sale_id = a.sale_id ';
		$sql .= ' WHERE a.customer_id = %d ';
		$sql .= ' GROUP BY a.sale_id';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params['customer_id'] ) );

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
					$wpdb->prefix . 'woocrm_sale_debt_item',
					array(
						'debt_id'     => $debt_id,
						'sale_id'     => $row->sale_id,
						'total_payed' => $payment,
					)
				);
				$total = $total - $payment;
			}
		}
		return $debt_id;
	}

	/**
	 * Delete sale debt.
	 *
	 * @param int $debt_id
	 *
	 * @return void
	 */
	public function delete_sale_debt( int $debt_id ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocrm_sale_debt_item', array( 'debt_id' => $debt_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocrm_sale_debt', array( 'debt_id' => $debt_id ) );
	}

	/**
	 * @param array $params
	 *
	 * return array
	 */
	public function select_list_payed( $params ): array {
		global $wpdb;

		$select    = 'SELECT a.*, b.pay_date, c.sale_cd, d.display_name as user_nm ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_sale_debt_item as a ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_sale_order as c ON c.sale_id = a.sale_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_sale_debt as b ON b.debt_id = a.debt_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'users as d ON d.ID = b.user_id ';
		$sql_where = ' WHERE b.customer_id = %d ORDER BY a.debt_id DESC';
		$wheres[]  = $params['customer_id'];

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
	/**
	 * @param int $customer_id
	 *
	 * @return int
	 */
	public function select_total_value( int $customer_id ): int {
		global $wpdb;

		$sql  = 'SELECT (SUM(a.total_value) - SUM(a.discount_val))';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_order as a';
		$sql .= ' WHERE a.customer_id = %d ';
		$sql .= ' GROUP BY a.customer_id';
		return $wpdb->get_var( $wpdb->prepare( $sql, $customer_id ) ) ?? 0;
	}
	/**
	 * @param int $customer_id
	 *
	 * @return int
	 */
	public function select_total_payed( int $customer_id ): int {
		global $wpdb;

		$sql  = 'SELECT SUM(a.total_payed)';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_debt as a';
		$sql .= ' WHERE a.customer_id = %d ';
		$sql .= ' GROUP BY a.customer_id';
		return $wpdb->get_var( $wpdb->prepare( $sql, $customer_id ) ) ?? 0;
	}
	/**
	 * @param int $sale_id
	 * @return int
	 */
	public function select_total_payed_by_sale_order( int $sale_id ): int {
		global $wpdb;

		$sql  = 'SELECT SUM(a.total_payed)';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_sale_debt_item as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_sale_debt as b ON b.debt_id = a.debt_id ';
		$sql .= ' WHERE a.sale_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $sql, array( $sale_id ) ) ) ?? 0;
	}
	/**
	 * check exists sale debt with ID
	 *
	 * @param int $debt_id
	 * @return int
	 */
	public function count_exists( $debt_id ): int {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_sale_debt WHERE debt_id = %d', $debt_id ) );
	}
}
