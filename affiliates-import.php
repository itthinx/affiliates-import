<?php
/**
 * affiliates-import.php
 *
 * Copyright (c) 2017-2022 "kento" Karim Rahimpur www.itthinx.com
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
 * @author Karim Rahimpur
 * @package affiliates-import
 * @since affiliates-import 1.0.0
 *
 * Plugin Name: Affiliates Import
 * Plugin URI: https://www.itthinx.com/plugins/affiliates-import/
 * Description: Import affiliate accounts with <a href="https://wordpress.org/plugins/affiliates/">Affiliates</a>, <a href="https://www.itthinx.com/shop/affiliates-pro/">Affiliates Pro</a> and <a href="https://www.itthinx.com/shop/affiliates-enterprise/">Affiliates Enterprise</a>.
 * Version: 1.4.0
 * Author: itthinx
 * Author URI: https://www.itthinx.com/
 * Donate-Link: https://www.itthinx.com/shop/
 * License: GPLv3
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_IMPORT_PLUGIN_VERSION', '1.4.0' );

/**
 * Plugin boot.
 */
function affiliates_import_plugins_loaded() {
	if ( class_exists( 'Affiliates' ) ) {
		define( 'AFFILIATES_IMPORT_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'AFFILIATES_IMPORT_LIB', AFFILIATES_IMPORT_DIR . '/lib' );
		define( 'AFFILIATES_IMPORT_PLUGIN_URL', plugins_url( 'affiliates-import' ) );
		require_once AFFILIATES_IMPORT_LIB . '/class-affiliates-import.php';
		require_once AFFILIATES_IMPORT_LIB . '/class-affiliates-import-process.php';
	}
}
add_action( 'plugins_loaded', 'affiliates_import_plugins_loaded' );
