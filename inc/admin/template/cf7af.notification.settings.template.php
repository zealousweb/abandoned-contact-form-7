<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	wp_enqueue_style( CF7AF_ADMIN_STYLE_HANDLE );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$cf7af_message = $cf7af_custom_error = '';

	$cf7af_mail_notify_option = get_option( CF7AF_OPTION_MAIL_NOTIFY );

	if ( isset( $_POST['cf7af_save_notify'] ) || isset( $_POST['save_notify'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'abandoned-contact-form-7' ) );
		}

		if ( ! check_admin_referer( CF7AF_Helpers::NONCE_NOTIFY, CF7AF_Helpers::NONCE_NOTIFY ) ) {
			$cf7af_custom_error .= ' ' . __( 'Nonce check failed.', 'abandoned-contact-form-7' );
		} else {
			$cf7af_mail_notify_option['cf7af_email_body'] = wp_kses_post( CF7AF_Helpers::get_post_field_value( 'cf7af_email_body', 'cf7af_email_body' ) );
			$cf7af_mail_notify_option['cf7af_subject']    = sanitize_text_field( CF7AF_Helpers::get_post_field_value( 'cf7af_subject', 'cf7af_subject' ) );

			update_option( CF7AF_OPTION_MAIL_NOTIFY, $cf7af_mail_notify_option );
			$cf7af_message .= __( 'Settings saved.', 'abandoned-contact-form-7' );
		}
	}
	?>

	<div class="wrap">
		<h2><?php esc_html_e( 'Mail Notification Settings', 'abandoned-contact-form-7' ); ?></h2>
		<p>
			<?php esc_html_e( 'Use {email} to insert the email into the mail body.', 'abandoned-contact-form-7' ); ?><br>
			<?php esc_html_e( 'Use {contact_form} to insert the form name into the mail body.', 'abandoned-contact-form-7' ); ?><br>
			<?php esc_html_e( 'Use {link} to insert the page contact link into the mail body.', 'abandoned-contact-form-7' ); ?>
		</p>

		<?php if( !empty( $cf7af_message ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible">
		<p><strong><?php echo esc_html( $cf7af_message ); ?></strong></p>
		</div>
		<?php } ?>

		<?php if( !empty( $cf7af_custom_error ) ) { ?>
		<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible">
			<p><strong><?php echo esc_html( $cf7af_custom_error ); ?></strong></p>
		</div>
		<?php } ?>

		<form autocomplete="off" id="cf7af_notify_frm" method="post" action="" enctype="multipart/form-data">

			<table class="form-table tooltip-table cf7af-notification-setting">
				<tbody>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-subject">
							<?php esc_html_e( 'Subject', 'abandoned-contact-form-7' ); ?>
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
							<?php esc_html_e( 'Email Body', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-email-body-pointer"></span>
					</th>
					<td>
						<?php
							$cf7af_mail_notify_option['cf7af_email_body'] = isset( $cf7af_mail_notify_option['cf7af_email_body'] ) ?  stripslashes( $cf7af_mail_notify_option['cf7af_email_body'] ) : '';

							$cf7af_editor_settings = array( 'textarea_rows' => '10' );
							wp_editor( $cf7af_mail_notify_option['cf7af_email_body'], 'cf7af_email_body', $cf7af_editor_settings );
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
							name="cf7af_save_notify"
							value="<?php esc_attr_e( 'Save', 'abandoned-contact-form-7' ); ?>"
						/>
						<?php wp_nonce_field( CF7AF_Helpers::NONCE_NOTIFY, CF7AF_Helpers::NONCE_NOTIFY ); ?>
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>

<?php
	// Localize the script with new data
	$cf7af_translation_array = array(
		'cf7af_subject' => __( '<h3>Subject</h3><p>Please enter the subject for send mail.</p>', 'abandoned-contact-form-7' ),
		'cf7af_email_body' => __( '<h3>Email Body </h3><p>It\'s a body content of mail which reflect on sent mail.</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_ADMIN_SCRIPT_HANDLE, 'cf7af_translate_strings', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_ADMIN_SCRIPT_HANDLE );

?>