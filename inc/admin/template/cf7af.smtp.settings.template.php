<?php

	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$message = '';
	$error = array();

	$cf7af_smtp_option = get_option( 'cf7af_smtp_option' );

	if ( isset( $_POST['cf7af_smtp_test_submit'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_smtptest_nonce_name' ) ) {
			$error[] =  __( 'Nonce check failed.', 'cf7-abandoned-form' );
		}
		global $wp_version;
		require_once ABSPATH . WPINC . '/class-phpmailer.php';

		if ( version_compare( $wp_version, '5.5.1', '>=' ) ) {
			// WordPress version is greater than 4.3
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			$mail = new PHPMailer\PHPMailer\PHPMailer();
		}  else {
			$mail = new PHPMailer( true );
		}

		$to_email = sanitize_email( $_POST['cf7af_test_to_email'] );
		$subject = sanitize_text_field( $_POST['cf7af_test_subject'] );
		$body = sanitize_textarea_field( $_POST['cf7af_test_message'] );
		$ret = array();
		//$mail = new PHPMailer( true );

		try {

			$charset       = get_bloginfo( 'charset' );
			$mail->CharSet = $charset;
			$from_name  = $this->cf7af_smtp_opt['cf7af_from_name'];
			$from_email = $this->cf7af_smtp_opt['cf7af_from_email'];

			$mail->IsSMTP();

			// send plain text test email
			$mail->ContentType = 'text/plain';
			$mail->IsHTML( false );

			/* If using smtp auth, set the username & password */
			if ( 'yes' === $this->cf7af_smtp_opt['cf7af_smtp_auth'] ) {
				$mail->SMTPAuth = true;
				$mail->Username = $this->cf7af_smtp_opt['cf7af_smtp_username'];
				$mail->Password = $this->cf7af_smtp_opt['cf7af_smtp_password'];
			}

			/* Set the SMTPSecure value, if set to none, leave this blank */
			if ( 'none' !== $this->cf7af_smtp_opt['cf7af_smtp_ency_type'] ) {
				$mail->SMTPSecure = $this->cf7af_smtp_opt['cf7af_smtp_ency_type'];
			}

			/* PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate. */
			$mail->SMTPAutoTLS = false;

			/* Set the other options */
			$mail->Host = $this->cf7af_smtp_opt['cf7af_smtp_host'];
			$mail->Port = $this->cf7af_smtp_opt['cf7af_smtp_port'];

			$mail->SetFrom( $from_email, $from_name );
			//This should set Return-Path header for servers that are not properly handling it, but needs testing first
			//$mail->Sender		 = $mail->From;
			$mail->Subject = $subject;
			$mail->Body    = $body;
			$mail->AddAddress( $to_email );
			global $debug_msg;
			$debug_msg = '';
			$mail->Debugoutput = function ( $str, $level ) {
				global $debug_msg;
				$debug_msg .= $str.'<br>';
			};
			$mail->SMTPDebug = 1;
			//set reasonable timeout
			$mail->Timeout = 10;

			/* Send mail and return result */
			$mail->Send();
			$mail->ClearAddresses();
			$mail->ClearAllRecipients();
			$success = 1;
		} catch ( Exception $e ) {
			$success = 0;
			$ret['error'] = $mail->ErrorInfo;
		}

		$ret['debug_log'] = $debug_msg;

		if( $success==0 ) {
			$error[] = __( 'Error on send mail.', 'cf7-abandoned-form' );
			$error[] = $ret['error'];
			$error[] = $ret['debug_log'];
		}

		if ( empty( $error ) ) {
			$message .= __( 'Test email was successfully sent. No errors occurred during the process.', 'cf7-abandoned-form' );
		}

	}

	if ( isset( $_POST['cf7af_smtp_submit'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_smtp_nonce_name' ) ) {
			$error .= ' ' . __( 'Nonce check failed.', 'cf7-abandoned-form' );
		}

		if ( isset( $_POST['cf7af_from_email'] ) ) {
			if ( is_email( $_POST['cf7af_from_email'] ) ) {
				$cf7af_smtp_option['cf7af_from_email'] = sanitize_email( $_POST['cf7af_from_email'] );
			} else {
				$error .= ' ' . __( "Please enter a valid email address in the 'From Email Address' field.", 'cf7-abandoned-form' );
			}
		}
		$cf7af_smtp_option['cf7af_from_name'] = sanitize_text_field( $_POST['cf7af_from_name'] );
		$cf7af_smtp_option['cf7af_smtp_host'] = stripslashes( $_POST['cf7af_smtp_host'] );
		$cf7af_smtp_option['cf7af_smtp_ency_type'] = isset( $_POST['cf7af_smtp_ency_type'] ) ? sanitize_text_field($_POST['cf7af_smtp_ency_type']) : 'none';
		$cf7af_smtp_option['cf7af_smtp_auth'] = isset( $_POST['cf7af_smtp_auth'] ) ? sanitize_text_field($_POST['cf7af_smtp_auth']) : 'no';


		$cf7af_smtp_option['cf7af_smtp_port']	= '25';
		/* Check value from "SMTP port" option */
		if ( isset( $_POST['cf7af_smtp_port'] ) ) {
			if ( empty( $_POST['cf7af_smtp_port'] ) || 1 > intval( $_POST['cf7af_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['cf7af_smtp_port'] ) ) ) {
				$cf7af_smtp_option['cf7af_smtp_port']	= '25';
				$error                                   .= ' ' . __( "Please enter a valid port in the 'SMTP Port' field.", 'cf7-abandoned-form' );
			} else {
				$cf7af_smtp_option['cf7af_smtp_port'] = sanitize_text_field( $_POST['cf7af_smtp_port'] );
			}
		}
		$cf7af_smtp_option['cf7af_smtp_username']       = stripslashes( $_POST['cf7af_smtp_username'] );
		$cf7af_smtp_option['cf7af_smtp_password'] = sanitize_text_field($_POST['cf7af_smtp_password']);

		/* Update settings in the database */
		if ( empty( $error ) ) {
			update_option( 'cf7af_smtp_option', $cf7af_smtp_option );
			$message .= __( 'Settings saved.', 'cf7-abandoned-form' );
		} else {
			$error .= ' ' . __( 'Settings are not saved.', 'cf7-abandoned-form' );
		}

	}
	$current = ( ! empty( $_GET['tab'] ) ) ? esc_attr( $_GET['tab'] ) : 'smtp-settings';

	$tabs = array(
		'smtp-settings'   => __( 'SMTP Settings', 'cf7-abandoned-form' ),
		'test-mail'  => __( 'Test Mail', 'cf7-abandoned-form' )
	);
	$html = '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ){
		$class = ( $tab == $current ) ? 'nav-tab-active' : '';
		$html .= '<a class="nav-tab ' . $class . '" href="?post_type='.CF7AF_POST_TYPE.'&page=cf7af-stmp-setting&tab=' . $tab . '">' . $name . '</a>';
	}
	$html .= '</h2>';
	echo $html;

	echo '<div class="wrap">';

	if ( $current != 'test-mail' ) {
	?>

		<h2><?php _e( 'SMTP Settings', 'cf7-abandoned-form' ); ?></h2>

		<?php if( !empty( $message ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
			<p><strong><?php echo esc_html( $message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $error ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<p><strong><?php echo esc_html( $error ); ?></strong></p>
		</div>
		<?php } ?>

		<form autocomplete="off" id="cf7af_smtp_settings_frm" method="post" action="">

			<table class="form-table tooltip-table">
				<tbody>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-from-email">
							<?php _e( 'From Email Address', 'cf7-abandoned-form' ); ?>
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
							<?php _e( 'From Name', 'cf7-abandoned-form' ); ?>
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
							<?php _e( 'SMTP Host', 'cf7-abandoned-form' ); ?>
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
							<?php _e( 'Type of Encryption', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-ency-type-pointer"></span>
					</th>
					<td>
					<?php
					$cf7af_smtp_option['cf7af_smtp_ency_type'] = isset( $cf7af_smtp_option['cf7af_smtp_ency_type'] ) ? $cf7af_smtp_option['cf7af_smtp_ency_type'] : 'none';
					?>
						<label for="cf7af_smtp_ency_type_1">
							<input id="cf7af_smtp_ency_type_1" name="cf7af_smtp_ency_type" type="radio" value="none"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'none' ); ?> ><?php _e( 'None', 'cf7-abandoned-form' ); ?>
						</label>

						<label for="cf7af_smtp_ency_type_2">
							<input id="cf7af_smtp_ency_type_2" name="cf7af_smtp_ency_type" type="radio" value="ssl"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'ssl' ); ?> ><?php _e( 'SSL/TLS', 'cf7-abandoned-form' ); ?>
						</label>

						<label for="cf7af_smtp_ency_type_3">
							<input id="cf7af_smtp_ency_type_3" name="cf7af_smtp_ency_type" type="radio" value="tls"  <?php checked( $cf7af_smtp_option['cf7af_smtp_ency_type'], 'tls' ); ?> ><?php _e( 'STARTTLS', 'cf7-abandoned-form' ); ?>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-port">
							<?php _e( 'Port', 'cf7-abandoned-form' ); ?>
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
							<?php _e( 'SMTP Authentication', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-smtp-auth-pointer"></span>
					</th>
					<td>
					<?php
					$cf7af_smtp_option['cf7af_smtp_auth'] = isset( $cf7af_smtp_option['cf7af_smtp_auth'] ) ? $cf7af_smtp_option['cf7af_smtp_auth'] : 'yes';
					?>
						<label for="cf7af_smtp_auth_1">
							<input id="cf7af_smtp_auth_1" name="cf7af_smtp_auth" type="radio" value="no" <?php checked( $cf7af_smtp_option['cf7af_smtp_auth'], 'no' ); ?> ><?php _e( 'No', 'cf7-abandoned-form' ); ?>
						</label>

						<label for="cf7af_smtp_auth_2">
							<input id="cf7af_smtp_auth_2" name="cf7af_smtp_auth" type="radio" value="yes" <?php checked( $cf7af_smtp_option['cf7af_smtp_auth'], 'yes' ); ?> ><?php _e( 'Yes', 'cf7-abandoned-form' ); ?>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-smtp-username">
							<?php _e( 'SMTP Username', 'cf7-abandoned-form' ); ?>
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
							<?php _e( 'SMTP Password', 'cf7-abandoned-form' ); ?>
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
							value="<?php echo isset( $cf7af_smtp_option['cf7af_smtp_password'] ) ? esc_attr_e( $cf7af_smtp_option['cf7af_smtp_password'] ) : ''; ?>"
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
							value="<?php _e( 'Save', 'cf7-abandoned-form' ); ?>"
						/>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), '_smtp_nonce_name' ); ?>
					</td>
				</tr>

				</tbody>
			</table>
		</form>

	<?php } else { ?>

		<?php if( !empty( $message ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
			<p><strong><?php echo esc_html( $message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $error ) )  { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<?php
			foreach( $error as $key=>$val) {
				echo '<p><strong>'.  ( $val ) .'</strong></p>';
			}
			?>
		</div>
		<?php } ?>

		<h2><?php _e( 'Test Mail', 'cf7-abandoned-form' ); ?></h2>
		<form id="cf7af_smtp_test_frm" method="post" action="">
			<table class="form-table tooltip-table">
				<tbody>

					<tr valign="top">
						<th scope="row" valign="top">
							<label for="cf7af-test-to-email">
								<?php _e( 'To Email', 'cf7-abandoned-form' ); ?>
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
								<?php _e( 'Subject', 'cf7-abandoned-form' ); ?>
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
								<?php _e( 'Message', 'cf7-abandoned-form' ); ?>
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
								value="<?php _e( 'Send Test Email', 'cf7-abandoned-form' ); ?>"
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
	$translation_array = array(
		'cf7af_from_email' => __( '<h3>From Email Address</h3>' .
			'<p>This email address will be used in the &apos;FROM&apos; field.</p>', 'cf7-abandoned-form' ),
		'cf7af_from_name' => __( '<h3>From Name</h3>' .
			'<p>This text will be used in the &apos;FROM&apos; field.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_host' => __( '<h3>SMTP Host</h3>' .
			'<p>Enter your SMTP host id.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_ency_type' => __( '<h3>Type of Encryption</h3>' .
			'<p>For most servers SSL/TLS is the recommended option.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_port' => __( '<h3>Port</h3>' .
			'<p>The port to your mail server.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_auth' => __( '<h3>SMTP Authentication</h3>' .
			'<p>This options should always be checked &apos;Yes&apos;.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_username' => __( '<h3>SMTP Username</h3>' .
			'<p>The username to login to your mail server.</p>', 'cf7-abandoned-form' ),
		'cf7af_smtp_password' => __( '<h3>SMTP Password</h3>' .
			'<p>The password to login to your mail server.</p>', 'cf7-abandoned-form' ),
		'cf7af_test_to_email' => __( '<h3>To Email</h3>' .
			'<p>Enter the recipient&apos;s email address.</p>', 'cf7-abandoned-form' ),
		'cf7af_test_subject' => __( '<h3>Subject</h3>' .
			'<p>Enter a subject for your message.</p>', 'cf7-abandoned-form' ),
		'cf7af_test_message' => __( '<h3>Message</h3>' .
			'<p>Write your email message.</p>', 'cf7-abandoned-form' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>