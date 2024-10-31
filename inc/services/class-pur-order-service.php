<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Pur_Order_Service extends Base_Service {

	private Inventory_Service $inventory_service;
	private Pur_Debt_Service $pur_debt_service;
	public function __construct() {
		 $this->inventory_service = new Inventory_Service();
		$this->pur_debt_service   = new Pur_Debt_Service();
	}

	/**
	 * Sum total value of purchase order by current year.
	 *
	 * @return array
	 */
	public function sum_pur_order_by_current_year(): array {
		global $wpdb;

		$sql  = " SELECT SUM(o.total_value) AS total, DATE_FORMAT(o.pur_date, '%c') as mm  ";
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order as o ';
		$sql .= " WHERE DATE_FORMAT(o.pur_date, '%Y') = DATE_FORMAT(CURRENT_DATE, '%Y') ";
		$sql .= " GROUP BY DATE_FORMAT(o.pur_date, '%c') ";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Insert purchase order.
	 *
	 * @param array $params Is parameter request.
	 *
	 * @return int The purchase id after insert.
	 */
	public function insert_pur_order( array $params = array(), $user_login, $user_id ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocrm_pur_order',
			array(
				'pur_cd'       => $params['pur_cd'],
				'partner_id'   => $params['partner_id'],
				'pur_date'     => $params['pur_date'],
				'wh_id'        => $params['wh_id'],
				// 'email' => $params['email'],
				'user_id'      => $params['user_id'],
				'total_value'  => $params['total_value'],
				'remark'       => $params['remark'] ?? null,
				'discount_val' => $params['discount_val'],
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			)
		);
		$pur_id = $wpdb->insert_id;

		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_pur_item',
				array(
					'pur_id'     => $pur_id,
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
			// update warehouse inventory
			$this->inventory_service->pur_update_inventory(
				$params['wh_id'],
				$prod_id,
				$params['quantity'][ $index ],
				$params['unit_price'][ $index ],
				$user_login
			);
		}

		// Insert pur debt
		if ( ! empty( $params['total_payed'] ) && $params['total_payed'] > 0 ) {
			$this->pur_debt_service->insert_debt_by_pur_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'partner_id'   => $params['partner_id'],
					'total_payed'  => $params['total_payed'],
					'user_id'      => $user_id,
					'remark'       => '',
					'pur_id'       => $pur_id,
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}

		return $pur_id;
	}

	/**
	 * Update purchase order.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_pur_order( array $params = array(), $user_login, $user_id ): void {
		global $wpdb;

		// revert all items before update
		$this->inventory_service->pur_revert_invertory( $params['pur_id'] );

		// Insert pur debt
		if ( $params['total_payed'] >= 0 ) {

			$old_total_payed = $this->pur_debt_service->select_total_payed_by_pur_order( $params['pur_id'] );

			if ( $old_total_payed > 0 ) {
				$this->pur_debt_service->insert_debt_by_pur_order(
					array(
						'pay_date'     => date( 'Y-m-d' ),
						'partner_id'   => $params['partner_id'],
						'total_payed'  => ( -1 * $old_total_payed ),
						'user_id'      => $user_id,
						'remark'       => '',
						'pur_id'       => $params['pur_id'],
						'created_user' => $user_login,
						'updated_user' => $user_login,
					),
					$user_login
				);
			}
			$this->pur_debt_service->insert_debt_by_pur_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'partner_id'   => $params['partner_id'],
					'total_payed'  => $params['total_payed'],
					'user_id'      => $user_id,
					'remark'       => '',
					'pur_id'       => $params['pur_id'],
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}

		$wpdb->update(
			$wpdb->prefix . 'woocrm_pur_order',
			array(
				'pur_cd'       => $params['pur_cd'],
				// 'partner_id'   => $params['partner_id'],
				'wh_id'        => $params['wh_id'],
				'pur_date'     => $params['pur_date'],
				'remark'       => $params['remark'] ?? null,
				'discount_val' => $params['discount_val'],
				'total_value'  => $params['total_value'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'pur_id' => $params['pur_id'],
			)
		);
		// delete all old items in this purchase item
		$wpdb->delete( $wpdb->prefix . 'woocrm_pur_item', array( 'pur_id' => $params['pur_id'] ) );
		// save new items for this quotation
		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_pur_item',
				array(
					'pur_id'     => $params['pur_id'],
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
			// update warehouse inventory
			$this->inventory_service->pur_update_inventory(
				$params['wh_id'],
				$prod_id,
				$params['quantity'][ $index ],
				$params['unit_price'][ $index ],
				$user_login
			);
		}
	}

	/**
	 * Delete purchase order.
	 *
	 * @param int $pur_id
	 *
	 * @return void
	 */
	public function delete_pur_order( int $pur_id, $user_login, $user_id ): void {
		global $wpdb;

		$sql        = 'SELECT a.partner_id FROM ' . $wpdb->prefix . 'woocrm_pur_order as a';
		$sql       .= ' WHERE a.pur_id = %d ';
		$partner_id = $wpdb->get_var( $wpdb->prepare( $sql, $pur_id ) );

		$old_total_payed = $this->pur_debt_service->select_total_payed_by_pur_order( $partner_id, $pur_id );

		if ( $old_total_payed > 0 ) {
			$this->pur_debt_service->insert_debt_by_pur_order(
				array(
					'pay_date'     => date( 'Y-m-d' ),
					'partner_id'   => $partner_id,
					'total_payed'  => ( -1 * $old_total_payed ),
					'user_id'      => $user_id,
					'remark'       => '',
					'pur_id'       => $pur_id,
					'created_user' => $user_login,
					'updated_user' => $user_login,
				),
				$user_login
			);
		}
		$this->inventory_service->pur_revert_invertory( $pur_id );
		$wpdb->delete( $wpdb->prefix . 'woocrm_pur_item', array( 'pur_id' => $pur_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocrm_pur_order', array( 'pur_id' => $pur_id ) );
	}

	/**
	 * @param array $params
	 *
	 * return array
	 */
	public function select_list_pur_order( $params ): array {
		global $wpdb;

		$select    = '  SELECT a.*, b.partner_nm, b.partner_cd, c.wh_nm, d.display_name as user_nm ';
		$select   .= ' , (SELECT IFNULL(SUM(dd.total_payed),0) FROM ' . $wpdb->prefix . 'woocrm_pur_debt_item as dd
        JOIN ' . $wpdb->prefix . 'woocrm_pur_debt as di ON di.debt_id = dd.debt_id
        WHERE dd.pur_id = a.pur_id AND di.partner_id = a.partner_id) AS total_payed ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_pur_order as a ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_partner as b ON b.partner_id = a.partner_id ';
		$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as c ON c.wh_id = a.wh_id ';
		$sql_from .= ' LEFT JOIN ' . $wpdb->prefix . 'users as d ON d.ID = a.user_id ';
		$sql_where = ' WHERE 1 = 1';
		$wheres    = array();
		if ( ! empty( $params['pur_cd'] ) ) {
			$sql_where .= ' AND a.pur_cd = %s ';
			$wheres[]   = $params['pur_cd'];
		}
		if ( ! empty( $params['pur_date'] ) ) {
			$sql_where .= ' AND a.pur_date = %s ';
			$wheres[]   = $params['pur_date'];
		}
		if ( ! empty( $params['partner_nm'] ) ) {
			$sql_where .= ' AND b.partner_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['partner_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['partner_id'] ) ) {
			$sql_where .= ' AND a.partner_id = %d ';
			$wheres[]   = $params['partner_id'];
		}
        if ( ! empty( $params['wh_nm'] ) ) {
			$sql_where .= ' AND c.wh_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['wh_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.pur_id DESC';


		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}

	/**
	 * @param int $pur_id
	 *
	 * @return object
	 */
	public function select_one_pur_order( int $pur_id ): ?object {
		global $wpdb;

		$sql  = 'SELECT a.*, b.partner_nm, b.partner_cd, c.wh_id, c.wh_nm, d.display_name as user_nm ';
        $sql .= ' , (SELECT IFNULL(SUM(dd.total_payed),0) FROM ' . $wpdb->prefix . 'woocrm_pur_debt_item as dd WHERE dd.pur_id = a.pur_id) AS total_payed ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_pur_order as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_partner as b ON b.partner_id = a.partner_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as c ON c.wh_id = a.wh_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'users as d ON d.ID = a.user_id ';
		$sql .= ' WHERE a.pur_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $pur_id ) );
	}

	/**
	 * Get list items in purchase order.
	 *
	 * @param int $pur_id
	 *
	 * @return array
	 */
	public function select_list_pur_order_items( int $pur_id ): array {
		global $wpdb;

		$sql  = 'SELECT a.*, b.prod_nm, b.prod_cd, c.unit_nm, d.prod_type_nm FROM ' . $wpdb->prefix . 'woocrm_pur_item as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_product as b ON b.product_id = a.product_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as c ON c.unit_id = b.unit_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as d ON d.prod_type_id = b.prod_type_id ';
		$sql .= ' WHERE a.pur_id = %d ';
		return $wpdb->get_results( $wpdb->prepare( $sql, $pur_id ) );
	}

	/**
	 * check exists purchase order with ID
	 *
	 * @param int $pur_id
	 * @return int
	 */
	public function count_exists( $pur_id ): int {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_pur_order WHERE pur_id = %d', $pur_id ) );
	}
	/**
	 * check exists purchase with Code or ID
	 *
	 * @param string $quo_cd
	 * @param int    $quo_id
	 * @return int
	 */
	public function count_exist_by_pur_cd( $pur_cd, $pur_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_pur_order WHERE pur_cd = %s';
		$wheres[] = $pur_cd;
		if ( ! empty( $pur_id ) ) {
			$sql     .= ' AND pur_id != %s ';
			$wheres[] = $pur_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}

	/**
	 * Count total purchase order by partner.
	 *
	 * @param int $partner_id The partner ID.
	 * @return int The count total purchase order.
	 */
	public function count_exists_by_partner_id( $partner_id ): int {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_pur_order WHERE partner_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $sql, $partner_id ) );
	}
}
