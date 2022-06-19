<?php

namespace JITS\StringLocator\Extension\SQL;

class Save {

	private $override_save = false;

	public function __construct() {
		add_filter( 'string_locator_save_params', array( $this, 'check_save_parameters' ) );
		add_filter( 'string_locator_save_handler', array( $this, 'maybe_handle_save' ) );
	}

	public function check_save_parameters( $parameters ) {
		if ( isset( $parameters['file-type'] ) && 'sql' === $parameters['file-type'] ) {
			$this->override_save = true;
		}

		return $parameters;
	}

	public function maybe_handle_save( $handler ) {
		if ( ! $this->override_save ) {
			return $handler;
		}

		return $this;
	}

	public function save( $params ) {
		global $wpdb;

		$content = $params['string-locator-editor-content'];

		/**
		 * Filter if the save process should be performed or not.
		 *
		 * @attr bool   $can_save Can the save be carried out.
		 * @attr string $content  The content to save.
		 * @attr string $path     Path to the file being edited.
		 */
		$can_save = apply_filters( 'string_locator_pre_save', true, $content, 'sql' );

		if ( ! $can_save ) {
			return array(
				'notices' => apply_filters( 'string_locator_pre_save_fail_notice', array() ),
			);
		}

		if ( 'int' === $params['sql-primary-type'] ) {
			$original = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT " . $params['sql-column'] . " FROM " . $params['sql-table'] . " WHERE " . $params['sql-primary-column'] . " = %d LIMIT 1",
					$params['sql-primary-key']
				)
			);
		} else {
			$original = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT " . $params['sql-column'] . " FROM " . $params['sql-table'] . " WHERE " . $params['sql-primary-column'] . " = %s LIMIT 1",
					$params['sql-primary-key']
				)
			);
		}

		$wpdb->update(
			$params['sql-table'],
			array(
				$params['sql-column'] => $content,
			),
			array(
				$params['sql-primary-column'] => $params['sql-primary-key'],
			)
		);

		/**
		 * Filter if the save process completed as it should or if warnings should be returned.
		 *
		 * @attr bool   $save_successful Boolean indicating if the save was successful.
		 * @attr string $content         The edited content.
		 * @attr string $original        The original content.
		 * @attr string $path            The path to the file being edited.
		 */
		$save_successful = apply_filters( 'string_locator_post_save', true, $content, $original, 'sql' );

		/**
		 * Check the status of the site after making our edits.
		 * If the site fails, revert the changes to return the sites to its original state
		 */
		if ( ! $save_successful ) {
			$wpdb->update(
				$params['sql-table'],
				array(
					$params['sql-column'] => $original,
				),
				array(
					$params['sql-primary-column'] => $params['sql-primary-key'],
				)
			);

			return array(
				'notices' => apply_filters( 'string_locator_post_save_fail_notice', array() ),
			);
		}

		return array(
			'notices' => array(
				array(
					'type'    => 'success',
					'message' => __( 'The database entry has been updated.', 'string-locator' ),
				),
			),
		);
	}

}

new Save();