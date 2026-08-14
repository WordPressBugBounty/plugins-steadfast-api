<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'STDF_Admin_Menu' ) ) {

	class STDF_Admin_Menu {

		protected static $_instance = null;

		/**
		 * All Hooks
		 */
		function __construct() {
			add_action( 'admin_menu', array( $this, 'register_steadfast_admin_menu_page' ) );
			add_action( 'admin_init', array( $this, 'register_admin_settings_fields' ) );

			add_action( 'admin_init',array($this,'remove_admin_notice'));
		}


		function remove_admin_notice()
		{
			if (isset($_GET['page']) && $_GET['page'] === 'steadfast') {

				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');

			}
		}



		/**
		 * @return void
		 */
		public function register_admin_settings_fields() {
			$setting_nonce = isset( $_POST['stdf_settings_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['stdf_settings_nonce_field'] ) ) : '';

			if ( $setting_nonce && wp_verify_nonce( $setting_nonce, 'stdf_settings_nonce' ) ) {

				if ( ! empty( $_FILES ) && isset( $_FILES['stdf_business_logo'] ) ) {
					$uploaded_image = wp_handle_upload( $_FILES['stdf_business_logo'], array( 'test_form' => false ) );

					if ( isset( $uploaded_image['url'] ) ) {
						update_option( 'stdf_business_logo', $uploaded_image['url'] );
					}
				}
			}

			add_settings_section( 'settings_section', ' ', array( $this, 'render_settings_section' ), 'stdf_settings' );

			$fields = array(
				'stdf_settings_tab_checkbox' => array(
					'title' => esc_html__( 'Enable/Disable', 'steadfast-api'),
					'type'  => 'checkbox',
				),

				'stdf_settings_tab_notes' => array(
					'title'    => esc_html__( 'Notes', 'steadfast-api'),
					'type'     => 'checkbox',
					'subtitle' => esc_html__( 'Please enable this checkbox for send customer notes', 'steadfast-api'),
				),

				'api_settings_tab_api_key' => array(
					'title'       => esc_html__( 'API Key *', 'steadfast-api'),
					'type'        => 'password',
					'placeholder' => esc_html__( 'Enter your api key', 'steadfast-api'),
					'subtitle'    => esc_html__( 'This field is required', 'steadfast-api'),
				),

				'api_settings_tab_api_secret_key' => array(
					'title'       => esc_html__( 'Secret Key *', 'steadfast-api'),
					'type'        => 'password',
					'placeholder' => esc_html__( 'Enter your secret key', 'steadfast-api'),
					'subtitle'    => esc_html__( 'This field is required', 'steadfast-api'),
				),

				'stdf_webhook_url' => array(
					'title'    => esc_html__( 'Webhook Callback URL', 'steadfast-api'),
					'type'     => 'webhook_url',
					'subtitle' => esc_html__( 'Copy and paste this URL into your SteadFast Merchant Dashboard API Webhook section.', 'steadfast-api'),
				),

				'stdf_webhook_token' => array(
					'title'    => esc_html__( 'Webhook Secret Token', 'steadfast-api'),
					'type'     => 'webhook_token',
					'subtitle' => esc_html__( 'Copy and paste this Token as the Authorization Token in your SteadFast Dashboard.', 'steadfast-api'),
				),

				'stdf_business_title' => array(
					'title' => esc_html__( 'Please use this fields for print your invoice', 'steadfast-api'),
					'type'  => 'hidden',
				),

				'stdf_business_name' => array(
					'title'       => esc_html__( 'Business Name', 'steadfast-api'),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Business Name(optional)', 'steadfast-api'),
					'subtitle'    => esc_html__( 'Please enter your business name.', 'steadfast-api'),
				),

				'stdf_business_address' => array(
					'title'    => esc_html__( 'Business Address', 'steadfast-api'),
					'type'     => 'text',
					'subtitle' => esc_html__( 'Please enter your business address.', 'steadfast-api'),
				),

				'stdf_business_email' => array(
					'title'    => esc_html__( 'Business Email', 'steadfast-api'),
					'type'     => 'email',
					'subtitle' => esc_html__( 'Please enter your business email.', 'steadfast-api'),
				),

				'stdf_business_number' => array(
					'title'    => esc_html__( 'Business Number', 'steadfast-api'),
					'type'     => 'text',
					'subtitle' => esc_html__( 'Please enter your business number.', 'steadfast-api'),
				),

				'stdf_term_condition' => array(
					'title'    => esc_html__( 'Terms & Conditions', 'steadfast-api'),
					'type'     => 'textarea',
					'subtitle' => esc_html__( 'Please enter your business T&C.', 'steadfast-api'),
				),

			);

			foreach ( $fields as $field_id => $field_data ) {
				add_settings_field(
					$field_id,
					$field_data['title'],
					array( $this, 'render_setting_fields' ),
					'stdf_settings',
					'settings_section',
					array(
						'field_id'    => $field_id,
						'field_type'  => $field_data['type'],
						'placeholder' => $field_data['placeholder'] ?? '',
						'subtitle'    => $field_data['subtitle'] ?? '',
					)
				);
				register_setting( 'stdf_settings', $field_id );
			}

		}

		/**
		 * @return void
		 */
		public function render_settings_section() {
			echo '<h2>' . esc_html__( 'SteadFast Courier Settings', 'steadfast-api') . '</h2>';
		}


		/**
		 * @param $args
		 *
		 * @return void
		 */
		public function render_setting_fields( $args ): void {
			$field_id    = $args['field_id'];
			$field_type  = $args['field_type'];
			$field_value = get_option( $field_id );
			$placeholder = $args['placeholder'];
			$subtitle    = isset( $args['subtitle'] ) ? sanitize_text_field( $args['subtitle'] ) : '';

			if ( $field_type == 'checkbox' ) {
				echo '<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_id ) . '" value="yes" ' . checked( 'yes', $field_value, false ) . ' /><p>' . esc_html( $subtitle ) . '</p>';
			} elseif ( $field_type == 'textarea' ) {
				echo '<textarea name="stdf_term_condition" id="std_term_condition" cols="33" rows="2">' . esc_attr( $field_value ) . '</textarea>';
			} elseif ( $field_type == 'webhook_url' ) {
				$webhook_url = get_rest_url(null, 'stdf-api/v1/webhook');
				echo '<input type="text" id="stdf-webhook-url-input" value="' . esc_url($webhook_url) . '" readonly onclick="this.select();" style="background: #edf2f7; cursor: copy; width: 420px; font-family: monospace;" />';
				
				$last_received = get_option('stdf_last_webhook_received');
				if ($last_received) {
					$time_diff = human_time_diff(strtotime($last_received), current_time('timestamp'));
					echo '<p style="color: #2f855a; font-size: 11px; margin-top: 5px; font-weight: 600; display: flex; align-items: center; gap: 4px;" id="stdf-webhook-last-received-status">● Last signal received: ' . esc_html($time_diff) . ' ago (updates dynamically when signals arrive)</p>';
				} else {
					echo '<p style="color: #718096; font-size: 11px; margin-top: 5px;" id="stdf-webhook-last-received-status">● Last signal received: Never</p>';
				}
				echo '<p>' . esc_html( $subtitle ) . '</p>';
			} elseif ( $field_type == 'webhook_token' ) {
				$token = get_option('stdf_webhook_token');
				if (empty($token)) {
					$token = wp_generate_password(32, false);
					update_option('stdf_webhook_token', $token);
				}
				echo '<input type="text" id="stdf-webhook-token-input" value="' . esc_attr($token) . '" readonly onclick="this.select();" style="background: #edf2f7; cursor: copy; width: 420px; font-family: monospace;" /><p>' . esc_html( $subtitle ) . '</p>';
			} else {
				$autocomplete = ( $field_type === 'password' ) ? ' autocomplete="new-password"' : '';
				if ( $field_type === 'password' ) {
					echo '<div class="std-password-wrapper" style="position: relative; display: inline-block;">';
					echo '<input type="password" id="' . esc_attr( $field_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" name="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '"' . $autocomplete . ' style="padding-right: 38px !important;" />';
					echo '<span class="std-password-toggle" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #718096; display: flex; align-items: center; z-index: 10;">';
					echo '<svg class="eye-open" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="transition: color 0.15s ease;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
					echo '<svg class="eye-closed hidden" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="transition: color 0.15s ease;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
					echo '</span>';
					echo '</div><p>' . esc_html( $subtitle ) . '</p>';
				} else {
					echo '<input type="' . esc_attr( $field_type ) . '" id="' . esc_attr( $field_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" name="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '"' . $autocomplete . ' /><p>' . esc_html( $subtitle ) . '</p>';
				}
			}
		}

		/**
		 *  Register SteadFast Admin Menu Page.
		 * @return void
		 */
		function register_steadfast_admin_menu_page() {
			$svg_content = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">' .
			               '<title>Plugin Icon SteadFast</title>' .
			               '<path fill="#fff" d="M160.11,187.67l-42.49,26.41s-65.46-85-104.5-106.8L470.17,62.5S125.66,111.88,99.25,128Z"/>' .
			               '<path fill="#fff" d="M498.88,62.5S345,103.84,230.16,325.48l-43.64-97.61-36.75,27.56s70.05,127.47,74.64,194.07C223.27,449.5,318.59,172.74,498.88,62.5Z"/>' .
			               '<path fill="#fff" d="M40.68,317.44S159,171.59,431.13,78.58A1328.36,1328.36,0,0,0,40.68,317.44Z"/>' .
			               '</svg>';

			$svg = 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
			add_menu_page( 'SteadFast', 'SteadFast', 'manage_options', 'steadfast', array( $this, 'stdf_admin_menu_callback' ), $svg, '5' );
		}

		/**
		 * SteadFast Admin Menu Callback.
		 * @return void
		 */
		function stdf_admin_menu_callback()
		{
			$nonce_action = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

			if ($nonce_action && wp_verify_nonce(wp_unslash($nonce_action), 'dashboard_tab_nonce')) {
				$active_tab = 'dashboard';
			} elseif (wp_verify_nonce(wp_unslash($nonce_action), 'settings_tab_nonce')) {
				$active_tab = 'settings';
			} else {
				$active_tab = 'dashboard';
			}

			?>
				<div class="wrap">
					<h2 class="nav-tab-wrapper">
						<a href="?page=steadfast&tab=dashboard&_wpnonce=<?php echo esc_attr(wp_create_nonce('dashboard_tab_nonce')); ?>" data-tab="dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Dashboard', 'steadfast-api'); ?></a>
						<a href="?page=steadfast&tab=settings&_wpnonce=<?php echo esc_attr(wp_create_nonce('settings_tab_nonce')); ?>" data-tab="settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('API Settings', 'steadfast-api'); ?></a>
					</h2>

					<div class="tab-content std-admin-menu">
						<!-- Dashboard Panel -->
						<div id="std-tab-content-dashboard" class="std-tab-panel <?php echo $active_tab !== 'dashboard' ? 'hidden' : ''; ?>">
							<div class="std-dashboard">
								<div class="std-header">
									<img src="<?php echo esc_url( esc_url_raw( STDF_PLUGIN_URL ) . 'assets/admin/img/logo.png' ); ?>" alt="SteadFast Courier" class="std-dashboard-logo" />
									<span class="std-badge-online checking" id="std-connection-badge">● <?php echo esc_html__('Checking Connection...', 'steadfast-api'); ?></span>
								</div>

								<div class="std-dashboard-grid">
									<!-- Left Column: Balance & Contact -->
									<div class="std-dashboard-col">
										<div class="std-section std-balance-box" style="margin-top: 0;">
											<div class="std-section-header">
												<span class="std-icon-wrapper"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.103-.897 2-2 2H5c-1.103 0-2-.897-2-2V5c0-1.103.897-2 2-2h14c1.103 0 2 .897 2 2v1h-9c-1.103 0-2 .897-2 2v8c0 1.103.897 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.828 0-1.5-.672-1.5-1.5s.672-1.5 1.5-1.5 1.5.672 1.5 1.5-.672 1.5-1.5 1.5z"/></svg></span>
												<h3 class="std-section-title"><?php echo esc_html__('Check Balance', 'steadfast-api'); ?></h3>
											</div>

											<div class="std-balance-actions">
												<button class="std-btn std-balance" data-stdf-balance-nonce="<?php echo esc_attr(wp_create_nonce('stdf-balance-verify')); ?>" value="check-yes">
													<?php echo esc_html__('Check Balance', 'steadfast-api'); ?>
												</button>
											</div>

											<div class="std-balance-result hidden std-current-bal">
												<span><?php echo esc_html__('Current Wallet Balance:', 'steadfast-api'); ?> </span>
												<span class="balance"></span>
											</div>
										</div>

										<div class="std-section std-contact">
											<div class="std-section-header">
												<span class="std-icon-wrapper"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12c0 1.954.568 3.774 1.547 5.32L2.057 22l4.887-1.442C8.36 21.437 10.119 22 12 22c5.514 0 10-4.486 10-10S17.514 2 12 2zm0 18c-1.68 0-3.23-.483-4.542-1.314l-.323-.205-3.053.901.854-2.883-.243-.393C3.766 14.889 3.2 13.504 3.2 12 3.2 7.147 7.147 3.2 12 3.2s8.8 3.947 8.8 8.8-3.947 8.8-8.8 8.8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg></span>
												<h4 class="std-section-title"><?php echo esc_html__('Facing an issue? Please let us know', 'steadfast-api'); ?></h4>
											</div>

											<div class="std-social-links">
												<a class="std-social std-facebook" target="_blank" href="<?php echo esc_url('https://www.facebook.com/steadfastcourier'); ?>">
													<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg> Facebook
												</a>

												<a class="std-social std-whatsapp" target="_blank" href="<?php echo esc_url('https://wa.me/+8801722743076'); ?>">
													<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.443-4.436-9.885-9.888-9.885-5.45 0-9.886 4.434-9.89 9.885-.001 2.225.618 3.891 1.67 5.455l-.999 3.648 3.736-.965zm11.334-7.502c-.302-.15-1.786-.881-2.07-.985-.285-.102-.492-.152-.697.152-.207.305-.8.985-.98 1.187-.18.203-.36.228-.662.078-3.003-1.5-3.694-2.42-4.518-3.834-.23-.397.23-.368.658-.78.118-.115.228-.276.34-.415.112-.137.15-.237.225-.397.075-.16.038-.3-.02-.45-.057-.15-.492-1.187-.675-1.637-.18-.432-.375-.373-.51-.38-.135-.006-.29-.007-.445-.007-.155 0-.408.058-.62.29-.213.23-.812.793-.812 1.936 0 1.143.83 2.246.946 2.4.116.155 1.634 2.5 3.96 3.511 2.327 1.011 2.327.674 3.158.599.83-.075 1.786-.73 2.035-1.436.25-.706.25-1.31.175-1.436-.075-.125-.284-.2-.587-.35z"/></svg> WhatsApp
												</a>
											</div>
										</div>
									</div>

									<!-- Right Column: Order Analytics -->
									<div class="std-dashboard-col">
										<?php 
										$analytics = stdf_get_order_analytics();
										?>
										<div class="std-section std-analytics-box" style="margin-top: 0;">
											<div class="std-section-header">
												<span class="std-icon-wrapper">
													<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
												</span>
												<h3 class="std-section-title"><?php echo esc_html__('SteadFast Analytics', 'steadfast-api'); ?></h3>
											</div>

											<div class="std-analytics-summary">
												<div class="std-analytics-card">
													<span class="std-analytics-num"><?php echo esc_html($analytics['total_sent']); ?></span>
													<span class="std-analytics-lbl"><?php echo esc_html__('Total Booked Orders', 'steadfast-api'); ?></span>
												</div>
											</div>

											<div class="std-analytics-list">
												<div class="std-analytics-row">
													<div class="std-analytics-status">
														<span class="std-dot std-dot-delivered"></span>
														<span class="std-analytics-row-lbl"><?php echo esc_html__('Delivered', 'steadfast-api'); ?></span>
													</div>
													<span class="std-analytics-row-val"><?php echo esc_html($analytics['delivered']); ?> (<?php echo esc_html($analytics['delivered_percent']); ?>%)</span>
												</div>
												<div class="std-analytics-progress-track">
													<div class="std-analytics-progress-bar std-bg-delivered" style="width: <?php echo esc_attr($analytics['delivered_percent']); ?>%"></div>
												</div>

												<div class="std-analytics-row">
													<div class="std-analytics-status">
														<span class="std-dot std-dot-pending"></span>
														<span class="std-analytics-row-lbl"><?php echo esc_html__('Pending / In Review', 'steadfast-api'); ?></span>
													</div>
													<span class="std-analytics-row-val"><?php echo esc_html($analytics['pending']); ?> (<?php echo esc_html($analytics['pending_percent']); ?>%)</span>
												</div>
												<div class="std-analytics-progress-track">
													<div class="std-analytics-progress-bar std-bg-pending" style="width: <?php echo esc_attr($analytics['pending_percent']); ?>%"></div>
												</div>

												<div class="std-analytics-row">
													<div class="std-analytics-status">
														<span class="std-dot std-dot-cancelled"></span>
														<span class="std-analytics-row-lbl"><?php echo esc_html__('Cancelled', 'steadfast-api'); ?></span>
													</div>
													<span class="std-analytics-row-val"><?php echo esc_html($analytics['cancelled']); ?> (<?php echo esc_html($analytics['cancelled_percent']); ?>%)</span>
												</div>
												<div class="std-analytics-progress-track">
													<div class="std-analytics-progress-bar std-bg-cancelled" style="width: <?php echo esc_attr($analytics['cancelled_percent']); ?>%"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Settings Panel -->
						<div id="std-tab-content-settings" class="std-tab-panel <?php echo $active_tab !== 'settings' ? 'hidden' : ''; ?>">
							<?php $uploaded_image_url = get_option('stdf_business_logo'); ?>
							<div class="wrap std-settings">
								<form id="std-settings-form" method="post" action="<?php echo esc_url(admin_url('options.php')); ?>" enctype="multipart/form-data">
									<?php wp_nonce_field('stdf_settings_nonce', 'stdf_settings_nonce_field'); ?>
									<?php settings_fields('stdf_settings'); ?>
									<?php do_settings_sections('stdf_settings'); ?>
									<label for="std_business_logo"><h2><?php echo esc_html__('Business Logo', 'steadfast-api') ?></h2></label>
									<input type="file" name="stdf_business_logo" id="std_business_logo"/>
									<?php if ($uploaded_image_url): ?>
											<img src="<?php echo esc_attr($uploaded_image_url); ?>" alt="Uploaded Image" style="max-width: 150px; max-height: 80px;"/>
									<?php endif; ?>
									<?php submit_button(); ?>
									<div id="std-settings-progress" class="hidden">
										<div class="std-progress-track">
											<div class="std-progress-bar"></div>
										</div>
										<span class="std-progress-text"></span>
									</div>
								</form>
							</div>
						</div>

					</div>
				</div>
				<?php
		}


		/**
		 * @return self|null
		 */
		public
		static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}
}

STDF_Admin_Menu::instance();