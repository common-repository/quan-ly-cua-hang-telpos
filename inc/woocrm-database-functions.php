<?php
/**
 *
 * @version 1.0
 * @package WooCRM
 */

function woocrm_create_table() {
	global $wpdb;
	$woocrm_db_version = '1.0';
	$sql               = '';

	$woocrm_prod_unit = $wpdb->prefix . 'woocrm_prod_unit';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_prod_unit'" ) != $woocrm_prod_unit ) {
		$sql .= ' CREATE TABLE ' . $woocrm_prod_unit . "(
			unit_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			unit_nm VARCHAR(255) NOT NULL,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            UNIQUE KEY(unit_nm)
		); ";

		$sql .= ' CREATE INDEX idx_woocrm_prod_unit ON ' . $woocrm_prod_unit . '(unit_nm); ';
		$sql .= ' INSERT INTO ' . $woocrm_prod_unit . "(unit_id, unit_nm) VALUES(1, 'Cái'); ";
	}

	$woocrm_prod_type = $wpdb->prefix . 'woocrm_prod_type';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_prod_type'" ) != $woocrm_prod_type ) {
		$sql .= 'CREATE TABLE ' . $woocrm_prod_type . "(
			prod_type_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			prod_type_nm VARCHAR(255) NOT NULL,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            term_id BIGINT UNIQUE NULL,
            UNIQUE KEY(prod_type_nm)
		); ";

		$sql .= ' CREATE INDEX idx_woocrm_prod_type ON ' . $woocrm_prod_type . '(prod_type_nm); ';
	}

	$woocrm_product = $wpdb->prefix . 'woocrm_product';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_product' " ) != $woocrm_product ) {
		$sql .= 'CREATE TABLE ' . $woocrm_product . "(
			product_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			prod_cd VARCHAR(50) NOT NULL,
			prod_nm VARCHAR(255) NOT NULL,
            unit_id BIGINT NOT NULL,
            prod_type_id BIGINT NOT NULL,
            sale_price BIGINT NOT NULL DEFAULT 0,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            post_id BIGINT UNIQUE NULL,
            UNIQUE KEY(prod_cd, prod_nm)
		); ";

		$sql .= ' CREATE INDEX idx_woocrm_product ON ' . $woocrm_product . '(prod_cd, prod_nm); ';
	}

	$woocrm_product_price_his = $wpdb->prefix . 'woocrm_prod_price_his';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_product_price_his' " ) != $woocrm_product_price_his ) {
		$sql .= 'CREATE TABLE ' . $woocrm_product_price_his . '(
            his_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			product_id BIGINT NOT NULL,
            sale_price BIGINT NOT NULL DEFAULT 0,
			created_at TIMESTAMP NOT NULL,
			created_user VARCHAR(60) NOT NULL
		); ';
	}

	$woocrm_warehouse = $wpdb->prefix . 'woocrm_warehouse';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_warehouse'" ) != $woocrm_warehouse ) {
		$sql .= 'CREATE TABLE ' . $woocrm_warehouse . "(
			wh_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			wh_cd VARCHAR(50) NOT NULL,
			wh_nm VARCHAR(255) NOT NULL,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            UNIQUE KEY(wh_cd)
		); ";
		$sql .= ' CREATE INDEX idx_woocrm_wh ON ' . $woocrm_warehouse . '(wh_cd, wh_nm); ';
	}

	$woocrm_partner = $wpdb->prefix . 'woocrm_partner';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_partner'" ) != $woocrm_partner ) {
		$sql .= 'CREATE TABLE ' . $woocrm_partner . "(
			partner_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			partner_cd VARCHAR(50) NOT NULL,
			partner_nm VARCHAR(255) NOT NULL,
            address VARCHAR(255) NULL,
            phone VARCHAR(20) NULL,
            fax VARCHAR(20) NULL,
            email VARCHAR(255) NULL,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            UNIQUE KEY(partner_cd)
		); ";

		$sql .= ' CREATE INDEX idx_woocrm_partner ON ' . $woocrm_partner . '(partner_cd, partner_nm); ';
	}

	$woocrm_customer = $wpdb->prefix . 'woocrm_customer';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_customer'" ) != $woocrm_customer ) {
		$sql .= 'CREATE TABLE ' . $woocrm_customer . "(
			customer_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			customer_cd VARCHAR(50) NOT NULL,
			customer_nm VARCHAR(255) NOT NULL,
            address VARCHAR(255) NULL,
            phone VARCHAR(20) NULL,
            fax VARCHAR(20) NULL,
            email VARCHAR(255) NULL,
			active char(1) NOT NULL DEFAULT 'Y',
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            UNIQUE KEY(customer_cd)
		); ";

		$sql .= ' CREATE INDEX idx_woocrm_customer ON ' . $woocrm_customer . '(customer_cd, customer_nm); ';
	}

	$woocrm_pur_order = $wpdb->prefix . 'woocrm_pur_order';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_pur_order'" ) != $woocrm_pur_order ) {
		$sql .= 'CREATE TABLE ' . $woocrm_pur_order . '(
			pur_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			pur_cd VARCHAR(50) NOT NULL,
			partner_id BIGINT NOT NULL,
			pur_date DATE NOT NULL,
			wh_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
			total_value BIGINT DEFAULT 0,
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            discount_val BIGINT NOT NULL DEFAULT 0,
            UNIQUE KEY(pur_cd)
		); ';
	}
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_pur_order'" ) != $woocrm_pur_order ) {
        $sql_addColumn = 'ALTER TABLE ' . $woocrm_pur_order . ' ADD discount_val BIGINT NOT NULL DEFAULT 0';
        $wpdb->query( $sql_addColumn );
    }

	$woocrm_pur_item = $wpdb->prefix . 'woocrm_pur_item';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_pur_item'" ) != $woocrm_pur_item ) {
		$sql .= ' CREATE TABLE ' . $woocrm_pur_item . '(
			pur_id BIGINT NOT NULL,
			product_id BIGINT NOT NULL,
			quantity FLOAT NOT NULL DEFAULT 0,
			unit_price BIGINT NOT NULL DEFAULT 0,
			PRIMARY KEY (pur_id, product_id)
		); ';
	}

	$woocrm_quotation = $wpdb->prefix . 'woocrm_quotation';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_quotation'" ) != $woocrm_quotation ) {
		$sql .= 'CREATE TABLE ' . $woocrm_quotation . "(
			quo_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			quo_cd VARCHAR(50) NOT NULL,
			quo_nm VARCHAR(255) NOT NULL,
			customer_id BIGINT NULL,
			quo_type CHAR(1) NOT NULL DEFAULT 'C',
			quo_date DATE NOT NULL,
			quo_total DOUBLE DEFAULT 0,
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            UNIQUE KEY(quo_cd)
		); ";
	}

	$woocrm_quotation_item = $wpdb->prefix . 'woocrm_quotation_item';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_quotation_item'" ) != $woocrm_quotation_item ) {
		$sql .= ' CREATE TABLE ' . $woocrm_quotation_item . '(
			quo_id BIGINT NOT NULL,
			product_id BIGINT NOT NULL,
			quantity FLOAT NOT NULL DEFAULT 0,
			unit_price BIGINT NOT NULL DEFAULT 0,
			PRIMARY KEY (quo_id, product_id)
		); ';

	}

	$woocrm_sale_order = $wpdb->prefix . 'woocrm_sale_order';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_sale_order'" ) != $woocrm_sale_order ) {
		$sql .= 'CREATE TABLE ' . $woocrm_sale_order . '(
			sale_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			sale_cd VARCHAR(50) NOT NULL,
			customer_id BIGINT NOT NULL,
			sale_date DATE NOT NULL,
			wh_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
			total_value DOUBLE DEFAULT 0,
            remark VARCHAR(255) NULL,
			created_at TIMESTAMP NULL,
			updated_at TIMESTAMP NULL,
			created_user VARCHAR(60) NULL,
			updated_user VARCHAR(60) NULL,
            discount_val BIGINT NOT NULL DEFAULT 0,
            UNIQUE KEY(sale_cd)
		); ';
	}
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_sale_order'" ) != $woocrm_sale_order ) {
        $sql_addColumn = 'ALTER TABLE ' . $woocrm_sale_order . ' ADD discount_val BIGINT NOT NULL DEFAULT 0';
        $wpdb->query( $sql_addColumn );
    }

	$woocrm_sale_item = $wpdb->prefix . 'woocrm_sale_item';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_sale_item'" ) != $woocrm_sale_item ) {
		$sql .= ' CREATE TABLE ' . $woocrm_sale_item . '(
			sale_id BIGINT NOT NULL,
			product_id BIGINT NOT NULL,
			quantity FLOAT NOT NULL DEFAULT 0,
			unit_price BIGINT NOT NULL DEFAULT 0,
			PRIMARY KEY (sale_id, product_id)
		); ';
	}

	// warehouse inventory
	$woocrm_inventory = $wpdb->prefix . 'woocrm_inventory';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_inventory'" ) != $woocrm_inventory ) {
		$sql .= 'CREATE TABLE ' . $woocrm_inventory . '(
            wh_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity FLOAT NOT NULL,
            pur_price BIGINT NOT NULL,
            sale_price BIGINT NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            created_user VARCHAR(60) NULL,
            updated_user VARCHAR(60) NULL,
            PRIMARY KEY (wh_id, product_id)
        ); ';
	}

	$woocrm_inventory_his = $wpdb->prefix . 'woocrm_inventory_his';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_inventory_his'" ) != $woocrm_inventory_his ) {
		$sql .= 'CREATE TABLE ' . $woocrm_inventory_his . '(
            his_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            wh_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            quantity FLOAT NOT NULL,
            created_at TIMESTAMP NOT NULL,
            created_user VARCHAR(60) NOT NULL
        ); ';
	}

	// Purchase Debt
	$woocrm_pur_debt = $wpdb->prefix . 'woocrm_pur_debt';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_pur_debt'" ) != $woocrm_pur_debt ) {
		$sql .= 'CREATE TABLE ' . $woocrm_pur_debt . '(
            debt_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            pay_date DATE NOT NULL,
            partner_id BIGINT NOT NULL,
            total_payed BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            created_user VARCHAR(60) NULL,
            updated_user VARCHAR(60) NULL
        ); ';
	}

	// Purchase Debt Item
	$woocrm_pur_debt_item = $wpdb->prefix . 'woocrm_pur_debt_item';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_pur_debt_item'" ) != $woocrm_pur_debt_item ) {
		$sql .= 'CREATE TABLE ' . $woocrm_pur_debt_item . '(
            debt_id BIGINT NOT NULL,
            pur_id BIGINT NOT NULL,
            total_payed BIGINT NOT NULL,
            PRIMARY KEY (debt_id, pur_id)
        ); ';
	}

	// Sale Debt
	$woocrm_sale_debt = $wpdb->prefix . 'woocrm_sale_debt';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_sale_debt'" ) != $woocrm_sale_debt ) {
		$sql .= 'CREATE TABLE ' . $woocrm_sale_debt . '(
            debt_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            pay_date DATE NOT NULL,
            customer_id BIGINT NOT NULL,
            total_payed BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            remark VARCHAR(255) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            created_user VARCHAR(60) NULL,
            updated_user VARCHAR(60) NULL
        ); ';
	}

	// Sale Debt Item
	$woocrm_sale_debt_item = $wpdb->prefix . 'woocrm_sale_debt_item';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_sale_debt_item'" ) != $woocrm_sale_debt_item ) {
		$sql .= 'CREATE TABLE ' . $woocrm_sale_debt_item . '(
            debt_id BIGINT NOT NULL,
            sale_id BIGINT NOT NULL,
            total_payed BIGINT NOT NULL,
            PRIMARY KEY (debt_id, sale_id)
        ); ';
	}

	// Role
	$woocrm_role = $wpdb->prefix . 'woocrm_role';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$woocrm_role'" ) != $woocrm_role ) {
		$sql .= 'CREATE TABLE ' . $woocrm_role . '(
            role_cd VARCHAR(50) NOT NULL PRIMARY KEY,
            role_nm VARCHAR(255) NOT NULL
        ); ';

		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('QUOTAION', 'Báo giá'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PRODUCT', 'Sản phẩm'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PRODUCT_TYPE', 'Loại sản phẩm'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PRODUCT_UNIT', 'Đơn vị tính'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PARTNER', 'Nhà cung cấp'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PUR_ORDER', 'Đơn nhập hàng'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('PUR_DEBT', 'Công nợ nhà cung cấp'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('CUSTOMER', 'Khách hàng'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('SALE_ORDER', 'Đơn bán hàng'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('SALE_DEBT', 'Công nợ khách hàng'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('WAREHOUSE', 'Danh mục kho'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('INVENTORY', 'Quản lý tồn kho'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('ROLE', 'Phân quyền'); ";
		$sql .= ' INSERT INTO ' . $woocrm_role . " VALUES('SETTING', 'Cài đặt'); ";
	}

	if ( ! empty( $sql ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		add_option( 'woocrm_db_version', $woocrm_db_version );
	}
}

function woocrm_drop_table() {
	global $wpdb;
	$sql = '';
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_quotation_item;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_quotation;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_warehouse;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_prod_unit;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_partner;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_prod_type;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_product;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_customer;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_pur_order;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_pur_item;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_sale_order;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_sale_item;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_inventory;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_pur_debt;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_sale_debt;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_inventory_his;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_pur_debt_item;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_sale_debt_item;';
	$wpdb->query( $sql );
	$sql = 'DROP TABLE ' . $wpdb->prefix . 'woocrm_role;';
	$wpdb->query( $sql );

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
