<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$cf7af_abandoned_id = CF7AF_Helpers::get_send_mail_abandoned_id();
	if ( ! $cf7af_abandoned_id ) {
		wp_die( esc_html__( 'Invalid request.', 'abandoned-contact-form-7' ) );
	}

	wp_enqueue_style( CF7AF_ADMIN_STYLE_HANDLE );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

	$cf7af_abandoned_email_address = !empty( get_post_meta( $cf7af_abandoned_id , 'cf7af_email', true ) )
				? get_post_meta( $cf7af_abandoned_id , 'cf7af_email', true )
				: '';

	$cf7af_abandoned_from_name = get_bloginfo();
	$cf7af_form_id = CF7AF_Helpers::get_abandoned_entry_form_id( $cf7af_abandoned_id );
	$cf7af_page_url = get_post_meta( $cf7af_abandoned_id , 'cf7af_page_url', true );

	$cf7af_abandoned_from_email_address = get_bloginfo( 'admin_email' );

	// Set from email if set by contact form 7
	$cf7af_contact_form = WPCF7_ContactForm::get_instance( $cf7af_form_id );
	if( $cf7af_contact_form ) {
		$cf7af_form_properties = $cf7af_contact_form->get_properties();
		$cf7af_recipient = $cf7af_form_properties['mail']['recipient'];
		if( filter_var( $cf7af_recipient , FILTER_VALIDATE_EMAIL ) ) {
			$cf7af_abandoned_from_email_address = $cf7af_recipient;
		}
	}

	$cf7af_mail_notify_opt = get_option( CF7AF_OPTION_MAIL_NOTIFY );

	$cf7af_message = $cf7af_custom_error = ''; 
	if ( isset( $_POST['cf7af_send_mail'] ) || isset( $_POST['send_mail'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'abandoned-contact-form-7' ) );
		}

		if ( ! check_admin_referer( CF7AF_Helpers::NONCE_SEND_MAIL, CF7AF_Helpers::NONCE_SEND_MAIL ) ) {
			$cf7af_custom_error .= ' ' . __( 'Nonce check failed.', 'abandoned-contact-form-7' );
		} else {

			$cf7af_to = sanitize_email( CF7AF_Helpers::get_post_field_value( 'cf7af_abandoned_email_address', 'abandoned_email_address' ) );
			$cf7af_mail_subject = sanitize_text_field( CF7AF_Helpers::get_post_field_value( 'cf7af_abandoned_subject', 'abandoned_subject' ) );
			$cf7af_body = wp_kses_post( CF7AF_Helpers::get_post_field_value( 'cf7af_abandoned_email_body', 'abandoned_email_body' ) );
			$cf7af_body = nl2br( $cf7af_body );
			$cf7af_from_name = sanitize_text_field( CF7AF_Helpers::get_post_field_value( 'cf7af_abandoned_from_name', 'abandoned_from_name' ) );
			$cf7af_from_email_address = sanitize_email( CF7AF_Helpers::get_post_field_value( 'cf7af_abandoned_from_email_address', 'abandoned_from_email_address' ) );

			$cf7af_form_title = get_the_title( $cf7af_form_id );
			$cf7af_body = str_replace("{email}", $cf7af_to, $cf7af_body);
			$cf7af_body = str_replace("{contact_form}", $cf7af_form_title, $cf7af_body);
			
			if ( '' !== $cf7af_page_url ) {
				$cf7af_recover_url = CF7AF_Helpers::build_recover_url( $cf7af_page_url, $cf7af_abandoned_id );
				$cf7af_body        = str_replace( '{link}', $cf7af_recover_url, $cf7af_body );
			}
			else {
				$cf7af_body = str_replace("{link}", '', $cf7af_body);
			}

			$cf7af_headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$cf7af_headers[] = 'From: '.$cf7af_from_name.' <'.$cf7af_from_email_address.'>';
			$cf7af_wp_sent = wp_mail( $cf7af_to, $cf7af_mail_subject, $cf7af_body, $cf7af_headers );

			if( !$cf7af_wp_sent ) {
				$cf7af_number_fail_count = (int) CF7AF_Helpers::get_abandoned_entry_meta( $cf7af_abandoned_id, 'number_fail_count' );
				$cf7af_number_fail_count++;
				CF7AF_Helpers::update_abandoned_entry_meta( $cf7af_abandoned_id, 'number_fail_count', $cf7af_number_fail_count );
				$cf7af_custom_error .= ' ' . __( 'Error on Send Mail.', 'abandoned-contact-form-7' ); 
			}
			
		}

		/* Update settings in the database */
		if ( empty( $cf7af_custom_error ) ) {
			$cf7af_number_sentmail = (int) CF7AF_Helpers::get_abandoned_entry_meta( $cf7af_abandoned_id, 'number_sentmail' );
			$cf7af_number_sentmail++;
			CF7AF_Helpers::update_abandoned_entry_meta( $cf7af_abandoned_id, 'number_sentmail', $cf7af_number_sentmail );

			$cf7af_message .= __( 'Send Mail Suceessfully to Abandoned User.', 'abandoned-contact-form-7' );
		} else {
			$cf7af_custom_error .= ' ' . __( 'Mail has not send.', 'abandoned-contact-form-7' ); 
		}
	}

	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Send Mail to Abandoned User Entry', 'abandoned-contact-form-7' );  ?>
		<?php echo ' <a class="cf7af-entry-link" href="' . esc_url( get_edit_post_link( $cf7af_abandoned_id ) ) . '" target="_blank">#' . esc_html( $cf7af_abandoned_id ) . '</a>'; ?>
		</h2>

		<p>
			<?php esc_html_e( 'Use {email} to insert the email into the mail body.', 'abandoned-contact-form-7' ); ?><br>
			<?php esc_html_e( 'Use {contact_form} to insert the form name into the mail body.', 'abandoned-contact-form-7' ); ?><br>
			<?php esc_html_e( 'Use {link} to insert the page contact link into the mail body.', 'abandoned-contact-form-7' ); ?>
		</p>

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

		<form autocomplete="off" id="cf7af_send_mail_frm" method="post" action="">

			<table class="form-table tooltip-table">
				<tbody>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-abandoned-email-address">
							<?php esc_html_e( 'User Email Address (To)', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-email-address-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-abandoned-email-address"
							name="cf7af_abandoned_email_address"
							type="email"
							class="regular-text"
							required
							readonly
							value="<?php echo esc_attr( $cf7af_abandoned_email_address ); ?>" required
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-abandoned-from-name">
							<?php esc_html_e( 'From Name', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-from-name-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-abandoned-from-name"
							name="cf7af_abandoned_from_name"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $cf7af_abandoned_from_name ); ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-abandoned-from-email-address">
							<?php esc_html_e( 'From Email Address', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-from-email-address-pointer"></span>
					</th>
					<td>
						<input
							id="cf7af-abandoned-from-email-address"
							name="cf7af_abandoned_from_email_address"
							type="text"
							class="regular-text"
							required
							value="<?php echo esc_attr( $cf7af_abandoned_from_email_address ); ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-abandoned-subject">
							<?php esc_html_e( 'Subject', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-subject-pointer"></span>
					</th>
					<td>
						<?php
							$cf7af_abandoned_subject = isset( $cf7af_mail_notify_opt['cf7af_subject'] ) ? $cf7af_mail_notify_opt['cf7af_subject'] : '' ;
						?>
						<input
							id="cf7af-abandoned-subject"
							name="cf7af_abandoned_subject"
							type="text"
							class="regular-text"
							required
							value="<?php echo esc_attr( $cf7af_abandoned_subject ); ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="cf7af-abandoned-email-body">
							<?php esc_html_e( 'Email Body', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-email-body-pointer"></span>
					</th>
					<td>
						<?php
						$cf7af_content         = isset( $cf7af_mail_notify_opt['cf7af_email_body'] ) ? stripslashes( $cf7af_mail_notify_opt['cf7af_email_body'] ) : '';
						$cf7af_editor_settings = array(
							'textarea_rows' => '10',
							'media_buttons' => true,
						);
						wp_editor( $cf7af_content, 'cf7af_abandoned_email_body', $cf7af_editor_settings );
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top"></th>
					<td>
						<input type="submit" name="cf7af_send_mail" class="button button-primary" value="<?php esc_attr_e( 'Send', 'abandoned-contact-form-7' ); ?>" />
						<?php wp_nonce_field( CF7AF_Helpers::NONCE_SEND_MAIL, CF7AF_Helpers::NONCE_SEND_MAIL ); ?>
					</td>
				</tr>

				<tbody>
			</table>
		</form>
	</div>

<?php

	// Localize the script with new data
	$cf7af_translation_array = array(
		'cf7af_abandoned_email_address'      => __( '<h3>User Email Address (To)</h3><p>This is an Abandoned user&apos;s email ID which will receive the email.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_from_name'          => __( '<h3>From Name</h3><p>This is a default  &apos;Name&apos; which is get from website general settings but if you use SMTP settings then From Name used from SMTP settings page.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_from_email_address' => __( '<h3>From Email Address</h3><p>This is a default &apos;Email Address&apos; which is get from website general settings but if you use SMTP settings then From Email used from SMTP settings page.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_subject'            => __( '<h3>Subject</h3><p>This is the subject which is used in email.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_email_body'         => __( '<h3>Email Body</h3><p>This is an email body content which are reflect on email body.</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_ADMIN_SCRIPT_HANDLE, 'cf7af_translate_strings', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_ADMIN_SCRIPT_HANDLE );

?>