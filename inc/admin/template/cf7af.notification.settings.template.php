<?php
	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$message = $custom_error = ''; 

	$cf7af_mail_notify_option = get_option( 'cf7af_mail_notify_option' );

	if ( isset( $_POST['save_notify'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_notify_nonce_name' ) ) {
			$custom_error .= ' ' . __( 'Nonce check failed.', 'cf7-abandoned-form' ); 
		}

		$cf7af_mail_notify_option['cf7af_notification_time'] = isset( $_POST['cf7af_notification_time'] ) ? sanitize_text_field($_POST['cf7af_notification_time']) : 'cf7af_daily';

		wp_schedule_event( time(), $cf7af_mail_notify_option['cf7af_notification_time'] , 'cf7af_send_notify_event' );

		$cf7af_mail_notify_option['cf7af_email_body'] = isset( $_POST['cf7af_email_body'] ) ?   $_POST['cf7af_email_body'] : '';
		$cf7af_mail_notify_option['cf7af_subject'] = isset( $_POST['cf7af_subject'] ) ?  sanitize_text_field( $_POST['cf7af_subject'] ) : '';

		/* Update settings in the database */
		if ( empty( $custom_error ) ) {
			update_option( 'cf7af_mail_notify_option', $cf7af_mail_notify_option );
			$message .= __( 'Settings saved.', 'cf7-abandoned-form' );
		} else {
			$custom_error .= ' '. __( 'Settings are not saved.', 'cf7-abandoned-form' ); 
		}
	}
	?>

	<div class="wrap">
		<h2><?php _e( 'Mail Notification Settings', 'cf7-abandoned-form' ); ?></h2>
		<p>
			<?php _e( 'Use {email} to insert the email into the mail body.', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {contact_form} to insert the form name into the mail body.', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {link} to insert the page contact link into the mail body.', 'cf7-abandoned-form' ); ?>
		</p>

		<?php if( !empty( $message ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
		<p><strong><?php echo esc_html( $message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $custom_error ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<p><strong><?php echo esc_html( $custom_error ); ?></strong></p>
		</div>
		<?php } ?>

		<form autocomplete="off" id="cf7af_notify_frm" method="post" action="" enctype="multipart/form-data">

			<table class="form-table tooltip-table cf7af-notification-setting">
				<tbody>

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
		'cf7af_subject' => __( '<h3>Subject</h3>' .
			'<p>Please enter the subject for send mail.</p>', 'cf7-abandoned-form' ),
		'cf7af_email_body' => __( '<h3>Email Body </h3>' .
			'<p>It\'s a body content of mail which reflect on sent mail.</p>', 'cf7-abandoned-form' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>