<?php
/**
 * class-affiliates-import-admin.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-import
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Affiliates Import admin sections.
 */
class Affiliates_Import_Admin {

	const NONCE               = 'affiliates_import_admin_nonce';
	const NONCE_IMPORT_ACTION = 'affiliates_import_import';
	const SET_ADMIN_OPTIONS   = 'set_admin_options';
	const DEFAULT_PER_RUN     = 10;
	const REQUEST_IMPORT      = 'import-affiliates';

	/**
	 * Initialization action on WordPress init.
	 */
	public static function init() {
		if ( current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			add_action( 'affiliates_admin_menu', array( __CLASS__, 'affiliates_admin_menu' ) );
			add_action( 'init', array( __CLASS__, 'wp_init' ) );
		}
	}

	/**
	 * Adds the Import submenu item to the Affiliates menu.
	 */
	public static function affiliates_admin_menu() {
		$page = add_submenu_page(
			'affiliates-admin',
			__( 'Import', 'affiliates-import' ),
			__( 'Import', 'affiliates-import' ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-admin-import',
			array( __CLASS__, 'affiliates_admin_import' )
		);
		$pages[] = $page;
		add_action( 'admin_print_styles-' . $page, 'affiliates_admin_print_styles' );
		add_action( 'admin_print_scripts-' . $page, 'affiliates_admin_print_scripts' );
		//add_action( 'load-' . $page, array( __CLASS__, 'load' ) );
	}

	/**
	 * Registers the importer script and style.
	 * Currently not used.
	 */
	public static function load() {
		wp_register_script( 'affiliates-import', POST_GENERATOR_PLUGIN_URL . '/js/affiliates-import.js', array( 'jquery' ), AFFILIATES_IMPORT_PLUGIN_VERSION, true );
		wp_register_style( 'affiliates-import', POST_GENERATOR_PLUGIN_URL . '/css/affiliates-import.css', array(), AFFILIATES_IMPORT_PLUGIN_VERSION );
	}

	/**
	 * Process user import request
	 */
	public static function wp_init() {
		// @todo needs work - currently not used
		// AJAX request handler.
// 		if (
// 			isset( $_REQUEST['importer'] ) &&
// 			wp_verify_nonce( $_REQUEST['importer'], 'affiliates-import-js' )
// 		) {
// 			$options = get_option( Affiliates_Import::PLUGIN_OPTIONS, array() );
// 			$per_run = isset( $options['per-run'] ) ? intval( $options['per-run'] ) : self::DEFAULT_PER_RUN;
// 			$n = self::run( $per_run );
// 			$result = array( 'total' => $n );
// 			echo json_encode( $result );
// 			exit;
// 		}
		if ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] === self::REQUEST_IMPORT ) ) {
			if ( wp_verify_nonce( $_REQUEST[self::NONCE], self::NONCE_IMPORT_ACTION ) ) {
				Affiliates_Import_Process::import_affiliates( !empty( $_REQUEST['test'] ) );
			}
		}
	}

	/**
	 * Affiliates Import : admin section.
	 */
	public static function affiliates_admin_import() {
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'affiliates-import' ) );
		}
		$options = get_option( Affiliates_Import::PLUGIN_OPTIONS , array() );
		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], self::SET_ADMIN_OPTIONS ) ) {
				// currently nothing needed here
			}
			update_option( Affiliates_Import::PLUGIN_OPTIONS, $options );
		}

		// css
		echo '<style type="text/css">';
		echo 'div.field { padding: 0 1em 1em 0; }';
		echo 'div.field span.label { display: inline-block; width: 20%; }';
		echo 'div.field span.description { display: block; }';
		echo 'div.buttons { padding-top: 1em; }';
		echo '</style>';

		echo '<div>';
		echo '<h2>';
		esc_html_e( 'Import Affiliate Accounts', 'affiliates-import' );
		echo '</h2>';
		echo '</div>';

		echo '<div class="manage" style="padding:2em;margin-right:1em;">';

		echo '<div>';
		echo '<form enctype="multipart/form-data" name="import-users" method="post" action="">';
		echo '<div>';

		echo '<p>';
		echo '<label>';
		_e( 'Import users from file', 'affiliates-import' );
		echo '<input type="file" name="file" />';
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="test" value="1" ' . ( !empty( $_POST['test'] ) ? ' checked="checked" ' : '' ) .'" />';
		_e( 'Test only, no users are imported.', 'affiliates-import');
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="update_users" value="1" ' . ( !empty( $_POST['update_users'] ) ? ' checked="checked" ' : '' ) . '" />';
		_e( 'Update existing users (existing accounts are added as affiliates).', 'affiliates-import' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="suppress_warnings" value="1" ' . ( !empty( $_POST['suppress_warnings'] ) ? ' checked="checked" ' : '' ) . '" />';
		_e( 'Suppress warnings.', 'affiliates-import' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="stop_on_errors" value="1" ' . ( !empty( $_POST['stop_on_errors'] ) ? ' checked="checked" ' : '' ) . '" />';
		_e( 'Stop on errors.', 'affiliates-import' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="skip_limit_checks" value="1" ' . ( !empty( $_POST['skip_limit_checks'] ) ? ' checked="checked" ' : '' ) . '" />';
		_e( 'Skip PHP memory and execution time checks.', 'affiliates-import' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="notify_users" value="1" ' . ( ( empty( $_POST['action'] ) || !empty( $_POST['notify_users'] ) ) ? ' checked="checked" ' : '' ) . '" />';
		_e( 'Send new users their password by email.', 'affiliates-import' );
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label>';

		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : Affiliates_Import::DEFAULT_LIMIT;
		if ( $limit <= 0 ) {
			$limit = Affiliates_Import::DEFAULT_LIMIT;
		}
		printf( __( 'Import up to <input style="width:5em;text-align:right" type="text" name="limit" value="%d" /> users.', 'affiliates-import' ), $limit );

		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo wp_nonce_field( self::NONCE_IMPORT_ACTION, self::NONCE, true, false );
		echo '<input class="button-primary" type="submit" name="submit" value="' . esc_attr__( 'Import', 'affiliates-import' ) . '"/>';
		echo '</p>';

		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::REQUEST_IMPORT ) );

		echo '</div>';
		echo '</form>';
		echo '</div>';

		echo '</div>'; // .manage

		affiliates_footer();

	}

}
Affiliates_Import_Admin::init();
