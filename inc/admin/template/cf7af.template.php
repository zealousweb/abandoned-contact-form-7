<?php
	$custom_id = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' ); //phpcs:ignore

	if ( empty( $custom_id ) ) {
		$wpcf7 = WPCF7_ContactForm::get_current();
		$custom_id = $wpcf7->id(); //phpcs:ignore
	}
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );

	$enable_abandoned	= get_post_meta( $custom_id, CF7AF_META_PREFIX . 'enable_abandoned', true );
	$abandoned_email	= get_post_meta( $custom_id, CF7AF_META_PREFIX . 'abandoned_email', true );
	$abandoned_specific_field	= get_post_meta( $custom_id, CF7AF_META_PREFIX . 'abandoned_specific_field',false);
	echo '<fieldset>'.
		'<div class="cf7af-settings">' .
		'<div class="left-box postbox">' .
			'<table class="form-table tooltip-table">' .
				'<tbody>' .
					'<tr class="form-field">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX . 'enable_abandoned' ) . '">' .
								esc_html__( 'Enable Abandoned', 'cf7-abandoned-form' ) .
							'</label>' .
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-enable-abandoned-pointer"></span>' .
						'</th>' .
						'<td>' .
						    '<input id="' . esc_attr( CF7AF_META_PREFIX ) . 'enable_abandoned" name="' . esc_attr( CF7AF_META_PREFIX ) . 'enable_abandoned" type="checkbox" class="enable_required" value="1" ' . checked( $enable_abandoned, 1, false ) . '/>' .
						'</td>'.
					'</tr>'.
					'<tr class="form-field select-abandoned-email-row">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_email">' .
						esc_html__( 'Select Email Field', 'cf7-abandoned-form' ) .
						'</label>'.						
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-email-pointer"></span>' .
						'</th>' .
						'<td>';

						echo '<select name="' . esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ) . '" id="' . esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ) . '" required>';
								'<option>'.__( 'Select Email Field', 'cf7-abandoned-form' ).'</option>';
									$contact_form = WPCF7_ContactForm::get_instance($custom_id);
									$form_fields = $contact_form->scan_form_tags();
									if( $form_fields ) {
										foreach ( $form_fields as $form_field ) {
											if( $form_field->basetype == 'email' ) {
												$mail_tag = $form_field->name;
												echo '<option value="' . esc_attr( $mail_tag ) . '" ' . selected( $abandoned_email, $mail_tag, false ) . '>[' . esc_html( $mail_tag ) . ']</option>';
											}
										}
									}
							echo '</select>'.

						'</td>' .
					'<tr class="form-field select-abandoned-specific-field-row">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX . 'specific_field' ) . '">' .
						esc_html__( 'Select Multiple Field', 'cf7-abandoned-form' ) .
						'</label>'.
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-specific-field-pointer"></span>' .
						'</th>' .
						'<td>';
						echo '<select name="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_specific_field[]"  id="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_specific_field" multiple="yes">';
									if( $form_fields ) {
										foreach ( $form_fields as $form_field ) {
											if( $form_field->basetype != 'email' && $form_field->basetype != 'submit') {
												$mail_tag = $form_field->name;

												if(!empty($abandoned_specific_field)) {
													$selected='';
													if (in_array($mail_tag, $abandoned_specific_field)){
														$selected='selected="selected"';
												  	}
													else{
												  		$selected='';
												  	}
												}
												echo '<option value="' . esc_attr( $mail_tag ) . '" ' . esc_attr( $selected ) . '>[' . esc_html( $mail_tag ) . ']</option>';
											}
										}
									}
							echo '</select>'.

						'</td>' .
					'</tr>';
					'</tr>';

					echo '<input type="hidden" name="post" value="' . esc_attr( $custom_id ) . '">' .
				'</tbody>' .
			'</table>' .

		'</div>' .

	'</div> </fieldset>';

	// Localize the script with new data
	$translation_array = array(
		'cf7af_enable_abandoned' => __( '<h3>Enable/Disable Abandoned</h3>' .
					'<p>You can enable/disable Abandoned form functionality.</p>', 'cf7-abandoned-form' ),
		'cf7af_abandoned_email' => __( '<h3>Select Email Field</h3>' .
					'<p>Select the email field for tracking Abandoned user</p>', 'cf7-abandoned-form' ),

		'cf7af_abandoned_specific_field' => __( '<h3>Select Multiple Field</h3>' .
				'<p>Select the multiple field for tracking Abandoned user</p>', 'cf7-abandoned-form' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );