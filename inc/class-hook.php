<?php
namespace Telsky\Woocrm;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Hook {

	public $isConfig = true;
	public function __construct() {
		 // add action product
		add_action( 'save_post', array( $this, 'add_prod_after_add_post' ), 10, 3 );

		// add action product type
		add_action( 'create_term', array( $this, 'add_prod_type_after_add_term' ), 10, 3 );

		add_action( 'edited_term', array( $this, 'update_prod_type_after_edit_term' ), 10, 3 );
	}

	public function add_prod_after_add_post( $post_ID, $post ) {

		global $wpdb;
		if ( wp_is_post_revision( $post_ID ) ) {
			return;
		}
		if ( in_array( $post->post_status, array( 'auto-draft' ), true ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'product', 'awpcp_listing' ), true ) ) {
			return;
		}

		$sql      = 'SELECT a.*';
		$sql     .= ' FROM ' . $wpdb->prefix . 'terms as a';
		$sql     .= ' JOIN ' . $wpdb->prefix . 'term_taxonomy as b ON b.term_id = a.term_id ';
		$sql     .= ' JOIN ' . $wpdb->prefix . 'term_relationships as c ON c.term_taxonomy_id = a.term_id ';
		$sql     .= " WHERE b.taxonomy = 'product_cat' AND c.object_id = %d";
		$catagory = $wpdb->get_row( $wpdb->prepare( $sql, $post_ID ) );

		$sql        = 'SELECT a.prod_type_id';
		$sql       .= ' FROM ' . $wpdb->prefix . 'woocrm_prod_type as a';
		$sql       .= ' WHERE a.prod_type_nm = %s';
		$catagoryID = $wpdb->get_var( $wpdb->prepare( $sql, $catagory->name ) );

		$sql    = 'SELECT display_name';
		$sql   .= ' FROM ' . $wpdb->prefix . 'users';
		$sql   .= ' WHERE ID = ' . $post->post_author;
		$userNm = $wpdb->get_var( $wpdb->prepare( $sql ) );

		if ( empty( $catagoryID ) ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_prod_type',
				array(
					'prod_type_cd' => $catagory->name,
					'prod_type_nm' => $catagory->name,
					'remark'       => $catagory->name,
					'active'       => 'Y',
					'created_at'   => date( 'Y-m-d H:i:s' ),
					'updated_at'   => date( 'Y-m-d H:i:s' ),
					'created_user' => $userNm,
					'updated_user' => $userNm,
					'term_id'      => $catagory->term_id,
				),
				array( '%s' )
			);

			$catagoryID = $wpdb->insert_id;
		}

		$sql  = 'SELECT a.product_id';
		$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_product as a';
		$sql .= ' WHERE a.post_id = %s';
		$id   = $wpdb->get_var( $wpdb->prepare( $sql, $post_ID ) );

		if ( ! empty( $id ) ) {
			$wpdb->update(
				$wpdb->prefix . 'woocrm_product',
				array(
					'prod_cd'      => 'SP-WCRM-' . $post_ID,
					'prod_nm'      => $post->post_title,
					'prod_type_id' => $catagoryID ?? 1,
					'unit_id'      => 1,
					'remark'       => $post->post_title,
					'active'       => 'Y',
					'updated_at'   => date( 'Y-m-d H:i:s' ),
					'updated_user' => $userNm,
				),
				array(
					'product_id' => $id,
				),
				array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_product',
				array(
					'prod_cd'      => 'SP-WCRM-' . $post_ID,
					'prod_nm'      => $post->post_title,
					'prod_type_id' => $catagoryID ?? 1,
					'unit_id'      => 1,
					'remark'       => $post->post_title,
					'active'       => 'Y',
					'created_at'   => date( 'Y-m-d H:i:s' ),
					'updated_at'   => date( 'Y-m-d H:i:s' ),
					'created_user' => $userNm,
					'updated_user' => $userNm,
					'post_id'      => $post_ID,
				),
				array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
			);
		}

	}

	public function add_prod_type_after_add_term( $term, $tt_id, $taxonomy ) {

		global $wpdb;

		if ( ! in_array( $taxonomy, array( 'product_cat' ), true ) ) {
			return;
		}

		$catagory = get_term( $term, 'product_cat' );

		if ( ! empty( $catagory ) ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocrm_prod_type',
				array(
					'prod_type_cd' => $catagory->name,
					'prod_type_nm' => $catagory->name,
					'remark'       => $catagory->name,
					'active'       => 'Y',
					'created_at'   => date( 'Y-m-d H:i:s' ),
					'updated_at'   => date( 'Y-m-d H:i:s' ),
					'created_user' => 'System',
					'updated_user' => 'System',
					'term_id'      => $term,
				),
				array( '%s' )
			);
		}
	}

	public function update_prod_type_after_edit_term( $term, $tt_id, $taxonomy ) {

		global $wpdb;

		if ( ! in_array( $taxonomy, array( 'product_cat' ), true ) ) {
			return;
		}

		$catagory = get_term( $term, 'product_cat' );

		if ( ! empty( $catagory ) ) {
			$sql  = 'SELECT a.prod_type_id';
			$sql .= ' FROM ' . $wpdb->prefix . 'woocrm_prod_type a';
			$sql .= ' WHERE a.term_id = %d';
			$id   = $wpdb->get_var( $wpdb->prepare( $sql, $term ) );

			if ( ! empty( $id ) ) {
				$wpdb->update(
					$wpdb->prefix . 'woocrm_prod_type',
					array(
						'prod_type_cd' => $catagory->name,
						'prod_type_nm' => $catagory->name,
						'remark'       => $catagory->name,
						'active'       => 'Y',
						'updated_at'   => date( 'Y-m-d H:i:s' ),
						'updated_user' => 'System',
					),
					array(
						'prod_type_id' => $id,
					),
					array( '%s' )
				);
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'woocrm_prod_type',
					array(
						'prod_type_cd' => $catagory->name,
						'prod_type_nm' => $catagory->name,
						'remark'       => $catagory->name,
						'active'       => 'Y',
						'created_at'   => date( 'Y-m-d H:i:s' ),
						'updated_at'   => date( 'Y-m-d H:i:s' ),
						'created_user' => 'System',
						'updated_user' => 'System',
						'term_id'      => $term,
					),
					array( '%s' )
				);
			}
		}
	}
}
