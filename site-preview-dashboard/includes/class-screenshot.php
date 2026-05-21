<?php
defined( 'ABSPATH' ) or exit;

class SPD_Screenshot {

	public static string $last_error = '';

	public static function get_screenshot( string $url, string $site_id ): bool {
		self::$last_error = '';

		$api_key = get_option( 'spd_api_key', '' );
		if ( empty( $api_key ) ) {
			self::$last_error = 'API-sleutel is leeg in de database.';
			return false;
		}

		$api_url = add_query_arg(
			array(
				'access_key'          => $api_key,
				'url'                 => $url,
				'viewport_width'      => 1280,
				'viewport_height'     => 900,
				'format'              => 'jpg',
				'image_quality'       => 85,
				'block_ads'           => 'true',
				'block_trackers'      => 'true',
				'hide_cookie_banners' => 'true',
				'delay'               => 2,
			),
			'https://api.screenshotone.com/take'
		);

		$response = wp_remote_get( $api_url, array( 'timeout' => 45 ) );

		if ( is_wp_error( $response ) ) {
			self::$last_error = 'HTTP-fout: ' . $response->get_error_message();
			return false;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			self::$last_error = 'API HTTP ' . $http_code . ': ' . wp_remote_retrieve_body( $response );
			return false;
		}

		// Validate the response is an image (case-insensitive, non-fatal if header missing).
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! empty( $content_type ) && stripos( $content_type, 'image/' ) === false ) {
			self::$last_error = 'Onverwacht content-type: ' . $content_type;
			return false;
		}

		$upload_dir   = wp_upload_dir();
		$previews_dir = $upload_dir['basedir'] . '/site-previews/';

		if ( ! wp_mkdir_p( $previews_dir ) ) {
			self::$last_error = 'Map aanmaken mislukt: ' . $previews_dir;
			return false;
		}

		// Prevent directory listing and PHP execution (force overwrite for compatibility).
		file_put_contents( $previews_dir . 'index.php', '<?php // Silence is golden.' );
		file_put_contents(
			$previews_dir . '.htaccess',
			"<FilesMatch \"\\.php$\">\n  Order deny,allow\n  Deny from all\n</FilesMatch>"
		);

		$file_path = $previews_dir . "site-{$site_id}.jpg";
		$written   = file_put_contents( $file_path, wp_remote_retrieve_body( $response ) );

		if ( false === $written || 0 === $written ) {
			self::$last_error = 'Bestand schrijven mislukt: ' . $file_path;
			return false;
		}

		$sites = get_option( 'spd_sites', array() );
		if ( isset( $sites[ $site_id ] ) ) {
			$sites[ $site_id ]['last_updated'] = current_time( 'timestamp' );
			update_option( 'spd_sites', $sites );
		}

		SPD_Counter::increment();
		return true;
	}
}
