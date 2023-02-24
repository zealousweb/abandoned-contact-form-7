<?php
	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$message = $error = '';

	$cf7af_mail_notify_option = get_option( 'cf7af_mail_notify_option' );

	if ( isset( $_POST['save_notify'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_notify_nonce_name' ) ) {
			$error .= ' ' . __( 'Nonce check failed.', 'cf7-abandoned-form' );
		}
<<<<<<< HEAD
		wp_clear_scheduled_hook( 'cf7af_send_notify_event' );

		$cf7af_mail_notify_option['cf7af_mailer_type'] = isset( $_POST['cf7af_mailer_type'] ) ? sanitize_text_field($_POST['cf7af_mailer_type']) : 'none';
		$cf7af_mail_notify_option['cf7af_nums_email'] = isset( $_POST['cf7af_nums_email'] ) ? sanitize_text_field($_POST['cf7af_nums_email']) : '1';

		if( $cf7af_mail_notify_option['cf7af_nums_email'] < 1 || $cf7af_mail_notify_option['cf7af_nums_email'] >= 6 ) {
			$error .= __( 'Please enter the valid number.', 'cf7-abandoned-form' );
		}
=======
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4

		$cf7af_mail_notify_option['cf7af_notification_time'] = isset( $_POST['cf7af_notification_time'] ) ? sanitize_text_field($_POST['cf7af_notification_time']) : 'cf7af_daily';

		wp_schedule_event( time(), $cf7af_mail_notify_option['cf7af_notification_time'] , 'cf7af_send_notify_event' );

<<<<<<< HEAD
		$cf7af_mail_notify_option['cf7af_email_body'] = isset( $_POST['cf7af_email_body'] ) ?  sanitize_textarea_field( $_POST['cf7af_email_body'] ) : '';
=======
		$cf7af_mail_notify_option['cf7af_email_body'] = isset( $_POST['cf7af_email_body'] ) ?   $_POST['cf7af_email_body'] : '';
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		$cf7af_mail_notify_option['cf7af_subject'] = isset( $_POST['cf7af_subject'] ) ?  sanitize_text_field( $_POST['cf7af_subject'] ) : '';

		/* Update settings in the database */
		if ( empty( $error ) ) {
			update_option( 'cf7af_mail_notify_option', $cf7af_mail_notify_option );
			$message .= __( 'Settings saved.', 'cf7-abandoned-form' );
<<<<<<< HEAD

			if( $cf7af_mail_notify_option['cf7af_mailer_type'] == 'smtp' ) {
				$message .= __( ' To change SMTP settings <a href=" '. admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-stmp-setting' ) .' " >Click Here </a>', 'cf7-abandoned-form' );
			}

=======
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		} else {
			$error .= ' '. __( 'Settings are not saved.', 'cf7-abandoned-form' );
		}
	}
	?>

	<div class="wrap">
		<h2><?php _e( 'Mail Notification Settings', 'cf7-abandoned-form' ); ?></h2>
		<p>
<<<<<<< HEAD
			<?php _e( 'Use {email} to insert the email into the mail body', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {contact_form} to insert the form name into the mail body', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {link} to insert the page contact link into the mail body', 'cf7-abandoned-form' ); ?>
=======
			<?php _e( 'Use {email} to insert the email into the mail body.', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {contact_form} to insert the form name into the mail body.', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {link} to insert the page contact link into the mail body.', 'cf7-abandoned-form' ); ?>
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		</p>

		<?php if( !empty( $message ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
			<p><strong><?php echo $message; ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $error ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<p><strong><?php echo esc_html( $error ); ?></strong></p>
		</div>
		<?php } ?>

		<form autocomplete="off" id="cf7af_notify_frm" method="post" action="" enctype="multipart/form-data">

			<table class="form-table tooltip-table cf7af-notification-setting">
				<tbody>
<<<<<<< HEAD
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-mailer-type">
							<?php _e( 'Mailer Type', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-mailer-type-pointer"></span>
					</th>
					<td>
						<label for="cf7af_mailer_type_1">
							<input id="cf7af_mailer_type_1" name="cf7af_mailer_type" type="radio" value="none"  <?php checked( $cf7af_mail_notify_option['cf7af_mailer_type'], 'none' ); ?> ><?php _e( 'Default (PHP Mailer)', 'cf7-abandoned-form' ); ?>
						</label>
						<label for="cf7af_mailer_type_2">
							<input id="cf7af_mailer_type_2" name="cf7af_mailer_type" type="radio" value="smtp"  <?php checked( $cf7af_mail_notify_option['cf7af_mailer_type'], 'smtp' ); ?> ><?php _e( 'SMTP', 'cf7-abandoned-form' ); ?>
						</label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-notification-time">
							<?php _e( 'Schedule Notification Time', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-notification-time-pointer"></span>
					</th>
					<td>
						<?php
						$cf7af_mail_notify_option['cf7af_notification_time'] = isset( $cf7af_mail_notify_option['cf7af_notification_time'] ) ? $cf7af_mail_notify_option['cf7af_notification_time'] : '';
						?>
						<select name="cf7af_notification_time" id="cf7af-notification-time" required>
							<option value=""><?php _e( 'Select Time', 'cf7-abandoned-form' ); ?></option>
							<option value="cf7af_hourly" <?php selected( $cf7af_mail_notify_option['cf7af_notification_time'], 'cf7af_hourly' ); ?> ><?php _e( 'Hourly', 'cf7-abandoned-form' ); ?></option>
							<option value="cf7af_daily" <?php selected( $cf7af_mail_notify_option['cf7af_notification_time'], 'cf7af_daily' ); ?>><?php _e( 'Daily', 'cf7-abandoned-form' ); ?></option>
							<option value="cf7af_weekly" <?php selected( $cf7af_mail_notify_option['cf7af_notification_time'], 'cf7af_weekly' ); ?>><?php _e( 'Weekly', 'cf7-abandoned-form' ); ?></option>
							<option value="cf7af_monthly" <?php selected( $cf7af_mail_notify_option['cf7af_notification_time'], 'cf7af_monthly' ); ?>><?php _e( 'Monthly', 'cf7-abandoned-form' ); ?></option>
						</select>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-nums-email">
							<?php _e( 'Number of Email Notification', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-nums-email-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-nums-email"
							name="cf7af_nums_email"
							type="number"
							min="1"
							max="5"
							class="regular-number"
							required
							value="<?php echo isset( $cf7af_mail_notify_option['cf7af_nums_email'] ) ? esc_attr( $cf7af_mail_notify_option['cf7af_nums_email'] ) : 1 ; ?>"
						/>
					</td>
				</tr>
=======
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-subject">
							<?php _e( 'Subject', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-subject-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-subject"
							name="cf7af_subject"
							type="text"
							class="regular-text"
							required
							value="<?php echo isset( $cf7af_mail_notify_option['cf7af_subject'] ) ? esc_attr( $cf7af_mail_notify_option['cf7af_subject'] ) : '' ; ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-email-body">
							<?php _e( 'Email Body', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-email-body-pointer"></span>
					</th>
					<td>
						<?php
							$cf7af_mail_notify_option['cf7af_email_body'] = isset( $cf7af_mail_notify_option['cf7af_email_body'] ) ?  stripslashes( $cf7af_mail_notify_option['cf7af_email_body'] ) : '';

							wp_editor( $cf7af_mail_notify_option['cf7af_email_body'], 'cf7af_email_body', $settings = array('textarea_rows'=> '10') );
						?>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
					</th>
					<td>
						<input
							type="submit"
							class="button-primary"
							name="save_notify"
							value="<?php _e( 'Save', 'cf7-abandoned-form' ); ?>"
						/>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), '_notify_nonce_name' ); ?>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>

<?php
	// Localize the script with new data
	$translation_array = array(
<<<<<<< HEAD
		'cf7af_mailer_type' => __( '<h3>Mailer Type</h3>' .
			'<p>You can change mailer type for emails to be sent.</p>', 'cf7-abandoned-form' ),
		'cf7af_notification_time' => __( '<h3>Schedule Notification Time</h3>' .
			'<p>Please select the schedule notification time for send mail.</p>', 'cf7-abandoned-form' ),
		'cf7af_nums_email' => __( '<h3>Number of Email Notification</h3>' .
			'<p>Please set the number of email notification to the abandoned user.</p>', 'cf7-abandoned-form' ),
=======
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		'cf7af_subject' => __( '<h3>Subject</h3>' .
			'<p>Please enter the subject for send mail.</p>', 'cf7-abandoned-form' ),
		'cf7af_email_body' => __( '<h3>Email Body </h3>' .
			'<p>It\'s a body content of mail which reflect on sent mail.</p>', 'cf7-abandoned-form' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>