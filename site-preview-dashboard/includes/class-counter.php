<?php
defined( 'ABSPATH' ) or exit;

class SPD_Counter {

	private static function option_key(): string {
		return 'spd_counter_' . date( 'Y_m' );
	}

	public static function get_count(): int {
		return (int) get_option( self::option_key(), 0 );
	}

	public static function increment(): void {
		$key   = self::option_key();
		$count = (int) get_option( $key, 0 );
		update_option( $key, $count + 1, false );
	}
}
