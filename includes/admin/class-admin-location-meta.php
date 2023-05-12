<?php

class Inventory_Presser_Admin_Location_Meta {

	public function add_hooks() {
		// location taxonomy admin actions
		add_action( 'location_add_form_fields', array( $this, 'add_location_fields' ), 10, 2 );
		add_action( 'location_edit_form_fields', array( $this, 'edit_location_field' ), 10, 2 );
		add_action( 'created_location', array( $this, 'save_location_meta' ), 10, 2 );
		add_action( 'edited_location', array( $this, 'save_location_meta' ), 10, 2 );

		// Load JavaScript for timepickers, draggable and repeating fields.
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * add_location_fields
	 *
	 * Outputs fields that are added to the add term page.
	 *
	 * @param  string $taxonomy
	 * @return void
	 */
	function add_location_fields( $taxonomy ) {
		?><div class="form-field term-group location-tax">
			<div class="form-wrap form-field">
				<label><?php _e( 'Phone Numbers', 'inventory-presser' ); ?></label>
				<div class="repeat-group">
					<div class="repeat-container"></div>
					<div class="repeat-this">
						<div class="repeat-form">
							<input type="text" name="phone_description[]" placeholder="<?php _e( 'Label', 'inventory-presser' ); ?>" />
							<input type="text" name="phone_number[]" placeholder="<?php _e( 'Number', 'inventory-presser' ); ?>" required />
						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
							<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this phone number', 'inventory-presser' ); ?>"></span>
						</div>
					</div>
					<button type="button" class="repeat-add button action"><?php _e( 'Add Phone Number', 'inventory-presser' ); ?></button>
				</div>
			</div>
			<div class="form-wrap form-field">
				<label><?php _e( 'Hours', 'inventory-presser' ); ?></label>
				<div class="repeat-group">
					<div class="repeat-container"></div>
					<div class="repeat-this">
						<div class="repeat-form">

						<input type="text" name="hours_title[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" />

						<table class="wp-list-table widefat fixed striped hours">
							<thead>
								<th class="day-col"></th>
								<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
								<th class="to-col"></th>
								<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
								<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
							</thead>
							<tbody>
							<?php

							foreach ( array_keys( INVP::weekdays() ) as $index => $day ) {
								?>
								<tr>
									<th><?php echo ucfirst( $day ); ?></th>
									<td><input name="hours[<?php echo $index; ?>][open][]" class="timepick" type="text"></td>
									<th>to</th>
									<td><input name="hours[<?php echo $index; ?>][close][]" class="timepick" type="text"></td>
									<td>
										<select name="hours[<?php echo $index; ?>][appt][]">
											<option value="0"><?php _e( 'No', 'inventory-presser' ); ?></option>
											<option value="1"><?php _e( 'Yes', 'inventory-presser' ); ?></option>
										</select>
									</td>
								</tr>
								<?php

							}

							?>
							</tbody>
							</table>

						</div>
						<div class="repeat-buttons">
							<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
							<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
						</div>
					</div>
					<p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory-presser' ); ?></p>
					<p><button type="button" class="repeat-add button action"><?php _e( 'Add Hours Block', 'inventory-presser' ); ?></button></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * edit_location_field
	 *
	 * Outputs HTML that renders additional fields for the edit term screen.
	 *
	 * @param  WP_Term $term     Current taxonomy term object.
	 * @param  string  $taxonomy Current taxonomy slug.
	 * @return void
	 */
	function edit_location_field( $term, $taxonomy ) {
		$meta = get_term_meta( $term->term_id );

		?>
		<tr class="form-field term-group-wrap">
					<th scope="row">
						<label><?php _e( 'Street Address', 'inventory-presser' ); ?></label>
					</th>
					<td>
						<input type="text" name="address_street" value="<?php echo $meta['address_street'][0] ?? ''; ?>" />
					</td>
				</tr>
				<tr class="form-field term-group-wrap">
					<th scope="row">
						<label><?php _e( 'Street Address line two', 'inventory-presser' ); ?></label>
					</th>
					<td>
						<input type="text" name="address_street_line_two" value="<?php echo $meta['address_street_line_two'][0] ?? ''; ?>" />
					</td>
				</tr>
				<tr class="form-field term-group-wrap">
					<th scope="row">
						<label><?php _e( 'City', 'inventory-presser' ); ?></label>
					</th>
					<td>
						<input type="text" name="address_city" value="<?php echo $meta['address_city'][0] ?? ''; ?>" />
					</td>
				</tr>
				<tr class="form-field term-group-wrap">
					<th scope="row">
						<label><?php _e( 'State', 'inventory-presser' ); ?></label>
					</th>
					<td>
						<input type="text" name="address_state" value="<?php echo $meta['address_state'][0] ?? ''; ?>" />
					</td>
				</tr>
				<tr class="form-field term-group-wrap">
					<th scope="row">
						<label><?php _e( 'Zip', 'inventory-presser' ); ?></label>
					</th>
					<td>
						<input type="text" name="address_zip" value="<?php echo $meta['address_zip'][0] ?? ''; ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<h2 class="title"><?php _e( 'Phone Numbers & Hours', 'inventory-presser' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="form-field term-group-wrap">
					<th scope="row"><label><?php _e( 'Phone Numbers', 'inventory-presser' ); ?></label></th>
					<td>
						<div class="repeat-group">
							<div class="repeat-container">
							<?php

							$phones = INVP::get_phones( $term->term_id );
							if ( ! empty( $phones ) ) {
								foreach ( $phones as $phone ) {
									?>
									<div class="repeated">
									<div class="repeat-form">
									<?php

									printf(
										'<input type="hidden" name="phone_uid[]" value="%s" />'
										. '<input type="text" name="phone_description[]" value="%s" placeholder="%s" />'
										. '<input type="text" name="phone_number[]" value="%s" placeholder="%s" />',
										$phone['uid'],
										$phone['description'],
										__( 'Label', 'inventory-presser' ),
										$phone['number'],
										__( 'Number', 'inventory-presser' )
									);

									?>
																</div>
									<div class="repeat-buttons">
										<span class="dashicons dashicons-menu repeat-move"></span>
										<span class="dashicons dashicons-trash repeat-delete"></span>
									</div>
								</div>
									<?php

								}
							}

							?>
							</div>
							<div class="repeat-this">
								<div class="repeat-form">
									<input type="text" name="phone_description[]" placeholder="<?php _e( 'Label', 'inventory-presser' ); ?>" />
									<input type="text" name="phone_number[]" placeholder="<?php _e( 'Number', 'inventory-presser' ); ?>" />
								</div>
								<div class="repeat-buttons">
									<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
									<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
								</div>
							</div>
							<button type="button" class="repeat-add button action"><?php _e( 'Add Phone Number', 'inventory-presser' ); ?></button>
						</div>
					</td>
				</tr>
				<tr class="form-field term-group-wrap">
					<th scope="row"><label><?php _e( 'Hours', 'inventory-presser' ); ?></label></th>
					<td>
						<div class="repeat-group">
							<div class="repeat-container">
							<?php

							$hours_sets = INVP::get_hours( $term->term_id );
							$days       = array_keys( INVP::weekdays() );
							if ( ! empty( $hours_sets ) ) {
								foreach ( $hours_sets as $hours ) {

									?>
									<div class="repeated">
									<div class="repeat-form">

										<input type="text" name="hours_title[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" value="<?php echo $hours['title']; ?>" />
										<input type="hidden" name="hours_uid[]" placeholder="<?php _e( 'Title', 'inventory-presser' ); ?>" value="<?php echo $hours['uid']; ?>" />

										<table class="repeater-table hours">
											<thead>
												<th class="day-col"></th>
												<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
												<th class="to-col"></th>
												<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
												<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
											</thead>
											<tbody>
											<?php

											for ( $d = 0; $d < 7; $d++ ) {

												?>
												<tr>
												<td><?php echo ucfirst( substr( $days[ $d ], 0, 3 ) ); ?></td>
												<td><input name="hours[<?php echo $d; ?>][open][]" class="timepick" type="text" value="<?php echo $hours[ $days[ $d ] . '_open' ]; ?>"></td>
												<td>to</td>
												<td><input name="hours[<?php echo $d; ?>][close][]" class="timepick" type="text" value="<?php echo $hours[ $days[ $d ] . '_close' ]; ?>"></td>
												<td>
													<select name="hours[<?php echo $d; ?>][appt][]" autocomplete="off">
														<option value="0"<?php echo ( $hours[ $days[ $d ] . '_appt' ] == '0' ) ? ' selected' : ''; ?>><?php _e( 'No', 'inventory-presser' ); ?></option>
														<option value="1"<?php echo ( $hours[ $days[ $d ] . '_appt' ] == '1' ) ? ' selected' : ''; ?>><?php _e( 'Yes', 'inventory-presser' ); ?></option>
													</select>
												</td>
											</tr>
												<?php

											}

											?>
											</tbody>
									</table>

									</div>
									<div class="repeat-buttons">
										<span class="dashicons dashicons-menu repeat-move" title="<?php _e( 'Drag to reposition', 'inventory-presser' ); ?>"></span>
										<span class="dashicons dashicons-trash repeat-delete" title="<?php _e( 'Delete this set of hours', 'inventory-presser' ); ?>"></span>
									</div>
								</div>
									<?php

								}
							}

							?>
							</div>
							<div class="repeat-this">
								<div class="repeat-form">

									<input type="text" name="hours_title[]" placeholder="Title" />

									<table class="repeater-table hours">
										<thead>
											<th class="day-col"></th>
											<th><?php _e( 'Open', 'inventory-presser' ); ?></th>
											<th class="to-col"></th>
											<th><?php _e( 'Close', 'inventory-presser' ); ?></th>
											<th><?php _e( 'Appt Only', 'inventory-presser' ); ?></th>
										</thead>
										<tbody>
										<?php

										foreach ( array_keys( INVP::weekdays() ) as $d => $day ) {

											?>
											<tr>
												<td><?php echo ucfirst( substr( $days[ $d ], 0, 3 ) ); ?></td>
												<td><input name="hours[<?php echo $d; ?>][open][]" class="timepick" type="text"></td>
												<td>to</td>
												<td><input name="hours[<?php echo $d; ?>][close][]" class="timepick" type="text"></td>
												<td>
													<select name="hours[<?php echo $d; ?>][appt][]">
														<option value="0"><?php _e( 'No', 'inventory-presser' ); ?></option>
														<option value="1"><?php _e( 'Yes', 'inventory-presser' ); ?></option>
													</select>
												</td>
											</tr>
											<?php

										}

										?>
										</tbody>
									</table>

								</div>
								<div class="repeat-buttons">
									<span class="dashicons dashicons-menu repeat-move"></span>
									<span class="dashicons dashicons-trash repeat-delete"></span>
								</div>
							</div>
							<p class="description"><?php _e( 'When saving multiple sets of hours for a single location, position the primary showroom hours first.', 'inventory-presser' ); ?></p>
							<p><button type="button" class="repeat-add button action"><?php _e( 'Add Hours Block', 'inventory-presser' ); ?></button></p>
						</div>
					</td>
				</tr>
				<?php
	}

	/**
	 * generate_location_uid
	 *
	 * Creates a unique ID for a phone number or set of hours.
	 *
	 * @param  string $salt_string Any string to be combined with rand() as the value to pass to md5()
	 * @return string A string of 12 characters.
	 */
	function generate_location_uid( $salt_string = null ) {
		return substr( md5( strval( rand() ) . $salt_string ), 0, 12 );
	}

	/**
	 * Loads stylesheets and JavaScript files on pages that edit terms.
	 *
	 * @param  string $hook The file name of the current page
	 * @return void
	 */
	function scripts_and_styles( $hook ) {
		global $current_screen;
		if ( ( $hook == 'edit-tags.php' || $hook == 'term.php' )
			&& $current_screen->post_type == INVP::POST_TYPE
			&& $current_screen->taxonomy == 'location'
		) {
			wp_enqueue_style( 'inventory-presser-timepicker-css', plugins_url( '/css/jquery.timepicker.min.css', INVP_PLUGIN_FILE_PATH ) );
			wp_enqueue_script( 'inventory-presser-timepicker', plugins_url( '/js/jquery.timepicker.min.js', INVP_PLUGIN_FILE_PATH ), array( 'jquery' ), '1.8.10' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'inventory-presser-location', plugins_url( '/js/tax-location.min.js', INVP_PLUGIN_FILE_PATH ), array( 'inventory-presser-timepicker', 'jquery-ui-sortable' ) );
		}
	}

	/**
	 * save_location_meta
	 *
	 * When a location term is saved, put all of the values in the right term
	 * meta fields.
	 *
	 * @param  int $term_id
	 * @param  int $tt_id
	 * @return void
	 */
	public function save_location_meta( $term_id, $tt_id ) {
		// Street address pieces
		$keys = array(
			'address_street',
			'address_street_line_two',
			'address_city',
			'address_state',
			'address_zip',
		);
		foreach ( $keys as $key ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				update_term_meta( $term_id, $key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}

		/**
		 * Now that the street address has been updated, repopulate the term
		 * description based on these pieces in term meta so the full street
		 * address in the term description always matches the pieces of the
		 * address.
		 */
		$this->update_location_term_description( $term_id );

		// HOURS
		if ( ! empty( $_POST['hours_title'] ) ) {
			$hours_count = sizeof( $_POST['hours_title'] ) - 1;
			for ( $i = 0; $i <= $hours_count; $i++ ) {
				// if this is an update, carry the id through
				$uid = '';
				if ( isset( $_POST['hours_uid'][ $i ] ) ) {
					$uid = sanitize_text_field( $_POST['hours_uid'][ $i ] );
				} else {
					// generate a unique id for these hours
					$uid = $this->generate_location_uid( $term_id . '_hours_' . $i );
				}
				update_term_meta( $term_id, 'hours_' . strval( $i + 1 ) . '_uid', $uid );

				// title of hours set
				update_term_meta( $term_id, 'hours_' . strval( $i + 1 ) . '_title', sanitize_text_field( $_POST['hours_title'][ $i ] ) );

				foreach ( array_keys( INVP::weekdays() ) as $d => $day ) {
					update_term_meta( $term_id, 'hours_' . strval( $i + 1 ) . '_' . $day . '_appt', sanitize_text_field( $_POST['hours'][ $d ]['appt'][ $i ] ) );
					update_term_meta( $term_id, 'hours_' . strval( $i + 1 ) . '_' . $day . '_open', sanitize_text_field( $_POST['hours'][ $d ]['open'][ $i ] ) );
					update_term_meta( $term_id, 'hours_' . strval( $i + 1 ) . '_' . $day . '_close', sanitize_text_field( $_POST['hours'][ $d ]['close'][ $i ] ) );
				}
			}

			// delete hours in slots higher than we just filled or deletes are not possible
			for ( $h = $hours_count + 1; $h < INVP::LOCATION_MAX_HOURS; $h++ ) {
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_uid' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_title' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_sunday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_sunday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_sunday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_saturday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_saturday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_saturday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_friday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_friday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_friday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_thursday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_thursday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_thursday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_wednesday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_wednesday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_wednesday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_tuesday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_tuesday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_tuesday_close' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_monday_appt' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_monday_open' );
				delete_term_meta( $term_id, 'hours_' . strval( $h ) . '_monday_close' );
			}
		}

		// PHONE NUMBERS
		if ( ! empty( $_POST['phone_number'] ) ) {
			$phones_count = 0;
			foreach ( $_POST['phone_number'] as $i => $phone_number ) {
				$phone_number = sanitize_text_field( $phone_number );
				if ( empty( $phone_number ) ) {
					continue;
				}

				update_term_meta( $term_id, 'phone_' . strval( $i + 1 ) . '_number', $phone_number );
				update_term_meta( $term_id, 'phone_' . strval( $i + 1 ) . '_description', sanitize_text_field( $_POST['phone_description'][ $i ] ) );

				// if this is an update, carry the id through
				$uid = '';
				if ( isset( $_POST['phone_uid'][ $i ] ) ) {
					$uid = sanitize_text_field( $_POST['phone_uid'][ $i ] );
				} else {
					// generate a unique id for this phone number
					$uid = $this->generate_location_uid( $term_id . '_phone_' . $i );
				}
				update_term_meta( $term_id, 'phone_' . strval( $i + 1 ) . '_uid', $uid );

				$phones_count++;
			}

			// delete phones in slots higher than we just filled or deletes are not possible
			for ( $p = $phones_count + 1; $p < INVP::LOCATION_MAX_PHONES; $p++ ) {
				delete_term_meta( $term_id, 'phone_' . strval( $p ) . '_uid' );
				delete_term_meta( $term_id, 'phone_' . strval( $p ) . '_description' );
				delete_term_meta( $term_id, 'phone_' . strval( $p ) . '_number' );
			}
		}
	}

	/**
	 * update_location_term_description
	 *
	 * @param  int $term_id
	 * @return void
	 */
	protected function update_location_term_description( $term_id ) {
		$line_one = $line_two = $line_three = '';
		$meta     = get_term_meta( $term_id );
		if ( ! empty( $meta['address_street'][0] ) ) {
			$line_one .= $meta['address_street'][0];
		}
		if ( ! empty( $meta['address_street_line_two'][0] ) ) {
			$line_two .= $meta['address_street_line_two'][0];
		}
		if ( ! empty( $meta['address_city'][0] ) ) {
			$line_three .= $meta['address_city'][0];
		}
		if ( ! empty( $meta['address_state'][0] ) ) {
			$line_three .= ', ' . $meta['address_state'][0];
		}
		if ( ! empty( $meta['address_zip'][0] ) ) {
			$line_three .= ' ' . $meta['address_zip'][0];
		}
		$term = get_term( $term_id );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			return;
		}

		/**
		 * This method is called from save_location_meta, which is hooked to
		 * location_edited. Remove that hook or else an infinite loop happens.
		 */
		remove_action( 'edited_location', array( $this, 'save_location_meta' ), 10, 2 );
		wp_update_term(
			$term->term_id,
			$term->taxonomy,
			array(
				'description' => trim( $line_one . "\n" . trim( $line_two . "\n" . $line_three ) ),
			)
		);
		add_action( 'edited_location', array( $this, 'save_location_meta' ), 10, 2 );
	}
}
