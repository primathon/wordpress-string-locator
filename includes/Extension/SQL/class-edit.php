<?php

namespace JITS\StringLocator\Extension\SQL;

class Edit {

	public function __construct() {
		add_filter( 'string_locator_view', array( $this, 'sql_edit_page' ) );

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_filter( 'string_locator_editor_fields', array( $this, 'editor_form_fields' ) );
	}

	public function editor_form_fields( $fields ) {
		if ( isset( $_GET['file-type'] ) && 'sql' === $_GET['file-type'] ) {
			$fields = array_merge(
				array(
					'sql-column'         => $_GET['sql-column'],
					'sql-table'          => $_GET['sql-table'],
					'sql-primary-column' => $_GET['sql-primary-column'],
					'sql-primary-type'   => $_GET['sql-primary-type'],
					'sql-primary-key'    => $_GET['sql-primary-key'],
				),
				$fields
			);
		}

		return $fields;
	}

	public function admin_body_class( $class ) {
		if ( isset( $_GET['file-type'] ) && 'sql' === $_GET['file-type'] && current_user_can( 'edit_themes' ) ) {
			$class .= ' file-edit-screen';
		}

		return $class;
	}

	public function sql_edit_page( $include_path ) {
		if ( ! isset( $_GET['file-type'] ) || 'sql' !== $_GET['file-type'] || ! current_user_can( 'edit_themes' ) ) {
			return $include_path;
		}

		// Validate the table name.
		if ( ! isset( $_GET['sql-table'] ) || ! $this->validate_sql_fields( $_GET['sql-table'] ) ) {
			return $include_path;
		}

		// Validate the primary column
		if ( ! isset( $_GET['sql-primary-column'] ) || ! $this->validate_sql_fields( $_GET['sql-primary-column'] ) ) {
			return $include_path;
		}

		// A primary key needs to be provided, this could be anything so we just make sure it is set and not empty.
		if ( ! isset( $_GET['sql-primary-key'] ) || empty( $_GET['sql-primary-key'] ) ) {
			return $include_path;
		}

		return STRING_LOCATOR_PLUGIN_DIR . '/includes/Extension/SQL/views/editor/sql.php';
	}

	public function validate_sql_fields( $field ) {
		return preg_match( '/^[0-9a-zA-Z_]+$/s', $field );
	}

}

new Edit();