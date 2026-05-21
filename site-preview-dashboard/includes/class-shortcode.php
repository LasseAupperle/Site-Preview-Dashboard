<?php
defined( 'ABSPATH' ) or exit;

class SPD_Shortcode {

	public function register(): void {
		add_shortcode( 'site-previews', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	public function maybe_enqueue_assets(): void {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}
		if ( has_shortcode( $post->post_content, 'site-previews' ) ) {
			$this->enqueue_assets();
		}
	}

	private function enqueue_assets(): void {
		$sites       = get_option( 'spd_sites', array() );
		$sites_for_js = array();
		foreach ( $sites as $site_id => $site ) {
			$sites_for_js[ $site_id ] = array(
				'name' => $site['name'],
				'url'  => $site['url'],
			);
		}

		wp_enqueue_style(
			'spd-frontend',
			SPD_URL . 'assets/css/frontend.css',
			array(),
			SPD_VERSION
		);
		wp_enqueue_script(
			'spd-frontend',
			SPD_URL . 'assets/js/frontend.js',
			array(),
			SPD_VERSION,
			true
		);
		wp_localize_script( 'spd-frontend', 'spdData', array(
			'sites' => $sites_for_js,
		) );
	}

	public function render(): string {
		$sites       = get_option( 'spd_sites', array() );
		$columns     = get_option( 'spd_columns', 3 );
		$card_height = (int) get_option( 'spd_card_height', 200 );
		$upload_dir  = wp_upload_dir();

		// Filter: active sites with an existing screenshot file.
		$visible = array();
		foreach ( $sites as $site_id => $site ) {
			if ( empty( $site['active'] ) ) {
				continue;
			}
			$file = $upload_dir['basedir'] . "/site-previews/site-{$site_id}.jpg";
			if ( ! file_exists( $file ) ) {
				continue;
			}
			$visible[ $site_id ] = $site;
		}

		if ( empty( $visible ) ) {
			return '<p class="spd-empty">Geen site-previews beschikbaar. Voeg sites toe en maak een screenshot via het beheerderspaneel.</p>';
		}

		// Sort by order ASC.
		uasort( $visible, function ( $a, $b ) {
			return $a['order'] <=> $b['order'];
		} );

		$base_url = $upload_dir['baseurl'];
		$columns  = absint( $columns );

		ob_start();
		?>
		<style>.spd-card img { height: <?php echo absint( $card_height ); ?>px; }</style>
		<div class="spd-grid spd-cols-<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $visible as $site_id => $site ) : ?>
			<div class="spd-card"
			     data-site-id="<?php echo esc_attr( $site_id ); ?>"
			     data-site-url="<?php echo esc_url( $site['url'] ); ?>"
			     data-site-name="<?php echo esc_attr( $site['name'] ); ?>"
			     role="button"
			     tabindex="0"
			     aria-label="<?php echo esc_attr( 'Preview van ' . $site['name'] . ' openen' ); ?>">
				<img src="<?php echo esc_url( $base_url . "/site-previews/site-{$site_id}.jpg?v=" . $site['last_updated'] ); ?>"
				     alt="<?php echo esc_attr( $site['name'] ); ?>"
				     loading="lazy">
				<span class="spd-label"><?php echo esc_html( $site['name'] ); ?></span>
			</div>
			<?php endforeach; ?>
		</div>

		<div id="spd-popup" class="spd-popup-overlay" aria-hidden="true" role="dialog" aria-modal="true"
		     aria-labelledby="spd-popup-title">
			<div class="spd-popup-window">
				<div class="spd-popup-header">
					<span id="spd-popup-title" class="spd-popup-site-name"></span>
					<div class="spd-popup-controls">
						<a id="spd-popup-visit" href="#" target="_blank" rel="noopener noreferrer"
						   class="spd-popup-visit-btn">Bezoek site &rarr;</a>
						<button id="spd-popup-close" class="spd-popup-close" aria-label="Sluiten">&times;</button>
					</div>
				</div>
				<iframe id="spd-popup-iframe" src="" width="100%"
				        title="Site preview" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
