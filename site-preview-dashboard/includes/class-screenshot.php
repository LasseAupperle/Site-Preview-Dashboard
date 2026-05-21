<?php
defined( 'ABSPATH' ) or exit;

class SPD_Screenshot {

	/**
	 * Fetch a screenshot via Screenshotone API and save it locally.
	 *
	 * @param string $url     URL to screenshot.
	 * @param string $site_id Stable unique site key.
	 * @return bool True on success, false on any failure.
	 */
	public static function get_screenshot( string $url, string $site_id ): bool {
		$api_key = get_option( 'spd_api_key', '' );
		if ( empty( $api_key ) ) {
			return false;
		}

		$api_url = add_query_arg(
			array(
				'access_key'      => $api_key,
				'url'             => $url,
				'viewport_width'  => 1280,
				'viewport_height' => 900,
				'format'          => 'jpg',
				'image_quality'   => 85,
				'block_ads'           => 'true',
				'block_trackers'      => 'true',
				'hide_cookie_banners' => 'true',
				'delay'               => 2,
			),
			'https://api.screenshotone.com/take'
		);

		$response = wp_remote_get( $api_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$upload_dir   = wp_upload_dir();
		$previews_dir = $upload_dir['basedir'] . '/site-previews/';

		if ( ! wp_mkdir_p( $previews_dir ) ) {
			return false;
		}

		// Prevent directory listing and PHP execution.
		$index = $previews_dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '<?php // Silence is golden.' );
		}
		$htaccess = $previews_dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "<Files \"*.php\">\n  Require all denied\n</Files>" );
		}

		// Validate response is actually an image before writing.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( strpos( $content_type, 'image/' ) === false ) {
			return false;
		}

		$file_path = $previews_dir . "site-{$site_id}.jpg";
		$written   = file_put_contents( $file_path, wp_remote_retrieve_body( $response ) );

		if ( false === $written || 0 === $written ) {
			return false;
		}

		// Persist timestamp.
		$sites = get_option( 'spd_sites', array() );
		if ( isset( $sites[ $site_id ] ) ) {
			$sites[ $site_id ]['last_updated'] = current_time( 'timestamp' );
			update_option( 'spd_sites', $sites );
		}

		SPD_Counter::increment();

		return true;
	}
}
