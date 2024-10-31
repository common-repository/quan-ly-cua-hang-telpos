<?php
namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Product_Service extends Base_Service {

	/**
	 * check product is exists
	 *
	 * @param int $product_id
	 * @return int count of exists
	 */
	public function count_exist( int $product_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_product WHERE product_id = %d', $product_id )
		);
	}
	/**
	 * check product code is exists
	 *
	 * @param string $prod_cd
	 * @param int    $product_id
	 * @return int count of exists
	 */
	public function count_exist_by_prod_cd( string $prod_cd, $product_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_product WHERE prod_cd = %s';
		$wheres[] = $prod_cd;
		if ( ! empty( $product_id ) ) {
			$sql     .= ' AND product_id != %s ';
			$wheres[] = $product_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}

	/**
	 * check product name is exists
	 *
	 * @param string $prod_cd
	 * @param int    $product_id
	 * @return int count of exists
	 */
	public function count_exist_by_prod_nm( string $prod_nm, $product_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_product WHERE lower(prod_nm) = %s';
		$wheres[] = strtolower( $prod_nm );
		if ( ! empty( $product_id ) ) {
			$sql     .= ' AND product_id != %s ';
			$wheres[] = $product_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) );
	}

	/**
	 * Delete product
	 *
	 * @param int $product_id
	 *
	 * @return void
	 */
	public function delete_product( int $product_id ): void {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'woocrm_product',
			array(
				'product_id' => $product_id,
			)
		);

		$wpdb->delete(
			$wpdb->prefix . 'woocrm_prod_price_his',
			array(
				'product_id' => $product_id,
			)
		);
	}
	/**
	 * Update product.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_product( array $params, string $user_login ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocrm_product',
			array(
				'prod_cd'      => $params['prod_cd'],
				'prod_nm'      => $params['prod_nm'],
				'prod_type_id' => $params['prod_type_id'],
				'unit_id'      => $params['unit_id'],
				'sale_price'   => $params['sale_price'],
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'product_id' => $params['product_id'],
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$this->insert_prod_price_his( $params['product_id'], $params['sale_price'], $user_login );
	}
	/**
	 * Insert product.
	 *
	 * @param array @params
	 * @param string        $user_login
	 *
	 * @return int
	 */
	public function insert_product( array $params, string $user_login ): int {
		global $wpdb;

		$sale_price = $params['sale_price'] ?? 0;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_product',
			array(
				'prod_cd'      => $params['prod_cd'],
				'prod_nm'      => $params['prod_nm'],
				'prod_type_id' => $params['prod_type_id'],
				'unit_id'      => $params['unit_id'],
				'sale_price'   => $sale_price,
				'remark'       => $params['remark'],
				'active'       => $params['active'],
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		$prod_id = $wpdb->insert_id;
		$this->insert_prod_price_his( $prod_id, $sale_price, $user_login );

		return $prod_id;
	}

	/**
	 * Store update price history
	 *
	 * @param int    $prod_id Product ID.
	 * @param int    $sale_price Sale Price.
	 * @param string $user_login Current User Login.
	 *
	 * @return void
	 */
	private function insert_prod_price_his( int $prod_id, int $sale_price, string $user_login ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_prod_price_his',
			array(
				'product_id'   => $prod_id,
				'sale_price'   => $sale_price ?? 0,
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Get one product.
	 *
	 * @param int $product_id
	 *
	 * @return object
	 */
	public function select_one_product( int $product_id ): object {
		global $wpdb;

		$sql  = '  SELECT a.*, b.unit_nm, c.prod_type_nm ';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_product as a ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as b ON b.unit_id = a.unit_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as c ON c.prod_type_id = a.prod_type_id ';
		$sql .= ' WHERE a.product_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $product_id ) );
	}
	/**
	 * Get list product.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_product( $params ): array {
		global $wpdb;

			$select    = '  SELECT a.*, b.unit_nm, c.prod_type_nm ';
			$select   .= ' ,( IFNULL( (SELECT x.unit_price
                            FROM
                                wp_woocrm_quotation_item AS x, wp_woocrm_quotation AS y
                            WHERE
                                x.quo_id = y.quo_id
                                    AND (y.customer_id = %d or y.customer_id = 0)
                                    AND x.product_id = a.product_id
                            ORDER BY y.customer_id DESC, y.quo_date DESC LIMIT 1), a.sale_price)
                        ) as sale_price ';
			$select   .= ' ,(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_inventory as aa WHERE aa.product_id = a.product_id) as cnt1 ';
			$select   .= ' ,(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_quotation_item as aa WHERE aa.product_id = a.product_id) as cnt2 ';
			$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_product as a ';
			$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as b ON b.unit_id = a.unit_id ';
			$sql_from .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_type as c ON c.prod_type_id = a.prod_type_id ';

			$sql_where = ' WHERE 1 = 1 ';
			$wheres    = array();
			$wheres[]  = empty( $params['customer_id'] ) ? null : $params['customer_id'];

		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['prod_cd'] ) ) {
			$sql_where .= ' AND a.prod_cd = %s ';
			$wheres[]   = $params['prod_cd'];
		}
		if ( ! empty( $params['prod_nm'] ) ) {
			$sql_where .= ' AND a.prod_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['prod_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['unit_nm'] ) ) {
			$sql_where .= ' AND b.unit_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['unit_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['prod_type_nm'] ) ) {
			$sql_where .= ' AND c.prod_type_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['prod_type_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.product_id DESC';

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}

	/**
	 * Count exits product unit in product.
	 *
	 * @param int $unit_id The product unit.
	 *
	 * @return int The count total product.
	 */
	public function count_exits_by_unit_id( $unit_id ): int {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "woocrm_product WHERE unit_id = %d ";
		return $wpdb->get_var( $wpdb->prepare( $sql, $unit_id ) );
	}

	/**
	 * Count exits product type in product.
	 *
	 * @param int $prod_type_id The product type.
	 *
	 * @return int The count total product.
	 */
	public function count_exits_by_prod_type_id( $prod_type_id ): int {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "woocrm_product WHERE prod_type_id = %d ";
		return $wpdb->get_var( $wpdb->prepare( $sql, $prod_type_id ) );
	}
}
