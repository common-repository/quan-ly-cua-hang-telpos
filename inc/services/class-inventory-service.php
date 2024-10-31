<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Inventory_Service extends Base_Service {


	/**
	 * Count warning quantity.
	 *
	 * @return int
	 */
	public function count_outoff_quantity(): ?int {
		global $wpdb;
		$sql  = 'SELECT COUNT(*) ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_inventory';
		$sql .= ' WHERE quantity = 0 ';
		return $wpdb->get_var( $wpdb->prepare( $sql, array() ) );
	}

	/**
	 * Count warning quantity.
	 *
	 * @return int
	 */
	public function count_warn_quantity(): ?int {
		global $wpdb;
		$warn_opt = get_option( 'telsky_opt_warn_quantity' );
		$warn_opt = empty( $warn_opt ) ? 5 : $warn_opt;
		$sql      = 'SELECT COUNT(*) ';
		$sql     .= ' FROM ' . $wpdb->prefix . 'woocrm_inventory';
		$sql     .= ' WHERE quantity BETWEEN 1 AND %d ';
		return $wpdb->get_var( $wpdb->prepare( $sql, array( $warn_opt ) ) );
	}

	/**
	 * Update warehouse inventory.
	 *
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_inventory( array $params ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . $wpdb->prefix . 'woocrm_inventory
                    SET
                        quantity = %d,
                        updated_at = %s,
                        updated_user = %s
                    WHERE wh_id = %d AND product_id = %d',
				array(
					$params['quantity'],
					date( 'Y-m-d H:i:s' ),
					$params['updated_user'],
					$params['wh_id'],
					$params['product_id'],
				)
			)
		);
		// Save update warehouse inventory history.
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_inventory_his',
			array(
				'wh_id'        => $params['wh_id'],
				'product_id'   => $params['product_id'],
				'quantity'     => $params['quantity'],
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $params['updated_user'],
			)
		);
	}

	/**
	 * Get one inventory
	 *
	 * @param array $params
	 *
	 * @return object
	 */
	public function select_one_inventory( $wh_id, $prod_id ): ?object {
		global $wpdb;

		$sql  = 'SELECT a.*, b.wh_nm, c.prod_cd, c.prod_nm, d.prod_type_nm, e.unit_nm ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_inventory as a ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as b ON a.wh_id = b.wh_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_product as c ON c.product_id = a.product_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as d ON d.prod_type_id = c.prod_type_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as e ON e.unit_id = c.unit_id ';
		$sql .= ' WHERE c.product_id = %d AND a.wh_id = %d ';

		return $wpdb->get_row( $wpdb->prepare( $sql, array( $prod_id, $wh_id ) ) );
	}

	/**
	 * Get list inventory
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_inventory( array $params ): array {
		global $wpdb;

		$sql_select  = 'SELECT a.*, b.wh_nm, b.wh_cd, c.prod_cd, c.prod_nm, d.prod_type_nm, e.unit_nm ';
        $sql_select .= ' ,(CASE WHEN f.unit_price IS NOT NULL THEN f.unit_price WHEN g.unit_price IS NOT NULL THEN g.unit_price ELSE c.sale_price END) as sale_price ';

		$sql_from   = ' FROM ' . $wpdb->prefix . 'woocrm_inventory as a ';
		$sql_from  .= ' JOIN ' . $wpdb->prefix . 'woocrm_warehouse as b ON a.wh_id = b.wh_id ';
		$sql_from  .= ' JOIN ' . $wpdb->prefix . 'woocrm_product as c ON c.product_id = a.product_id ';
		$sql_from  .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as d ON d.prod_type_id = c.prod_type_id ';
		$sql_from  .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as e ON e.unit_id = c.unit_id ';
        $sql_from  .= ' LEFT JOIN ( ';
        $sql_from  .= '     SELECT x.unit_price, x.product_id FROM wp_woocrm_quotation_item AS x, wp_woocrm_quotation AS y';
        $sql_from  .= '     WHERE x.quo_id = y.quo_id AND y.customer_id = %d ORDER BY y.quo_date DESC LIMIT 1';
        $sql_from  .= ' ) AS f ON f.product_id = a.product_id ';
        $sql_from  .= ' LEFT JOIN ( ';
        $sql_from  .= '     SELECT x.unit_price, x.product_id FROM wp_woocrm_quotation_item AS x, wp_woocrm_quotation AS y';
        $sql_from  .= '     WHERE x.quo_id = y.quo_id AND y.customer_id = %d ORDER BY y.quo_date DESC LIMIT 1';
        $sql_from  .= ' ) AS g ON g.product_id = a.product_id ';

		$sql_where  = ' WHERE 1 = 1 ';
		$wheres     = array();
        // For get sale price by customer
        $wheres[]  = empty( $params['customer_id'] ) ? null : $params['customer_id'];
        $wheres[]  = null;
        // End get sale price by customer

		if ( ! empty( $params['wh_id'] ) ) {
			$sql_where .= ' AND b.wh_id = %d ';
			$wheres[]   = $params['wh_id'];
		}
        if ( ! empty( $params['wh_cd'] ) ) {
			$sql_where .= ' AND b.wh_cd = %s ';
			$wheres[]   = $params['wh_cd'];
		}
		if ( ! empty( $params['wh_nm'] ) ) {
			$sql_where .= ' AND b.wh_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( $params['wh_nm'] ) ) . '%';
		}
		if ( ! empty( $params['prod_nm'] ) ) {
			$sql_where .= ' AND c.prod_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( $params['prod_nm'] ) ) . '%';
		}
		if ( ! empty( $params['prod_type_nm'] ) ) {
			$sql_where .= ' AND d.prod_type_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( $params['prod_type_nm'] ) ) . '%';
		}
		if ( ! empty( $params['unit_nm'] ) ) {
			$sql_where .= ' AND e.unit_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( $params['unit_nm'] ) ) . '%';
		}
        if ( ! empty( $params['is_out_stock'] ) ) {
            $sql_where .= ' AND a.quantity = 0 ';
        }
        if ( ! empty( $params['is_warn_stock'] ) ) {
            $warn_opt = get_option( 'telsky_opt_warn_quantity' );
		    $warn_opt = empty( $warn_opt ) ? 5 : $warn_opt;
            $sql_where .= ' AND a.quantity BETWEEN 1 AND %d ';
            $wheres[] = $warn_opt;
        }

		return $this->query_paging( $sql_select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
	/**
	 * revert all order items before update
	 *
	 * @param int $sale_id
	 * @return void
	 */
	public function sale_revert_invertory( int $sale_id ) {
		 global $wpdb;
		// get order information
		$order = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_sale_order WHERE sale_id = %d', $sale_id )
		);
		// get order items
		$items = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_sale_item WHERE sale_id = %d', $sale_id )
		);
		foreach ( $items as $item ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wpdb->prefix . 'woocrm_inventory
                        SET
                            quantity = quantity + %d
                        WHERE wh_id = %d AND product_id = %d',
					array( $item->quantity, $order->wh_id, $item->product_id )
				)
			);
		}
	}

	/**
	 * update warehouse inventory
	 *
	 * @param int $wh_id
	 * @param int $prod_id
	 * @param int $quantity
	 * @param int $user_login
	 */
	public function sale_update_inventory( int $wh_id, int $prod_id, int $quantity, $user_login ) {
		 global $wpdb;
		// count exists product in warehouse inventory
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . $wpdb->prefix . 'woocrm_inventory
                    SET
                        quantity = quantity - %d,
                        updated_at = %s,
                        updated_user = %s
                    WHERE wh_id = %d AND product_id = %d',
				array( $quantity, date( 'Y-m-d H:i:s' ), $user_login, $wh_id, $prod_id )
			)
		);
	}

	/**
	 * update warehouse inventory
	 *
	 * @param int $wh_id
	 * @param int $prod_id
	 * @param int $quantity
	 * @param int $user_login
	 */
	public function pur_update_inventory( int $wh_id, int $prod_id, int $quantity, int $unit_price, $user_login ) {
		 global $wpdb;
		// count exists product in warehouse inventory
		$cnt_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_inventory WHERE wh_id = %d AND product_id = %d',
				array( $wh_id, $prod_id )
			)
		);
		if ( ! empty( $cnt_exists ) && $cnt_exists > 0 ) {
			// if exists then update inventory
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wpdb->prefix . 'woocrm_inventory
                        SET
                            quantity = quantity + %d,
                            pur_price = %d,
                            updated_at = %s,
                            updated_user = %s
                        WHERE wh_id = %d AND product_id = %d',
					array( $quantity, $unit_price, date( 'Y-m-d H:i:s' ), $user_login, $wh_id, $prod_id )
				)
			);
		} else {
			// if not exists then insert new inventory
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_inventory',
				array(
					'wh_id'        => $wh_id,
					'product_id'   => $prod_id,
					'quantity'     => $quantity,
					'pur_price'    => $unit_price,
					'created_at'   => date( 'Y-m-d H:i:s' ),
					'updated_at'   => date( 'Y-m-d H:i:s' ),
					'created_user' => $user_login,
					'updated_user' => $user_login,
				)
			);
		}
	}

	/**
	 * revert all order items before update
	 *
	 * @param int $pur_id
	 * @return void
	 */
	public function pur_revert_invertory( int $pur_id ) {
		global $wpdb;
		// get order information
		$order = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_pur_order WHERE pur_id = %d', $pur_id )
		);
		// get order items
		$items = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocrm_pur_item WHERE pur_id = %d', $pur_id )
		);
		foreach ( $items as $item ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wpdb->prefix . 'woocrm_inventory
                        SET
                            quantity = quantity - %d
                        WHERE wh_id = %d AND product_id = %d',
					array( $item->quantity, $order->wh_id, $item->product_id )
				)
			);
		}
	}

	/**
	 * Count total inventory.
	 *
	 * @param int $wh_id The warehouse ID.
	 * @return int The count total inventory.
	 */
	public function count_exists_by_wh_id( $wh_id ): int {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_inventory WHERE wh_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $sql, $wh_id ) );
	}
}
