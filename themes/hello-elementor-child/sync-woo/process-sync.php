<?php

include_once( "includes/SimpleXLSX.php" );

use Shuchkin\SimpleXLSX;

$file = get_stylesheet_directory() . "/sync-woo/data/stock-precios.xlsx";

if ( $xlsx = SimpleXLSX::parse( $file ) ) {
	foreach($xlsx->rows() as $index => $row){
		if ( $index > 0 ){
			$id = wc_get_product_id_by_sku($row[0]);
			if ( $id ){
				$price = $row[1];
				$stock = $row[2];

				$product = wc_get_product( $id );
				$product->set_regular_price($price);
				$product->set_stock_quantity($stock);
				$product->save();
			}
		}
	}
} else {
	error_log( print_r( SimpleXLSX::parseError(), true ) );
}

