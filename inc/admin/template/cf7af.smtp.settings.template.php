<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$cf7af_message      = '';
	$cf7af_custom_error = '';
	$cf7af_test_errors  = array();

	$cf7af_smtp_option = get_option( 'cf7af_smtp_option' );

	if ( isset( $_POST['cf7af_smtp_test_submit'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_smtptest_nonce_name' ) ) {
			$cf7af_test_errors[] = __( 'Nonce check failed.', 'abandoned-contact-form-7' );
		}
		global $wp_version;
		require_once ABSPATH . WPINC . '/class-phpmailer.php';

		if ( version_compare( $wp_version, '5.5.1', '>=' ) ) {
			// WordPress version is greater than 4.3
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			$cf7af_mail = new PHPMailer\PHPMailer\PHPMailer();
		}  else {
			$cf7af_mail = new PHPMailer( true );
		}

		$cf7af_to_email = isset( $_POST['cf7af_test_to_email'] ) ? sanitize_email( wp_unslash( $_POST['cf7af_test_to_email'] ) ) : '';
		$cf7af_subject  = isset( $_POST['cf7af_test_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_test_subject'] ) ) : '';
		$cf7af_body     = isset( $_POST['cf7af_test_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cf7af_test_message'] ) ) : '';

		$cf7af_ret = array();
		//$cf7af_mail = new PHPMailer( true );

		try {

			$cf7af_charset       = get_bloginfo( 'charset' );
			$cf7af_mail->CharSet = $cf7af_charset;
			$cf7af_from_name  = $this->cf7af_smtp_opt['cf7af_from_name'];
			$cf7af_from_email = $this->cf7af_smtp_opt['cf7af_from_email'];

			$cf7af_mail->IsSMTP();

			// send plain text test email
			$cf7af_mail->ContentType = 'text/plain';
			$cf7af_mail->IsHTML( false );

			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->cf7af_smtp_opt['cf7af_smtp_auth'] ) {
				$cf7af_mail->SMTPAuth = true;
				$cf7af_mail->Username = $this->cf7af_smtp_opt['cf7af_smtp_username'];
				$cf7af_mail->Password = $this->cf7af_smtp_opt['cf7af_smtp_password'];
			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->cf7af_smtp_opt['cf7af_smtp_ency_type'] ) {
				$cf7af_mail->SMTPSecure = $this->cf7af_smtp_opt['cf7af_smtp_ency_type'];
			}

			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$cf7af_mail->SMTPAutoTLS = false;

			/* Set the other options */
			$cf7af_mail->Host = $this->cf7af_smtp_opt['cf7af_smtp_host'];
			$cf7af_mail->Port = $this->cf7af_smtp_opt['cf7af_smtp_port'];

			$cf7af_mail->SetFrom( $cf7af_from_email, $cf7af_from_name );
			//This should set Return-Path header for servers that are not properly handling it, but needs testing first
			//$cf7af_mail->Sender		 = $cf7af_mail->From;
			$cf7af_mail->Subject = $cf7af_subject;
			$cf7af_mail->Body    = $cf7af_body;
			$cf7af_mail->AddAddress( $cf7af_to_email );
			$cf7af_debug_msg = '';
			$cf7af_mail->Debugoutput = function ( $str, $level ) use ( &$cf7af_debug_msg ) {
				$cf7af_debug_msg .= $str . '<br>';
			};
			$cf7af_mail->SMTPDebug = 1;
			//set reasonable timeout
			$cf7af_mail->Timeout = 10;

			/* Send mail and return result */
			$cf7af_mail->Send();
			$cf7af_mail->ClearAddresses();
			$cf7af_mail->ClearAllRecipients();
			$cf7af_success = 1;
		} catch ( Exception $e ) {
			$cf7af_success = 0;
			$cf7af_ret['error'] = $cf7af_mail->ErrorInfo;
		}

		$cf7af_ret['debug_log'] = $cf7af_debug_msg;

		if ( 0 === $cf7af_success ) {
			$cf7af_test_errors[] = __( 'Error on send mail.', 'abandoned-contact-form-7' );
			$cf7af_test_errors[] = $cf7af_ret['error'];
			$cf7af_test_errors[] = $cf7af_ret['debug_log'];
		}

		if ( empty( $cf7af_test_errors ) ) {
			$cf7af_message .= __( 'Test email was successfully sent. No errors occurred during the process.', 'abandoned-contact-form-7' );
		}

	}

	if ( isset( $_POST['cf7af_smtp_submit'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_smtp_nonce_name' ) ) {
			$cf7af_custom_error .= ' ' . __( 'Nonce check failed.', 'abandoned-contact-form-7' );
		}

		if ( isset( $_POST['cf7af_from_email'] ) ) {
			$cf7af_post_from_email = sanitize_email( wp_unslash( $_POST['cf7af_from_email'] ) );
			if ( is_email( $cf7af_post_from_email ) ) {
				$cf7af_smtp_option['cf7af_from_email'] = $cf7af_post_from_email;
			} else {
				$cf7af_custom_error .= ' ' . __( "Please enter a valid email address in the 'From Email Address' field.", 'abandoned-contact-form-7' );
			}
		}
		$cf7af_smtp_option['cf7af_from_name']     = isset( $_POST['cf7af_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_from_name'] ) ) : '';
		$cf7af_smtp_option['cf7af_smtp_host']     = isset( $_POST['cf7af_smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_host'] ) ) : '';
		$cf7af_smtp_option['cf7af_smtp_ency_type'] = isset( $_POST['cf7af_smtp_ency_type'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_ency_type'] ) ) : 'none';
		$cf7af_smtp_option['cf7af_smtp_auth'] = isset( $_POST['cf7af_smtp_auth'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_auth'] ) ) : 'no';


		$cf7af_smtp_option['cf7af_smtp_port']	= '25';
		/* Check value from "SMTP port" option */
		if ( isset( $_POST['cf7af_smtp_port'] ) ) {
			$cf7af_smtp_port_raw = sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_port'] ) );
			if ( empty( $cf7af_smtp_port_raw ) || 1 > intval( $cf7af_smtp_port_raw ) || ! preg_match( '/^\d+$/', $cf7af_smtp_port_raw ) ) {
				$cf7af_smtp_option['cf7af_smtp_port'] = '25';
				$cf7af_custom_error                .= ' ' . __( "Please enter a valid port in the 'SMTP Port' field.", 'abandoned-contact-form-7' );
			} else {
				$cf7af_smtp_option['cf7af_smtp_port'] = $cf7af_smtp_port_raw;
			}
		}
		$cf7af_smtp_option['cf7af_smtp_username'] = isset( $_POST['cf7af_smtp_username'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_username'] ) ) : '';
		$cf7af_smtp_option['cf7af_smtp_password'] = isset( $_POST['cf7af_smtp_password'] ) ? sanitize_text_field( wp_unslash( $_POST['cf7af_smtp_password'] ) ) : '';

		/* Update settings in the database */
		if ( empty( $cf7af_custom_error ) ) {
			update_option( 'cf7af_smtp_option', $cf7af_smtp_option );
			$cf7af_message .= __( 'Settings saved.', 'abandoned-contact-form-7' );
		} else {
			$cf7af_custom_error .= ' ' . __( 'Settings are not saved.', 'abandoned-contact-form-7' );
		}

	}
	$cf7af_current = 'smtp-settings';
	if ( isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin settings tab navigation.
		$cf7af_current = sanitize_key( wp_unslash( $_GET['tab'] ) );
	}

	$cf7af_new_tabs = array(
		'smtp-settings'   => __( 'SMTP Settings', 'abandoned-contact-form-7' ),
		'test-mail'  => __( 'Test Mail', 'abandoned-contact-form-7' )
	);
	$cf7af_html = '<h2 class="nav-tab-wrapper">';
	foreach ( $cf7af_new_tabs as $cf7af_new_tab => $cf7af_name ) {
		$cf7af_class  = ( $cf7af_new_tab === $cf7af_current ) ? 'nav-tab-active' : '';
		$cf7af_html  .= '<a class="nav-tab ' . esc_attr( $cf7af_class ) . '" href="?post_type=' . esc_attr( CF7AF_POST_TYPE ) . '&page=cf7af-stmp-setting&tab=' . esc_attr( $cf7af_new_tab ) . '">' . esc_html( $cf7af_name ) . '</a>';
	}
	$cf7af_html .= '</h2>';
	echo $cf7af_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped when building tab markup.

	echo '<div class="wrap">';

	if ( 'test-mail' !== $cf7af_current ) {
	?>

		<h2><?php esc_html_e( 'SMTP Settings', 'abandoned-contact-form-7' ); ?></h2>

		<?php if( !empty( $cf7af_message ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
			<p><strong><?php echo esc_html( $cf7af_message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $cf7af_custom_error ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<p><strong><?php echo esc_html( $cf7af_custom_error ); ?></strong></p>
		</div>
		<?php } ?>

		<form autocomplete="off" id="cf7af_smtp_settings_frm" method="post" action="">

			<table class="form-table tooltip-table">
				<tbody>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-from-email">
							<?php esc_html_e( 'From Email Address', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-from-email-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-from-email"
							name="cf7af_from_email"
							type="email"
							class="regular-text"
							required
							value="<?php echo isset( $cf7af_smtp_option['cf7af_from_email'] ) ? esc_attr( $cf7af_smtp_option['cf7af_from_email'] ) : ''; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-from-name">
							<?php esc_html_e( 'From Name', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-from-name-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-from-name"
							name="cf7af_from_name"
							type="text"
							class="regular-text"
							value="<?php echo isset( $cf7af_smtp_option['cf7af_from_name'] ) ? esc_attr( $cf7af_smtp_option['cf7af_from_name'] ) : ''; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-host">
							<?php esc_html_e( 'SMTP Host', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-host-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-smtp-host"
							name="cf7af_smtp_host"
							type="text"
							class="regular-text"
							required
							value="<?php echo isset( $cf7af_smtp_option['cf7af_smtp_host'] ) ? esc_attr( $cf7af_smtp_option['cf7af_smtp_host'] ) : ''; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-ency-type">
							<?php esc_html_e( 'Type of Encryption', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-ency-type-pointer"></span>
					</th>
					<td>
					<?php
					$cf7af_smtp_option['cf7af_smtp_ency_type'] = isset( $cf7af_smtp_option['cf7af_smtp_ency_type'] ) ? $cf7af_smtp_option['cf7af_smtp_ency_type'] : 'none';
					?>
						<label for="cf7af_smtp_ency_type_1">
							<input id="cf7af_smtp_ency_type_1" name="cf7af_smtp_ency_type" type="radio" value="none"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'none' ); ?> ><?php esc_html_e( 'None', 'abandoned-contact-form-7' ); ?>
						</label>

						<label for="cf7af_smtp_ency_type_2">
							<input id="cf7af_smtp_ency_type_2" name="cf7af_smtp_ency_type" type="radio" value="ssl"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'ssl' ); ?> ><?php esc_html_e( 'SSL/TLS', 'abandoned-contact-form-7' ); ?>
						</label>

						<label for="cf7af_smtp_ency_type_3">
							<input id="cf7af_smtp_ency_type_3" name="cf7af_smtp_ency_type" type="radio" value="tls"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'tls' ); ?> ><?php esc_html_e( 'STARTTLS', 'abandoned-contact-form-7' ); ?>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-port">
							<?php esc_html_e( 'Port', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-port-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-smtp-port"
							name="cf7af_smtp_port"
							type="text"
							class="regular-number"
							required
							value="<?php echo isset( $cf7af_smtp_option['cf7af_smtp_port'] ) ? esc_attr( $cf7af_smtp_option['cf7af_smtp_port'] ) : 25; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-auth">
							<?php esc_html_e( 'SMTP Authentication', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-auth-pointer"></span>
					</th>
					<td>
					<?php
					$cf7af_smtp_option['cf7af_smtp_auth'] = isset( $cf7af_smtp_option['cf7af_smtp_auth'] ) ? $cf7af_smtp_option['cf7af_smtp_auth'] : 'yes';
					?>
						<label for="cf7af_smtp_auth_1">
							<input id="cf7af_smtp_auth_1" name="cf7af_smtp_auth" type="radio" value="no" <?php checked( $cf7af_smtp_option['cf7af_smtp_auth'], 'no' ); ?> ><?php esc_html_e( 'No', 'abandoned-contact-form-7' ); ?>
						</label>

						<label for="cf7af_smtp_auth_2">
							<input id="cf7af_smtp_auth_2" name="cf7af_smtp_auth" type="radio" value="yes" <?php checked( $cf7af_smtp_option['cf7af_smtp_auth'], 'yes' ); ?> ><?php esc_html_e( 'Yes', 'abandoned-contact-form-7' ); ?>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-username">
							<?php esc_html_e( 'SMTP Username', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-username-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-smtp-username"
							name="cf7af_smtp_username"
							type="text"
							class="regular-text"
							required
							value="<?php echo isset( $cf7af_smtp_option['cf7af_smtp_username'] ) ? esc_attr( $cf7af_smtp_option['cf7af_smtp_username'] ) : ''; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-password">
							<?php esc_html_e( 'SMTP Password', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-password-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-smtp-password"
							name="cf7af_smtp_password"
							type="password"
							class="regular-text"
							required
							value="<?php echo isset( $cf7af_smtp_option['cf7af_smtp_password'] ) ? esc_attr( $cf7af_smtp_option['cf7af_smtp_password'] ) : ''; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
					</th>
					<td>
						<input
							name="cf7af_smtp_submit"
							type="submit"
							class="button-primary"
							value="<?php esc_attr_e( 'Save', 'abandoned-contact-form-7' ); ?>"
						/>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), '_smtp_nonce_name' ); ?>
					</td>
				</tr>

				</tbody>
			</table>
		</form>

	<?php } else { ?>

		<?php if( !empty( $cf7af_message ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
			<p><strong><?php echo esc_html( $cf7af_message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if ( ! empty( $cf7af_test_errors ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<?php
			foreach ( $cf7af_test_errors as $cf7af_error_val ) {
				echo '<p><strong>' . esc_html( $cf7af_error_val ) . '</strong></p>';
			}
			?>
		</div>
		<?php } ?>

		<h2><?php esc_html_e( 'Test Mail', 'abandoned-contact-form-7' ); ?></h2>
		<form id="cf7af_smtp_test_frm" method="post" action="">
			<table class="form-table tooltip-table">
				<tbody>

					<tr valign="top">
						<th scope="row" valign="top">
							<label for="cf7af-test-to-email">
								<?php esc_html_e( 'To Email', 'abandoned-contact-form-7' ); ?>
							</label>
							<span class="cf7af-tooltip hide-if-no-js " id="cf7af-test-to-email-pointer"></span>
						</th>
						<td>
							<input
								id="cf7af-test-to-email"
								name="cf7af_test_to_email"
								type="email"
								class="regular-text"
								required
								value=""
							/>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" valign="top">
							<label for="cf7af-test-subject">
								<?php esc_html_e( 'Subject', 'abandoned-contact-form-7' ); ?>
							</label>
							<span class="cf7af-tooltip hide-if-no-js " id="cf7af-test-subject-pointer"></span>
						</th>
						<td>
							<input
								id="cf7af-test-subject"
								name="cf7af_test_subject"
								type="text"
								class="regular-text"
								required
								value=""
							/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" valign="top">
							<label for="cf7af-test-message">
								<?php esc_html_e( 'Message', 'abandoned-contact-form-7' ); ?>
							</label>
							<span class="cf7af-tooltip hide-if-no-js " id="cf7af-test-message-pointer"></span>
						</th>
						<td>
							<textarea
								id="cf7af-test-message"
								name="cf7af_test_message"
								class="regular-text"
								rows="5"
								required
							></textarea>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" valign="top">
						</th>
						<td>
							<input
								name="cf7af_smtp_test_submit"
								type="submit"
								class="button-primary"
								value="<?php esc_attr_e( 'Send Test Email', 'abandoned-contact-form-7' ); ?>"
							/>
							<?php wp_nonce_field( plugin_basename( __FILE__ ), '_smtptest_nonce_name' ); ?>
						</td>
					</tr>

				</tbody>
			</table>
		</form>

	<?php }

	echo '</div>';

	// Localize the script with new data
	$cf7af_translation_array = array(
		'cf7af_from_email' => __( '<h3>From Email Address</h3><p>This email address will be used in the &apos;FROM&apos; field.</p>', 'abandoned-contact-form-7' ),
		'cf7af_from_name' => __( '<h3>From Name</h3><p>This text will be used in the &apos;FROM&apos; field.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_host' => __( '<h3>SMTP Host</h3><p>Enter your SMTP host id.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_ency_type' => __( '<h3>Type of Encryption</h3><p>For most servers SSL/TLS is the recommended option.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_port' => __( '<h3>Port</h3><p>The port to your mail server.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_auth' => __( '<h3>SMTP Authentication</h3><p>This options should always be checked &apos;Yes&apos;.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_username' => __( '<h3>SMTP Username</h3><p>The username to login to your mail server.</p>', 'abandoned-contact-form-7' ),
		'cf7af_smtp_password' => __( '<h3>SMTP Password</h3><p>The password to login to your mail server.</p>', 'abandoned-contact-form-7' ),
		'cf7af_test_to_email' => __( '<h3>To Email</h3><p>Enter the recipient&apos;s email address.</p>', 'abandoned-contact-form-7' ),
		'cf7af_test_subject' => __( '<h3>Subject</h3><p>Enter a subject for your message.</p>', 'abandoned-contact-form-7' ),
		'cf7af_test_message' => __( '<h3>Message</h3><p>Write your email message.</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>