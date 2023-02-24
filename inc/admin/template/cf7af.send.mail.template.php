<?php

	if( !isset( $_GET['abandoned_id'] ) ) return;

	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );

<<<<<<< HEAD
	$abandoned_id = $_GET['abandoned_id'];
=======
	$abandoned_id = sanitize_text_field($_GET['abandoned_id']);
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4

	$abandoned_email_address = !empty( get_post_meta( $abandoned_id , 'cf7af_email', true ) )
				? get_post_meta( $abandoned_id , 'cf7af_email', true )
				: '';

	$abandoned_from_name = get_bloginfo();
	$cf7af_form_id = get_post_meta( $abandoned_id , 'cf7af_form_id', true );
	$cf7af_page_url = get_post_meta( $abandoned_id , 'cf7af_page_url', true );

	$abandoned_from_email_address = get_bloginfo( 'admin_email' );

	// Set from email if set by contact form 7
	$contact_form = WPCF7_ContactForm::get_instance( $cf7af_form_id );
	if( $contact_form ) {
		$cf7_property = $contact_form->get_properties();
		$recipient = $cf7_property['mail']['recipient'];
		if( filter_var( $recipient , FILTER_VALIDATE_EMAIL ) ) {
			$abandoned_from_email_address = $recipient;
		}
	}

	$cf7af_mail_notify_opt =  get_option( 'cf7af_mail_notify_option' );

	$message = $error = '';
	if ( isset( $_POST['send_mail'] ) ) {
		// check nounce
		if ( ! check_admin_referer( plugin_basename( __FILE__ ), '_send_mail_nonce_name' ) ) {
			$error .= ' ' . __( 'Nonce check failed.', 'cf7-abandoned-form' );
		} else {

<<<<<<< HEAD
			$cf7af_smtp_option =  get_option( 'cf7af_smtp_option' );
			if( $cf7af_mail_notify_opt['cf7af_mailer_type'] == 'smtp' && empty( $cf7af_smtp_option ) ) {
				$error .= ' ' . __( 'Please add SMTP details first.', 'cf7-abandoned-form' );
			} else {
				$to = sanitize_email( $_POST['abandoned_email_address'] );
				$subject = sanitize_text_field( $_POST['abandoned_subject'] );
				$body = stripslashes( nl2br( $_POST['abandoned_email_body'] ) ) ;
				$from_name = sanitize_text_field( $_POST['abandoned_from_name'] );
				$from_email_address = sanitize_email( $_POST['abandoned_from_email_address'] );

				$form_title = get_the_title( $cf7af_form_id );
				$body = str_replace("{email}", $to, $body);
				$body = str_replace("{contact_form}", $form_title, $body);

				if( $cf7af_page_url != '' ) {
					if( strpos($cf7af_page_url, 'recover=') !== false ) {
						$body = str_replace("{link}", $cf7af_page_url , $body);
					} else {
						if( strpos($cf7af_page_url, '/?') !== false ) {
							$body = str_replace("{link}", $cf7af_page_url.'&recover='.$abandoned_id, $body);
						} else {
							$body = str_replace("{link}", $cf7af_page_url.'?recover='.$abandoned_id, $body);
						}
					}
				}
				else {
					$body = str_replace("{link}", '', $body);
				}

				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				$headers[] = 'From: '.$from_name.' <'.$from_email_address.'>';
				$wp_sent = wp_mail( $to, $subject, $body, $headers );

				if( !$wp_sent ) {
					$number_fail_count =  get_post_meta( $abandoned_id, 'number_fail_count', true );
					$number_fail_count = $number_fail_count + 1;
					update_post_meta( $abandoned_id, 'number_fail_count', $number_fail_count );
					$error .= ' ' . __( 'Error on Send Mail.', 'cf7-abandoned-form' );
				}
			}
=======
			$to = sanitize_email( $_POST['abandoned_email_address'] );
			$subject = sanitize_text_field( $_POST['abandoned_subject'] );
			$body = stripslashes( nl2br( $_POST['abandoned_email_body'] ) );
			$from_name = sanitize_text_field( $_POST['abandoned_from_name'] );
			$from_email_address = sanitize_email( $_POST['abandoned_from_email_address'] );

			$form_title = get_the_title( $cf7af_form_id );
			$body = str_replace("{email}", $to, $body);
			$body = str_replace("{contact_form}", $form_title, $body);
			
			if( $cf7af_page_url != '' ) {
				if( strpos($cf7af_page_url, 'recover=') !== false ) {
					$body = str_replace("{link}", $cf7af_page_url , $body);
				} else {
					if( strpos($cf7af_page_url, '/?') !== false ) {
						$body = str_replace("{link}", $cf7af_page_url.'&recover='.$abandoned_id, $body);
					} else {
						$body = str_replace("{link}", $cf7af_page_url.'?recover='.$abandoned_id, $body);
					}
				}
			}
			else {
				$body = str_replace("{link}", '', $body);
			}

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$headers[] = 'From: '.$from_name.' <'.$from_email_address.'>';
			$wp_sent = wp_mail( $to, $subject, $body, $headers );

			if( !$wp_sent ) {
				$number_fail_count =  get_post_meta( $abandoned_id, 'number_fail_count', true );
				$number_fail_count = $number_fail_count + 1;
				update_post_meta( $abandoned_id, 'number_fail_count', $number_fail_count );
				$error .= ' ' . __( 'Error on Send Mail.', 'cf7-abandoned-form' );
			}
			
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		}

		/* Update settings in the database */
		if ( empty( $error ) ) {
			$number_sentmail =  get_post_meta( $abandoned_id, 'number_sentmail', true );
			$number_sentmail = $number_sentmail + 1;
			update_post_meta( $abandoned_id, 'number_sentmail', $number_sentmail );

			$message .= __( 'Send Mail Suceessfully to Abandoned User.', 'cf7-abandoned-form' );
		} else {
			$error .= ' ' . __( 'Mail has not send.', 'cf7-abandoned-form' );
		}
	}

	?>
	<div class="wrap">
		<h2><?php _e( 'Send Mail to Abandoned User Entry', 'cf7-abandoned-form' );  ?>
			<?php echo ' <a href="'.get_edit_post_link( $abandoned_id ).'" target="_blank" style="text-decoration:none;">#'. $abandoned_id. '</a>';  ?>
		</h2>
<<<<<<< HEAD
		<p>
			<?php _e( 'Use {email} to insert the email into the mail body', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {contact_form} to insert the form name into the mail body', 'cf7-abandoned-form' ); ?><br>
			<?php _e( 'Use {link} to insert the page contact link into the mail body', 'cf7-abandoned-form' ); ?>
=======

		<p>
			<?php 
			$cf7af_total = get_option('cf7af_total');
			$key = array_search ($abandoned_id, $cf7af_total);
			if($key >= 10)
			{

			}
			else
			{
				?>
				<?php _e( 'Use {email} to insert the email into the mail body.', 'cf7-abandoned-form' ); ?><br>
				<?php _e( 'Use {contact_form} to insert the form name into the mail body.', 'cf7-abandoned-form' ); ?><br>
				<?php _e( 'Use {link} to insert the page contact link into the mail body.', 'cf7-abandoned-form' ); ?>
				<?php
			}
			?>
			
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		</p>

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

		<form autocomplete="off" id="cf7af_send_mail_frm" method="post" action="">

			<table class="form-table tooltip-table">
				<tbody>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-email-address">
							<?php _e( 'User Email Address (To)', 'cf7-abandoned-form' ); ?>
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
							value="<?php esc_attr_e( $abandoned_email_address ); ?>" required
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-from-name">
							<?php _e( 'From Name', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-from-name-pointer"></span>
					</th>
					<td>
						<input
							id="abandoned-from-name"
							name="abandoned_from_name"
							type="text"
							class="regular-text"
							value="<?php esc_attr_e( $abandoned_from_name ); ?>"
						/>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-from-email-address">
							<?php _e( 'From Email Address', 'cf7-abandoned-form' ); ?>
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
							value="<?php esc_attr_e( $abandoned_from_email_address ); ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-subject">
							<?php _e( 'Subject', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-subject-pointer"></span>
					</th>
					<td>
						<?php
							$abandoned_subject = isset( $cf7af_mail_notify_opt['cf7af_subject'] ) ? $cf7af_mail_notify_opt['cf7af_subject'] : '' ;
						?>
						<input
							id="abandoned-subject"
							name="abandoned_subject"
							type="text"
							class="regular-text"
							required
							value="<?php echo $abandoned_subject; ?>"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="abandoned-email-body">
							<?php _e( 'Email Body', 'cf7-abandoned-form' ); ?>
						</label>
						<span class="cf7af-tooltip hide-if-no-js " id="abandoned-email-body-pointer"></span>
					</th>
					<td>
						<?php
<<<<<<< HEAD
=======
						$cf7af_total = get_option('cf7af_total');
						$key = array_search ($abandoned_id, $cf7af_total);
						if($key >= 10)
						{
							echo '<table><tbody>';
							//echo'<tr class="inside-field"><th scope="row"><img src="'.CF7AF_URL.'/assets/images/editor_disable.png"></th></tr>';
							echo'<tr class="inside-field"><th scope="row">You are using Free Abandoned Contact Form 7 - no license needed. Enjoy! 🙂</th></tr>';
							echo'<tr class="inside-field"><th scope="row"><a href="https://www.zealousweb.com/wordpress-plugins/product/abandoned-contact-form-7-pro/" target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
						echo '</tbody></table>';
						$content = isset( $cf7af_mail_notify_opt['cf7af_email_body'] ) ? stripslashes($cf7af_mail_notify_opt['cf7af_email_body']) : '' ;
							echo '<input type="hidden" name="abandoned_email_body" value="'.$content.'">';
					}
						else
						{
							if($cf7af_total)
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
							$content = isset( $cf7af_mail_notify_opt['cf7af_email_body'] ) ? stripslashes($cf7af_mail_notify_opt['cf7af_email_body']) : '' ;

							$settings = array('textarea_rows'=> '10', 'media_buttons' => true ) ;
							wp_editor( $content, 'abandoned_email_body', $settings );
<<<<<<< HEAD
=======
						}
						
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
						?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" valign="top"></th>
					<td>
						<input type="submit" name="send_mail" class="button button-primary" value="<?php _e( 'Send', 'cf7-abandoned-form' ); ?>" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), '_send_mail_nonce_name' ); ?>
					</td>
				</tr>

				<tbody>
			</table>
		</form>
	</div>

<?php

	// Localize the script with new data
	$translation_array = array(
		'abandoned_email_address' => __( '<h3>User Email Address (To)</h3>' .
			'<p>This is an Abandoned user&apos;s email ID which will receive the email.</p>', 'cf7-abandoned-form' ),
		'abandoned_from_name' => __( '<h3>From Name</h3>' .
			'<p>This is a default  &apos;Name&apos; which is get from website general settings but if you use SMTP settings then From Name used from SMTP settings page.</p>', 'cf7-abandoned-form' ),
		'abandoned_from_email_address' => __( '<h3>From Email Address</h3>' .
			'<p>This is a default &apos;Email Address&apos; which is get from website general settings but if you use SMTP settings then From Email used from SMTP settings page.</p>', 'cf7-abandoned-form' ),
		'abandoned_subject' => __( '<h3>Subject</h3>' .
			'<p>This is the subject which is used in email.</p>', 'cf7-abandoned-form' ),
		'abandoned_email_body' => __( '<h3>Email Body</h3>' .
			'<p>This is an email body content which are reflect on email body.</p>', 'cf7-abandoned-form' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

?>