<?php
defined( 'ABSPATH' ) or exit;

class SPD_Cron {

	public static function activate(): void {
		if ( ! wp_next_scheduled( 'spd_weekly_refresh' ) ) {
			wp_schedule_event( time(), 'weekly', 'spd_weekly_refresh' );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'spd_weekly_refresh' );
	}

	public static function add_weekly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly' ),
			);
		}
		return $schedules;
	}

	public static function refresh_all_sites(): void {
		if ( empty( get_option( 'spd_api_key', '' ) ) ) {
			return;
		}

		$sites = get_option( 'spd_sites', array() );
		foreach ( $sites as $site_id => $site ) {
			if ( ! empty( $site['active'] ) ) {
				SPD_Screenshot::get_screenshot( $site['url'], $site_id );
			}
		}
	}
}
