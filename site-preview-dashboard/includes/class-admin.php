<?php
defined( 'ABSPATH' ) or exit;

class SPD_Admin {

	public function __construct() {
		add_action( 'admin_menu',             array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_spd_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_spd_add_site',      array( $this, 'add_site' ) );
		add_action( 'wp_ajax_spd_refresh_site',  array( $this, 'ajax_refresh_site' ) );
		add_action( 'wp_ajax_spd_toggle_site',   array( $this, 'ajax_toggle_site' ) );
		add_action( 'wp_ajax_spd_delete_site',   array( $this, 'ajax_delete_site' ) );
		add_action( 'wp_ajax_spd_reorder_sites', array( $this, 'ajax_reorder_sites' ) );
	}

	// ── Menu ────────────────────────────────────────────────────────────────────

	public function add_menu(): void {
		add_menu_page(
			'Site Previews',
			'Site Previews',
			SPD_CAP,
			'site-previews',
			array( $this, 'render_page' ),
			'dashicons-images-alt2',
			30
		);
	}

	// ── Assets ──────────────────────────────────────────────────────────────────

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_site-previews' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'spd-admin', SPD_URL . 'assets/css/admin.css', array(), SPD_VERSION );
		wp_enqueue_script( 'spd-admin', SPD_URL . 'assets/js/admin.js', array(), SPD_VERSION, true );
		wp_localize_script( 'spd-admin', 'spdAdmin', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'spd_admin_nonce' ),
		) );
	}

	// ── Form handlers ────────────────────────────────────────────────────────────

	public function save_settings(): void {
		check_admin_referer( 'spd_save_settings' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_die( 'Unauthorized' );
		}

		$api_key     = sanitize_text_field( wp_unslash( $_POST['spd_api_key'] ?? '' ) );
		$columns     = absint( $_POST['spd_columns'] ?? 3 );
		$columns     = in_array( $columns, array( 2, 3, 4 ), true ) ? $columns : 3;
		$card_height = absint( $_POST['spd_card_height'] ?? 200 );
		$valid_heights = array( 130, 200, 280, 380 );
		$card_height = in_array( $card_height, $valid_heights, true ) ? $card_height : 200;

		update_option( 'spd_api_key', $api_key );
		update_option( 'spd_columns', $columns );
		update_option( 'spd_card_height', $card_height );

		wp_safe_redirect( add_query_arg( array( 'page' => 'site-previews', 'spd_saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function add_site(): void {
		check_admin_referer( 'spd_add_site' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_die( 'Unauthorized' );
		}

		$name = sanitize_text_field( wp_unslash( $_POST['spd_site_name'] ?? '' ) );
		$url  = esc_url_raw( wp_unslash( $_POST['spd_site_url'] ?? '' ) );

		if ( empty( $name ) || empty( $url ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'site-previews', 'spd_error' => 'empty' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$sites   = get_option( 'spd_sites', array() );
		$site_id = uniqid( 'spd' );

		$orders    = array_column( $sites, 'order' );
		$max_order = empty( $orders ) ? -1 : max( $orders );

		$sites[ $site_id ] = array(
			'name'         => $name,
			'url'          => $url,
			'active'       => true,
			'last_updated' => 0,
			'order'        => $max_order + 1,
		);

		update_option( 'spd_sites', $sites );

		wp_safe_redirect( add_query_arg( array( 'page' => 'site-previews', 'spd_added' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── AJAX handlers ────────────────────────────────────────────────────────────

	public function ajax_refresh_site(): void {
		check_ajax_referer( 'spd_admin_nonce', 'nonce' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$site_id = sanitize_text_field( wp_unslash( $_POST['site_id'] ?? '' ) );
		$sites   = get_option( 'spd_sites', array() );

		if ( ! isset( $sites[ $site_id ] ) ) {
			wp_send_json_error( 'Site niet gevonden.' );
		}

		if ( empty( get_option( 'spd_api_key', '' ) ) ) {
			wp_send_json_error( 'API-sleutel ontbreekt. Sla eerst een API-sleutel op onder Instellingen.' );
		}

		$success = SPD_Screenshot::get_screenshot( $sites[ $site_id ]['url'], $site_id );

		if ( $success ) {
			$sites = get_option( 'spd_sites', array() );
			wp_send_json_success( array(
				'last_updated' => date_i18n( 'd M Y H:i', $sites[ $site_id ]['last_updated'] ),
				'timestamp'    => $sites[ $site_id ]['last_updated'],
			) );
		} else {
			wp_send_json_error( 'Screenshot mislukt. Controleer de API-sleutel of probeer het opnieuw.' );
		}
	}

	public function ajax_toggle_site(): void {
		check_ajax_referer( 'spd_admin_nonce', 'nonce' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$site_id = sanitize_text_field( wp_unslash( $_POST['site_id'] ?? '' ) );
		$sites   = get_option( 'spd_sites', array() );

		if ( ! isset( $sites[ $site_id ] ) ) {
			wp_send_json_error( 'Site niet gevonden.' );
		}

		$sites[ $site_id ]['active'] = ! $sites[ $site_id ]['active'];
		update_option( 'spd_sites', $sites );

		wp_send_json_success( array( 'active' => $sites[ $site_id ]['active'] ) );
	}

	public function ajax_delete_site(): void {
		check_ajax_referer( 'spd_admin_nonce', 'nonce' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$site_id = sanitize_text_field( wp_unslash( $_POST['site_id'] ?? '' ) );
		$sites   = get_option( 'spd_sites', array() );

		if ( ! isset( $sites[ $site_id ] ) ) {
			wp_send_json_error( 'Site niet gevonden.' );
		}

		$upload_dir = wp_upload_dir();
		$file       = $upload_dir['basedir'] . "/site-previews/site-{$site_id}.jpg";
		if ( file_exists( $file ) ) {
			unlink( $file );
		}

		unset( $sites[ $site_id ] );
		update_option( 'spd_sites', $sites );

		wp_send_json_success();
	}

	public function ajax_reorder_sites(): void {
		check_ajax_referer( 'spd_admin_nonce', 'nonce' );
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$order = $_POST['order'] ?? array();
		if ( ! is_array( $order ) ) {
			wp_send_json_error( 'Ongeldige volgorde.' );
		}

		$sites = get_option( 'spd_sites', array() );
		foreach ( $order as $position => $site_id ) {
			if ( ! is_string( $site_id ) ) {
				continue;
			}
			$site_id = sanitize_text_field( $site_id );
			if ( isset( $sites[ $site_id ] ) ) {
				$sites[ $site_id ]['order'] = (int) $position;
			}
		}

		update_option( 'spd_sites', $sites );
		wp_send_json_success();
	}

	// ── Admin page render ────────────────────────────────────────────────────────

	public function render_page(): void {
		if ( ! current_user_can( SPD_CAP ) ) {
			wp_die( 'Unauthorized' );
		}

		$sites       = get_option( 'spd_sites', array() );
		$api_key     = get_option( 'spd_api_key', '' );
		$columns     = get_option( 'spd_columns', 3 );
		$card_height = (int) get_option( 'spd_card_height', 200 );
		$count       = SPD_Counter::get_count();
		$month_label = date_i18n( 'F Y' );
		$bar_pct     = min( 100, $count );
		$bar_class   = $count > 80 ? 'spd-over-limit' : '';

		// Sort by order ASC.
		uasort( $sites, function ( $a, $b ) {
			return $a['order'] <=> $b['order'];
		} );
		?>
		<div class="wrap spd-admin">
			<h1>Site Previews</h1>

			<?php if ( isset( $_GET['spd_saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Instellingen opgeslagen.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['spd_added'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Site toegevoegd.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['spd_error'] ) && 'empty' === $_GET['spd_error'] ) : ?>
				<div class="notice notice-error is-dismissible"><p>Naam en URL zijn verplicht.</p></div>
			<?php endif; ?>

			<?php if ( empty( $api_key ) ) : ?>
				<div class="notice notice-warning"><p><strong>Let op:</strong> Geen API-sleutel ingesteld. Screenshots kunnen niet worden opgehaald.</p></div>
			<?php endif; ?>

			<!-- ── Section 1: Settings ─────────────────────────────────────── -->
			<div class="spd-section">
				<h2>Instellingen</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'spd_save_settings' ); ?>
					<input type="hidden" name="action" value="spd_save_settings">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="spd_api_key">Screenshotone API-sleutel</label></th>
							<td>
								<input type="password" id="spd_api_key" name="spd_api_key"
								       value="<?php echo esc_attr( $api_key ); ?>"
								       class="regular-text" autocomplete="off">
								<p class="description">Vind je sleutel op <a href="https://screenshotone.com" target="_blank" rel="noopener">screenshotone.com</a> → Dashboard.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="spd_columns">Aantal kolommen</label></th>
							<td>
								<select id="spd_columns" name="spd_columns">
									<?php foreach ( array( 2, 3, 4 ) as $col ) : ?>
										<option value="<?php echo esc_attr( $col ); ?>" <?php selected( $columns, $col ); ?>>
											<?php echo esc_html( $col ); ?> kolommen
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="spd_card_height">Grootte preview-kaartjes</label></th>
							<td>
								<select id="spd_card_height" name="spd_card_height">
									<?php
									$height_options = array(
										130 => 'Klein',
										200 => 'Normaal',
										280 => 'Groot',
										380 => 'Extra groot',
									);
									foreach ( $height_options as $px => $label ) : ?>
										<option value="<?php echo esc_attr( $px ); ?>" <?php selected( $card_height, $px ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Bepaalt de hoogte van de screenshot-afbeeldingen in het grid.</p>
							</td>
						</tr>
					</table>
					<?php submit_button( 'Instellingen opslaan' ); ?>
				</form>
			</div>

			<!-- ── Section 2: API Counter ──────────────────────────────────── -->
			<div class="spd-section">
				<h2>API-gebruik &mdash; <?php echo esc_html( $month_label ); ?></h2>
				<p><strong>Schermafbeeldingen deze maand: <?php echo esc_html( $count ); ?> / 100</strong> (gratis limiet)</p>
				<div class="spd-progress-wrap">
					<div class="spd-progress-bar <?php echo esc_attr( $bar_class ); ?>"
					     style="width:<?php echo esc_attr( $bar_pct ); ?>%"></div>
				</div>
				<p class="spd-counter-note">
					Gratis limiet Screenshotone: 100 per maand. Bij overschrijding mislukken refreshes.
				</p>
			</div>

			<!-- ── Section 3: Sites ────────────────────────────────────────── -->
			<div class="spd-section">
				<h2>Sites beheren</h2>

				<div class="spd-section-actions">
					<button id="spd-refresh-all" class="button button-secondary">
						Vernieuw alle actieve sites
					</button>
					<span id="spd-refresh-progress" class="spd-progress-text"></span>
				</div>

				<?php if ( empty( $sites ) ) : ?>
					<p class="spd-empty">Nog geen sites toegevoegd. Gebruik het formulier hieronder.</p>
				<?php else : ?>
				<table class="wp-list-table widefat fixed striped spd-sites-table">
					<thead>
						<tr>
							<th class="spd-col-drag" aria-label="Herordenen"></th>
							<th>Naam</th>
							<th>URL</th>
							<th>Status</th>
							<th>Laatste screenshot</th>
							<th>Acties</th>
						</tr>
					</thead>
					<tbody id="spd-sites-tbody">
					<?php foreach ( $sites as $site_id => $site ) :
						$active_class = $site['active'] ? 'spd-active' : 'spd-inactive';
						$active_label = $site['active'] ? 'Actief' : 'Uitgeschakeld';
						$toggle_label = $site['active'] ? 'Uitzetten' : 'Aanzetten';
						$last_updated = $site['last_updated']
							? esc_html( date_i18n( 'd M Y H:i', $site['last_updated'] ) )
							: 'Nog geen screenshot';
					?>
					<tr data-site-id="<?php echo esc_attr( $site_id ); ?>" draggable="true">
						<td class="spd-col-drag">
							<span class="spd-drag-handle" title="Sleep om te herordenen">&#8942;</span>
						</td>
						<td><?php echo esc_html( $site['name'] ); ?></td>
						<td>
							<a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $site['url'] ); ?>
							</a>
						</td>
						<td>
							<span class="spd-status-dot <?php echo esc_attr( $active_class ); ?>"
							      title="<?php echo esc_attr( $active_label ); ?>"></span>
							<span class="spd-status-text"><?php echo esc_html( $active_label ); ?></span>
						</td>
						<td class="spd-last-updated"><?php echo esc_html( $last_updated ); ?></td>
						<td class="spd-actions">
							<button class="button spd-btn-refresh"
							        data-site-id="<?php echo esc_attr( $site_id ); ?>">Vernieuwen</button>
							<button class="button spd-btn-toggle"
							        data-site-id="<?php echo esc_attr( $site_id ); ?>"
							        data-active="<?php echo $site['active'] ? '1' : '0'; ?>">
								<?php echo esc_html( $toggle_label ); ?>
							</button>
							<button class="button spd-btn-delete"
							        data-site-id="<?php echo esc_attr( $site_id ); ?>">Verwijder</button>
							<span class="spd-action-feedback"></span>
						</td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>

				<h3>Nieuwe site toevoegen</h3>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'spd_add_site' ); ?>
					<input type="hidden" name="action" value="spd_add_site">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="spd_site_name">Sitenaam</label></th>
							<td>
								<input type="text" id="spd_site_name" name="spd_site_name"
								       class="regular-text" placeholder="Mijn site" required>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="spd_site_url">URL</label></th>
							<td>
								<input type="url" id="spd_site_url" name="spd_site_url"
								       class="regular-text" placeholder="https://example.com" required>
							</td>
						</tr>
					</table>
					<?php submit_button( 'Site toevoegen', 'secondary' ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
