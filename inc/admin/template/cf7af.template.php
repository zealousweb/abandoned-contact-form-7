<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$cf7af_custom_id = ( isset( $_REQUEST['post'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['post'] ) ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- CF7 editor context.

	if ( empty( $cf7af_custom_id ) ) {
		$cf7af_wpcf7 = WPCF7_ContactForm::get_current();
		$cf7af_custom_id = $cf7af_wpcf7->id(); //phpcs:ignore
	}
	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );

	$cf7af_enable_abandoned	= get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'enable_abandoned', true );
	$cf7af_abandoned_email	= get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'abandoned_email', true );
	$cf7af_abandoned_specific_field	= get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'abandoned_specific_field',false);
	echo '<fieldset>'.
		'<div class="cf7af-settings">' .
		'<div class="left-box postbox">' .
			'<table class="form-table tooltip-table">' .
				'<tbody>' .
					'<tr class="form-field">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX . 'enable_abandoned' ) . '">' .
								esc_html__( 'Enable Abandoned', 'abandoned-contact-form-7' ) .
							'</label>' .
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-enable-abandoned-pointer"></span>' .
						'</th>' .
						'<td>' .
						    '<input id="' . esc_attr( CF7AF_META_PREFIX ) . 'enable_abandoned" name="' . esc_attr( CF7AF_META_PREFIX ) . 'enable_abandoned" type="checkbox" class="enable_required" value="1" ' . checked( $cf7af_enable_abandoned, 1, false ) . '/>' .
						'</td>'.
					'</tr>'.
					'<tr class="form-field select-abandoned-email-row">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_email">' .
						esc_html__( 'Select Email Field', 'abandoned-contact-form-7' ) .
						'</label>'.						
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-email-pointer"></span>' .
						'</th>' .
						'<td>';

						echo '<select name="' . esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ) . '" id="' . esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ) . '" required>';
								'<option>'.__( 'Select Email Field', 'abandoned-contact-form-7' ).'</option>';
									$cf7af_contact_form = WPCF7_ContactForm::get_instance($cf7af_custom_id);
									$cf7af_form_fields = $cf7af_contact_form->scan_form_tags();
									if( $cf7af_form_fields ) {
										foreach ( $cf7af_form_fields as $cf7af_form_field ) {
											if( $cf7af_form_field->basetype == 'email' ) {
												$cf7af_mail_tag = $cf7af_form_field->name;
												echo '<option value="' . esc_attr( $cf7af_mail_tag ) . '" ' . selected( $cf7af_abandoned_email, $cf7af_mail_tag, false ) . '>[' . esc_html( $cf7af_mail_tag ) . ']</option>';
											}
										}
									}
							echo '</select>'.

						'</td>' .
					'<tr class="form-field select-abandoned-specific-field-row">' .
						'<th scope="row">' .
						'<label for="' . esc_attr( CF7AF_META_PREFIX . 'specific_field' ) . '">' .
						esc_html__( 'Select Multiple Field', 'abandoned-contact-form-7' ) .
						'</label>'.
							'<span class="cf7af-tooltip hide-if-no-js " id="cf7af-abandoned-specific-field-pointer"></span>' .
						'</th>' .
						'<td>';
						echo '<select name="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_specific_field[]"  id="' . esc_attr( CF7AF_META_PREFIX ) . 'abandoned_specific_field" multiple="yes">';
									if( $cf7af_form_fields ) {
										foreach ( $cf7af_form_fields as $cf7af_form_field ) {
											if( $cf7af_form_field->basetype != 'email' && $cf7af_form_field->basetype != 'submit') {
												$cf7af_mail_tag = $cf7af_form_field->name;

												if(!empty($cf7af_abandoned_specific_field)) {
													$cf7af_selected='';
													if (in_array($cf7af_mail_tag, $cf7af_abandoned_specific_field)){
														$cf7af_selected='selected="selected"';
												  	}
													else{
												  		$cf7af_selected='';
												  	}
												}
												echo '<option value="' . esc_attr( $cf7af_mail_tag ) . '" ' . esc_attr( $cf7af_selected ) . '>[' . esc_html( $cf7af_mail_tag ) . ']</option>';
											}
										}
									}
							echo '</select>'.

						'</td>' .
					'</tr>';
					'</tr>';

					echo '<input type="hidden" name="post" value="' . esc_attr( $cf7af_custom_id ) . '">' .
				'</tbody>' .
			'</table>' .

		'</div>' .

	'</div> </fieldset>';

	// Localize the script with new data
	$cf7af_translation_array = array(
		'cf7af_enable_abandoned' => __( '<h3>Enable/Disable Abandoned</h3><p>You can enable/disable Abandoned form functionality.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_email' => __( '<h3>Select Email Field</h3><p>Select the email field for tracking Abandoned user</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_specific_field' => __( '<h3>Select Multiple Field</h3><p>Select the multiple field for tracking Abandoned user</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );