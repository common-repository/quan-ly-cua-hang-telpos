<?php

namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Quotation_Service extends Base_Service {

	/**
	 * check exists quotation with ID
	 *
	 * @param int $quo_id
	 * @return int
	 */
	public function count_exists( int $quo_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_quotation WHERE quo_id = %d',
				$quo_id
			)
		);
	}
	/**
	 * check exists quotation with Code
	 */
	public function count_exist_by_quo_cd( string $quo_cd, $quo_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_quotation WHERE quo_cd = %s';
		$wheres[] = $quo_cd;
		if ( ! empty( $quo_id ) ) {
			$sql     .= ' AND quo_id != %s ';
			$wheres[] = $quo_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) ) > 0;
	}

	/**
	 * Delete quotation.
	 *
	 * @param int $quo_id
	 *
	 * @return void
	 */
	public function delete_quotation( int $quo_id ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocrm_quotation_item', array( 'quo_id' => $quo_id ) );
		$wpdb->delete( $wpdb->prefix . 'woocrm_quotation', array( 'quo_id' => $quo_id ) );
	}
	/**
	 * Update quotation.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_quotation( array $params, string $user_login ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocrm_quotation',
			array(
				'quo_cd'       => $params['quo_cd'],
				'quo_nm'       => $params['quo_nm'],
				'customer_id'  => $params['customer_id'] ?? null,
				'quo_type'     => $params['quo_type'] ?? 'C',
				'quo_date'     => $params['quo_date'],
				'remark'       => $params['remark'] ?? null,
				'quo_total'    => $params['quo_total'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'quo_id' => $params['quo_id'],
			)
		);
		// delete all old items in this quotation
		$wpdb->delete( $wpdb->prefix . 'woocrm_quotation_item', array( 'quo_id' => $params['quo_id'] ) );
		// save new items for this quotation
		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_quotation_item',
				array(
					'quo_id'     => $params['quo_id'],
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
		}
	}
	/**
	 * Insert quotation.
	 *
	 * @param array  $params,
	 * @param string $user_login
	 *
	 * @return int
	 */
	public function insert_quotation( array $params, string $user_login ): int {
		global $wpdb;
		// save quotation informations
		$wpdb->insert(
			$wpdb->prefix . 'woocrm_quotation',
			array(
				'quo_cd'       => $params['quo_cd'],
				'quo_nm'       => $params['quo_nm'],
				'customer_id'  => $params['customer_id'] ?? null,
				'quo_type'     => $params['quo_type'],
				'quo_date'     => $params['quo_date'],
				'quo_total'    => $params['quo_total'],
				'remark'       => $params['remark'] ?? null,
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			)
		);
		$quo_id = $wpdb->insert_id;

		// save items for quotation
		foreach ( $params['product_id'] as $index => $prod_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_quotation_item',
				array(
					'quo_id'     => $quo_id,
					'product_id' => $prod_id,
					'quantity'   => $params['quantity'][ $index ],
					'unit_price' => $params['unit_price'][ $index ],
				)
			);
		}

		return $quo_id;
	}
	/**
	 * Select list quotation.
	 *
	 * @param int $quo_id
	 *
	 * @return array
	 */
	public function select_list_quo_item( int $quo_id ): array {
		global $wpdb;
		// get list items in quotation
		$sql  = 'SELECT a.*, c.prod_nm, c.prod_cd, u.unit_id, u.unit_nm FROM ' . $wpdb->prefix . 'woocrm_quotation_item as a';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_product as c ON c.product_id = a.product_id ';
		$sql .= ' JOIN ' . $wpdb->prefix . 'woocrm_prod_unit as u ON u.unit_id = c.unit_id ';
		$sql .= ' WHERE a.quo_id = %d ';
		return $wpdb->get_results( $wpdb->prepare( $sql, $quo_id ) );
	}
	/**
	 * Select one quotation.
	 *
	 * @param int $quo_id
	 *
	 * @return object
	 */
	public function select_one_quotation( int $quo_id ): object {
		global $wpdb;
		// get quotation header information
		$sql  = 'SELECT a.*, b.customer_nm FROM ' . $wpdb->prefix . 'woocrm_quotation as a';
		$sql .= ' LEFT JOIN ' . $wpdb->prefix . 'woocrm_customer as b ON b.customer_id = a.customer_id ';
		$sql .= ' WHERE a.quo_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $quo_id ) );
	}
	/**
	 * Select list quotation.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function select_list_quotation( $params ): array {
		global $wpdb;

		$select    = '  SELECT a.*, b.customer_nm ';
		$sql_from  = ' FROM ' . $wpdb->prefix . 'woocrm_quotation as a ';
		$sql_from .= ' LEFT JOIN ' . $wpdb->prefix . 'woocrm_customer as b ON a.customer_id = b.customer_id ';
		$sql_where = ' WHERE 1 = 1 ';
		$wheres    = array();
		if ( ! empty( $params['quo_cd'] ) ) {
			$sql_where .= ' AND a.quo_cd = %s ';
			$wheres[]   = $params['quo_cd'];
		}
		if ( ! empty( $params['quo_nm'] ) ) {
			$sql_where .= ' AND a.quo_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['quo_nm'] ) ) ) . '%';
		}
        if ( ! empty( $params['customer_nm'] ) ) {
			$sql_where .= ' AND b.customer_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['customer_nm'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.quo_id DESC';

		return $this->query_paging( $select, $sql_from, $sql_where, $wheres, WOOCRM_PAGE_SIZE, $params['current'] );
	}
}
