<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$cf7af_custom_id = CF7AF_Helpers::get_wpcf7_editor_post_id();
	if ( ! $cf7af_custom_id ) {
		return;
	}

	wp_enqueue_script( 'wp-pointer' );
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_style( CF7AF_PREFIX . '_admin_css' );

	$cf7af_enable_abandoned          = get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'enable_abandoned', true );
	$cf7af_abandoned_email           = get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'abandoned_email', true );
	$cf7af_abandoned_specific_field  = get_post_meta( $cf7af_custom_id, CF7AF_META_PREFIX . 'abandoned_specific_field', false );
	if ( ! is_array( $cf7af_abandoned_specific_field ) ) {
		$cf7af_abandoned_specific_field = array();
	}

	$cf7af_contact_form = WPCF7_ContactForm::get_instance( $cf7af_custom_id );
	$cf7af_form_fields  = $cf7af_contact_form ? $cf7af_contact_form->scan_form_tags() : array();
	$cf7af_track_fields = array();

	if ( $cf7af_form_fields ) {
		foreach ( $cf7af_form_fields as $cf7af_form_field ) {
			if ( '' === $cf7af_form_field->name ) {
				continue;
			}
			if ( in_array( $cf7af_form_field->basetype, array( 'email', 'submit' ), true ) ) {
				continue;
			}
			$cf7af_track_fields[] = $cf7af_form_field;
		}
	}
	?>
	<fieldset>
		<div class="cf7af-settings">
			<div class="left-box postbox">
				<table class="form-table tooltip-table">
					<tbody>
						<tr class="form-field">
							<th scope="row">
								<label for="<?php echo esc_attr( CF7AF_META_PREFIX . 'enable_abandoned' ); ?>">
									<?php esc_html_e( 'Enable Abandoned', 'abandoned-contact-form-7' ); ?>
								</label>
								<span class="cf7af-tooltip hide-if-no-js" id="cf7af-enable-abandoned-pointer"></span>
							</th>
							<td>
								<input
									id="<?php echo esc_attr( CF7AF_META_PREFIX . 'enable_abandoned' ); ?>"
									name="<?php echo esc_attr( CF7AF_META_PREFIX . 'enable_abandoned' ); ?>"
									type="checkbox"
									class="enable_required"
									value="1"
									<?php checked( $cf7af_enable_abandoned, '1' ); ?>
								/>
							</td>
						</tr>
						<tr class="form-field select-abandoned-email-row">
							<th scope="row">
								<label for="<?php echo esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ); ?>">
									<?php esc_html_e( 'Select Email Field', 'abandoned-contact-form-7' ); ?>
								</label>
								<span class="cf7af-tooltip hide-if-no-js" id="cf7af-abandoned-email-pointer"></span>
							</th>
							<td>
								<select
									name="<?php echo esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ); ?>"
									id="<?php echo esc_attr( CF7AF_META_PREFIX . 'abandoned_email' ); ?>"
									class="cf7af-select"
									required
								>
									<option value=""><?php esc_html_e( 'Select Email Field', 'abandoned-contact-form-7' ); ?></option>
									<?php
									if ( $cf7af_form_fields ) {
										foreach ( $cf7af_form_fields as $cf7af_form_field ) {
											if ( 'email' !== $cf7af_form_field->basetype || '' === $cf7af_form_field->name ) {
												continue;
											}
											printf(
												'<option value="%1$s" %2$s>[%3$s]</option>',
												esc_attr( $cf7af_form_field->name ),
												selected( $cf7af_abandoned_email, $cf7af_form_field->name, false ),
												esc_html( $cf7af_form_field->name )
											);
										}
									}
									?>
								</select>
							</td>
						</tr>
						<tr class="form-field select-abandoned-specific-field-row">
							<th scope="row">
								<label for="cf7af-abandoned-specific-fields">
									<?php esc_html_e( 'Fields to Track', 'abandoned-contact-form-7' ); ?>
								</label>
								<span class="cf7af-tooltip hide-if-no-js" id="cf7af-abandoned-specific-field-pointer"></span>
							</th>
							<td>
								<div class="cf7af-field-picker" id="cf7af-abandoned-specific-fields">
									<p class="description cf7af-field-picker__intro">
										<?php esc_html_e( 'Choose which form fields are stored when a visitor abandons the form. click to toggle each field. ', 'abandoned-contact-form-7' ); ?>
									</p>
									</br>
									<?php if ( empty( $cf7af_track_fields ) ) : ?>
										<p class="cf7af-field-picker__empty">
											<?php esc_html_e( 'No trackable fields found. Add fields to your form (other than email and submit).', 'abandoned-contact-form-7' ); ?>
										</p>
									<?php else : ?>
										<ul class="cf7af-field-picker__list" role="group" aria-label="<?php esc_attr_e( 'Fields to track', 'abandoned-contact-form-7' ); ?>">
											<?php
											foreach ( $cf7af_track_fields as $cf7af_form_field ) {
												$cf7af_field_id = CF7AF_META_PREFIX . 'track-' . sanitize_html_class( $cf7af_form_field->name );
												?>
												<li class="cf7af-field-picker__item">
													<label for="<?php echo esc_attr( $cf7af_field_id ); ?>" class="cf7af-field-picker__label">
														<input
															type="checkbox"
															class="cf7af-field-picker__checkbox"
															id="<?php echo esc_attr( $cf7af_field_id ); ?>"
															name="<?php echo esc_attr( CF7AF_META_PREFIX . 'abandoned_specific_field[]' ); ?>"
															value="<?php echo esc_attr( $cf7af_form_field->name ); ?>"
															<?php checked( in_array( $cf7af_form_field->name, $cf7af_abandoned_specific_field, true ) ); ?>
														/>
														<span class="cf7af-field-picker__tag">[<?php echo esc_html( $cf7af_form_field->name ); ?>]</span>
														<span class="cf7af-field-picker__type"><?php echo esc_html( $cf7af_form_field->basetype ); ?></span>
													</label>
												</li>
												<?php
											}
											?>
										</ul>
										<p class="cf7af-field-picker__actions">
											<button type="button" class="button-link" id="cf7af-select-all-fields">
												<?php esc_html_e( 'Select all', 'abandoned-contact-form-7' ); ?>
											</button>
											<span aria-hidden="true">|</span>
											<button type="button" class="button-link" id="cf7af-clear-all-fields">
												<?php esc_html_e( 'Clear all', 'abandoned-contact-form-7' ); ?>
											</button>
										</p>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="post" value="<?php echo esc_attr( $cf7af_custom_id ); ?>">
			</div>
		</div>
	</fieldset>
	<?php

	$cf7af_translation_array = array(
		'cf7af_enable_abandoned'          => __( '<h3>Enable/Disable Abandoned</h3><p>You can enable or disable abandoned form tracking for this contact form.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_email'           => __( '<h3>Select Email Field</h3><p>Select the email field used to identify abandoned users.</p>', 'abandoned-contact-form-7' ),
		'cf7af_abandoned_specific_field'  => __( '<h3>Fields to Track</h3><p>Select which fields are saved when a user leaves the form without submitting.</p>', 'abandoned-contact-form-7' ),
	);

	wp_localize_script( CF7AF_PREFIX . '_admin_js', 'translate_string_cf7af', $cf7af_translation_array );
	wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );
