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
			} else {
				echo '<input type="' . esc_attr( $field_type ) . '" id="' . esc_attr( $field_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" name="' . esc_attr( $field_id ) . '" value="' . esc_attr( $field_value ) . '" /><p>' . esc_html( $subtitle ) . '</p>';
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
						<a href="?page=steadfast&tab=dashboard&_wpnonce=<?php echo esc_attr(wp_create_nonce('dashboard_tab_nonce')); ?>" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Dashboard', 'steadfast-api'); ?></a>
						<a href="?page=steadfast&tab=settings&_wpnonce=<?php echo esc_attr(wp_create_nonce('settings_tab_nonce')); ?>" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('API Settings', 'steadfast-api'); ?></a>
					</h2>

					<div class="tab-content std-admin-menu">
						<?php
						switch ($active_tab) {
							case 'settings':
								$uploaded_image_url = get_option('stdf_business_logo'); ?>
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
											</form>
										</div>
										<?php break;

							case 'dashboard':

								echo '<div class="std-dashboard">';

								echo '<div class="std-title"></div>';

								echo '<div class="std-section std-balance-box">';
								echo '<h3 class="std-section-title">' . esc_html__('Check Balance', 'steadfast-api') . '</h3>';

								echo '<button class="std-btn std-balance" data-stdf-balance-nonce="' . esc_attr(wp_create_nonce('stdf-balance-verify')) . '" value="check-yes">'
									. esc_html__('Check', 'steadfast-api') .
									'</button>';

								echo '<div class="std-balance-result hidden std-current-bal">';
								echo '<span>' . esc_html__('Your Current Balance is : ', 'steadfast-api') . '</span>';
								echo '<span class="balance"></span>';
								echo '</div>';
								echo '</div>';

								echo '<div class="std-section std-contact">';
								echo '<h4 class="std-section-title">' . esc_html__('Facing an issue, Please let me know', 'steadfast-api') . '</h4>';

								echo '<div class="std-social-links">';
								echo '<a class="std-social std-facebook" target="_blank" href="' . esc_url('https://www.facebook.com/steadfastcourier') . '">'
									. esc_html__('Facebook', 'steadfast-api') .
									'</a>';

								echo '<a class="std-social std-whatsapp" target="_blank" href="' . esc_url('https://wa.me/+8801722743076') . '">'
									. esc_html__('Whatsapp', 'steadfast-api') .
									'</a>';
								echo '</div>';
								echo '</div>';

								echo '</div>';


								break;
						} ?>

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