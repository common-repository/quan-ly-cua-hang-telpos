<?php
namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */
abstract class Base_Service {

	/**
	 * query with pagination
	 *
	 * @param string $select
	 * @param string $from
	 * @param string $where
	 *
	 * @return array
	 */
	public function query_paging(
		string $select = '', string $from = '', string $where = '', array $wheres = array(), int $pageSize = 10, int $current = 1
	) {
		global $wpdb;

		$sql_count = 'SELECT COUNT(*) ' . $from . $where;
		$total     = $wpdb->get_var( $wpdb->prepare( $sql_count, $wheres ) );

		$current = $current <= 0 ? 1 : $current;

		$wheres[] = $pageSize;
		$wheres[] = ( $current - 1 ) * $pageSize;
		$data     = $wpdb->get_results( $wpdb->prepare( $select . $from . $where . ' LIMIT %d OFFSET %d ', $wheres ) );
		return array(
			'data'  => $data,
			'total' => $total,
		);
	}
}
