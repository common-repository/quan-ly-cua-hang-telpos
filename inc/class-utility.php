<?php

class Utility {

	public static function is_date( $date, $format = 'Y-m-d' ) {
		$dt = DateTime::createFromFormat( $format, $date );
		return $dt && $dt->format( $format ) === $date;
	}
}
