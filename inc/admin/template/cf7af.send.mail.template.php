<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$cf7af_abandoned_id = CF7AF_Helpers::get_send_mail_abandoned_id();
	if ( ! $cf7af_abandoned_id ) {
		wp_die( esc_html__( 'Invalid request.', 'abandoned-contact-form-7' ) );
	}

	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
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

	$cf7af_mail_notify_opt =  get_option( 'cf7af_mail_notify_option' );

	$cf7af_message = $cf7af_custom_error = ''; 
	if ( isset( $_POST['send_mail'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_send_mail_nonce_name' ) ) {
			$cf7af_custom_error .= ' ' . __( 'Nonce check failed.', 'abandoned-contact-form-7' ); 
		} else {

			$cf7af_to = isset( $_POST['abandoned_email_address'] ) ? sanitize_email( wp_unslash( $_POST['abandoned_email_address'] ) ) : '';
			$cf7af_mail_subject = isset( $_POST['abandoned_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['abandoned_subject'] ) ) : '';
			$cf7af_body = isset( $_POST['abandoned_email_body'] ) ? wp_kses_post( wp_unslash( $_POST['abandoned_email_body'] ) ) : '';
			$cf7af_body = nl2br( $cf7af_body );
			$cf7af_from_name = isset( $_POST['abandoned_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['abandoned_from_name'] ) ) : '';
			$cf7af_from_email_address = isset( $_POST['abandoned_from_email_address'] ) ? sanitize_email( wp_unslash( $_POST['abandoned_from_email_address'] ) ) : '';

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
				$cf7af_number_fail_count =  get_post_meta( $cf7af_abandoned_id, 'number_fail_count', true );
				$cf7af_number_fail_count = $cf7af_number_fail_count + 1;
				update_post_meta( $cf7af_abandoned_id, 'number_fail_count', $cf7af_number_fail_count );
				$cf7af_custom_error .= ' ' . __( 'Error on Send Mail.', 'abandoned-contact-form-7' ); 
			}
			
		}

		/* Update settings in the database */
		if ( empty( $cf7af_custom_error ) ) {
			$cf7af_number_sentmail =  get_post_meta( $cf7af_abandoned_id, 'number_sentmail', true );
			$cf7af_number_sentmail = $cf7af_number_sentmail + 1;
			update_post_meta( $cf7af_abandoned_id, 'number_sentmail', $cf7af_number_sentmail );

			$cf7af_message .= __( 'Send Mail Suceessfully to Abandoned User.', 'abandoned-contact-form-7' );
		} else {
			$cf7af_custom_error .= ' ' . __( 'Mail has not send.', 'abandoned-contact-form-7' ); 
		}
	}

	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Send Mail to Abandoned User Entry', 'abandoned-contact-form-7' );  ?>
		<?php echo ' <a href="' . esc_url( get_edit_post_link( $cf7af_abandoned_id ) ) . '" target="_blank" style="text-decoration:none;">#' .esc_html($cf7af_abandoned_id) . '</a>';  ?>
		</h2>

		<p>
			<?php 
			$cf7af_total = get_option('cf7af_total');
			$cf7af_key = array_search ($cf7af_abandoned_id, $cf7af_total);
			if($cf7af_key >= 10)
			{

			}
			else
			{
				?>
				<?php esc_html_e( 'Use {email} to insert the email into the mail body.', 'abandoned-contact-form-7' ); ?><br>
				<?php esc_html_e( 'Use {contact_form} to insert the form name into the mail body.', 'abandoned-contact-form-7' ); ?><br>
				<?php esc_html_e( 'Use {link} to insert the page contact link into the mail body.', 'abandoned-contact-form-7' ); ?>
				<?php
			}
			?>
			
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
						<label for="abandoned-email-address">
							<?php esc_html_e( 'User Email Address (To)', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-email-address-pointer"></span>
					</th>
					<td>
						<input
							id="abandoned-email-address"
							name="abandoned_email_address"
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
						<label for="abandoned-from-name">
							<?php esc_html_e( 'From Name', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-from-name-pointer"></span>
					</th>
					<td>
						<input
							id="abandoned-from-name"
							name="abandoned_from_name"
							type="text"
							class="regular-text"
							value="<?php echo esc_attr( $cf7af_abandoned_from_name ); ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-from-email-address">
							<?php esc_html_e( 'From Email Address', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-from-email-address-pointer"></span>
					</th>
					<td>
						<input
							id="abandoned-from-email-address"
							name="abandoned_from_email_address"
							type="text"
							class="regular-text"
							required
							value="<?php echo esc_attr( $cf7af_abandoned_from_email_address ); ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-subject">
							<?php esc_html_e( 'Subject', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-subject-pointer"></span>
					</th>
					<td>
						<?php
							$cf7af_abandoned_subject = isset( $cf7af_mail_notify_opt['cf7af_subject'] ) ? $cf7af_mail_notify_opt['cf7af_subject'] : '' ;
						?>
						<input
							id="abandoned-subject"
							name="abandoned_subject"
							type="text"
							class="regular-text"
							required
							value="<?php echo esc_attr( $cf7af_abandoned_subject ); ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-email-body">
							<?php esc_html_e( 'Email Body', 'abandoned-contact-form-7' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-email-body-pointer"></span>
					</th>
					<td>
						<?php
						$cf7af_total = get_option('cf7af_total');
						$cf7af_key = array_search ($cf7af_abandoned_id, $cf7af_total);
						if($cf7af_key >= 10)
						{
							echo '<table><tbody>';
							//echo'<tr class="inside-field"><th scope="row"><img src="'.CF7AF_URL.'/assets/images/editor_disable.png"></th></tr>';
							echo'<tr class="inside-field"><th scope="row">You are using Free Abandoned Contact Form 7 - no license needed. Enjoy! 🙂</th></tr>';
							echo'<tr class="inside-field"><th scope="row"><a href="https://store.zealousweb.com/abandoned-contact-form-7-pro" target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
						echo '</tbody></table>';
						$cf7af_content = isset( $cf7af_mail_notify_opt['cf7af_email_body'] ) ? stripslashes($cf7af_mail_notify_opt['cf7af_email_body']) : '' ;
						 echo '<input type="hidden" name="abandoned_email_body" value="' . esc_attr( $cf7af_content ) . '">';
					}
						else
						{
							if($cf7af_total)
							$cf7af_content = isset( $cf7af_mail_notify_opt['cf7af_email_body'] ) ? stripslashes($cf7af_mail_notify_opt['cf7af_email_body']) : '' ;

							$cf7af_editor_settings = array( 'textarea_rows' => '10', 'media_buttons' => true );
							wp_editor( $cf7af_content, 'abandoned_email_body', $cf7af_editor_settings );
						}
						
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top"></th>
					<td>
						<input type="submit" name="send_mail" class="button button-primary" value="<?php esc_attr_e( 'Send', 'abandoned-contact-form-7' ); ?>" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), '_send_mail_nonce_name' ); ?>
					</td>
				</tr>

				<tbody>
			</table>
		</form>
	</div>

<?php

	// Localize the script with new data
	$cf7af_translation_array = array(
		'abandoned_email_address' => __( '<h3>User Email Address (To)</h3><p>This is an Abandoned user&apos;s email ID which will receive the email.</p>', 'abandoned-contact-form-7' ),
		'abandoned_from_name' => __( '<h3>From Name</h3><p>This is a default  &apos;Name&apos; which is get from website general settings but if you use SMTP settings then From Name used from SMTP settings page.</p>', 'abandoned-contact-form-7' ),
		'abandoned_from_email_address' => __( '<h3>From Email Address</h3><p>This is a default &apos;Email Address&apos; which is get from website general settings but if you use SMTP settings then From Email used from SMTP settings page.</p>', 'abandoned-contact-form-7' ),
		'abandoned_subject' => __( '<h3>Subject</h3><p>This is the subject which is used in email.</p>', 'abandoned-contact-form-7' ),
		'abandoned_email_body' => __( '<h3>Email Body</h3><p>This is an email body content which are reflect on email body.</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>