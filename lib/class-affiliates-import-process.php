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
 * Process import requests.
 */
class Affiliates_Import_Process {

	const MAX_FGET_LENGTH = 1024;
	const BASE_DELTA = 1048576;
	const DELTA_F    = 1.62;

	private static $admin_messages = array();

	private static $fields = array();

	private static $notify_users = true;

	/**
	 * Init hook to catch import file generation request.
	 */
	public static function init() {
		//add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Set up and return the fields.
	 *
	 * @return array
	 */
	public static function get_fields() {
		$registration_fields = self::get_affiliates_registration_fields();
		foreach( $registration_fields as $name => $field ) {
			if ( !in_array( $name, self::$fields ) ) {
				self::$fields[] = $name;
			}
		}
		return self::$fields;
	}

	/**
	 * Returns an array with registration fields from Affiliates > Settings > Registration.
	 *
	 * @return array of affiliate registration fields
	 */
	public static function get_affiliates_registration_fields() {
		$registration_fields = array();
		if ( defined( 'AFFILIATES_CORE_LIB' ) ) {
			include_once AFFILIATES_CORE_LIB . '/class-affiliates-settings.php';
			include_once AFFILIATES_CORE_LIB . '/class-affiliates-settings-registration.php';
			if ( class_exists( 'Affiliates_Settings_Registration' ) && method_exists( 'Affiliates_Settings_Registration', 'get_fields' ) ) {
				$registration_fields = Affiliates_Settings_Registration::get_fields();
			}
		}
		return $registration_fields;
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			echo '<div style="padding:1em;margin:1em;border:1px solid #aa0;border-radius:4px;background-color:#ffe;color:#333;">';
			foreach ( self::$admin_messages as $msg ) {
				echo '<p>';
				echo $msg;
				echo '</p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Import affiliates.
	 *
	 * @param boolean $test true if test only
	 */
	public static function import_affiliates( $test = false ) {

		$memory_limit = ini_get( 'memory_limit' );
		preg_match( '/([0-9]+)(.)/', $memory_limit, $matches );
		if ( isset( $matches[1] ) && isset( $matches[2] ) ) {
			$exp = array( 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4, 'P' => 5, 'E' => 6 );
			if ( key_exists( $matches[2], $exp ) ) {
				$memory_limit = intval( $matches[1] ) * pow( 1024, $exp[$matches[2]] );
			}
		}

		$bytes              = memory_get_usage( true );
		$max_execution_time = ini_get( 'max_execution_time' );
		if ( function_exists( 'getrusage' ) ) {
			$resource_usage = getrusage();
			if ( isset( $resource_usage['ru_utime.tv_sec'] ) ) {
				$initial_execution_time = $resource_usage['ru_stime.tv_sec'] + $resource_usage['ru_utime.tv_sec'] + 2; // add 2 as top value for the sum of ru_stime.tv_usec and ru_utime.tv_usec
			}
		}

		self::$notify_users = !empty( $_REQUEST['notify_users'] );

		if ( isset( $_FILES['file'] ) ) {
			if ( $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
				$tmp_name = $_FILES['file']['tmp_name'];
				if ( file_exists( $tmp_name ) ) {
					if ( $h = @fopen( $tmp_name, 'r' ) ) {

						$imported           = 0;
						$updated            = 0;
						$valid              = 0;
						$invalid            = 0;
						$skipped            = 0;
						$line_number        = 0;
						$update_users       = !empty( $_REQUEST['update_users'] );
						$stop_on_errors     = !empty( $_REQUEST['stop_on_errors'] );
						$suppress_warnings  = !empty( $_REQUEST['suppress_warnings'] );
						$errors             = 0;
						$warnings           = 0;
						$skip_limit_checks  = !empty( $_REQUEST['skip_limit_checks'] );
						$limit              = !empty( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : Affiliates_Import::DEFAULT_LIMIT;
						if ( $limit <= 0 ) {
							$limit = Affiliates_Import::DEFAULT_LIMIT;
						}

						$fields = self::get_fields();
						while( !feof( $h ) ) {

							$line  = '';
							$chunk = '';
							while( ( $chunk = fgets( $h, self::MAX_FGET_LENGTH ) ) !== false ) {
								$line .= $chunk;
								if ( ( strpos( $chunk, "\n" ) !== false ) || feof( $h ) ) {
									break;
								}
							}
							if ( strlen( $line ) == 0 ) {
								break;
							}

							$line_number++;

							$skip = false;
							$line = preg_replace( '/\r|\n/', '', $line );
							$line = trim( $line );

							// Skip comments and empty lines.
							if ( strpos( $line, '#' ) === 0 ) {
								continue;
							}
							if ( strlen( $line ) === 0 ) {
								continue;
							}

							// set fields and check for column indicators
							if ( strpos( $line, '@' ) === 0 ) {
								// reset?
								if ( $line == '@' ) {
									$fields = self::get_fields();
									self::$admin_messages[] = sprintf( __( 'Column declaration reset on line %d: <code>%s</code>', 'affiliates-import' ), $line_number, esc_html( implode( ', ', $fields ) ) );
								} else {
									preg_match_all( '/(meta:)?([a-zA-Z0-9_-]+)/', $line, $matches );
									if ( isset( $matches[0] ) && is_array( $matches[0] ) ) {
										$fields = array();
										$i = 0;
										foreach( $matches[0] as $field ) {
											if ( in_array( $field, self::get_fields() ) ) {
												$fields[] = $field;
											} else {
												// We don't handle meta: entries for now.
												// if ( isset( $matches[1] ) && isset( $matches[1][$i] ) && ( $matches[1][$i] == 'meta:' ) ) {
												//	if ( isset( $matches[2] ) && !empty( $matches[2][$i] ) ) {
												//		$meta_key = $matches[2][$i];
												//		$fields[] = 'meta:' . $meta_key;
												//	}
												// }
											}
											$i++;
										}
										self::$admin_messages[] = sprintf( __( 'Column declaration found on line %d, the following column order is assumed: <code>%s</code>', 'affiliates-import' ), $line_number, esc_html( implode( ', ', $fields ) ) );
									}
								}
								continue;
							}

							// data values
							$data = explode( "\t", $line );
							$userdata = array();
							foreach( $fields as $i => $field ) {
								if ( isset( $data[$i] ) ) {
									$value = trim( $data[$i] );
									$userdata[$field] = $value;
								}
							}

							$user_exists = false;

							// email checks
							if ( empty( $userdata['user_email'] ) ) {
								self::$admin_messages[] = sprintf( __( 'Error on line %d, missing email address.', 'affiliates-import' ), $line_number );
								$errors++;
								$skip = true;
							} else if ( !is_email( $userdata['user_email'] ) ) {
								self::$admin_messages[] = sprintf( __( 'Error on line %d, <code>%s</code> is not a valid email address.', 'affiliates-import' ), $line_number, esc_html( $userdata['user_email'] ) );
								$errors++;
								$skip = true;
							} else if ( get_user_by( 'email', $userdata['user_email'] ) ) {
								$user_exists = true;
								if ( !$update_users ) {
									if ( !$suppress_warnings ) {
										self::$admin_messages[] = sprintf( __( 'Warning on line %d, a user with the email address <code>%s</code> already exists.', 'affiliates-import' ), $line_number, esc_html( $userdata['user_email'] ) );
									}
									$warnings++;
									$skip = true;
								}
							}

							// username check
							if ( empty( $userdata['user_login'] ) && !empty( $userdata['user_email'] ) ) {
								$userdata['user_login'] = $userdata['user_email'];
							}
							if ( !empty( $userdata['user_login'] ) && get_user_by( 'login', $userdata['user_login'] ) ) {
								$user_exists = true;
								if ( !$update_users ) {
									if ( !$suppress_warnings ) {
										self::$admin_messages[] = sprintf( __( 'Warning on line %d, the username <code>%s</code> already exists.', 'affiliates-import' ), $line_number, esc_html( $userdata['user_login'] ) );
									}
									$warnings++;
									$skip = true;
								}
							}

							// generate a password for new users
							if ( empty( $userdata['password'] ) && !empty( $userdata['user_pass'] ) ) {
								$userdata['password'] = $userdata['user_pass'];
							}
							if ( empty( $userdata['password'] ) ) {
								if ( !$user_exists ) {
									$userdata['password'] = wp_generate_password();
								} else {
									unset( $userdata['password'] );
								}
							}

							// import or skip
							if ( !$skip ) {
								if ( !$user_exists && !empty( $userdata['user_login'] ) && !empty( $userdata['user_email'] ) ) {
									$valid++;
									if ( !$test ) {
										if ( self::insert_user( $userdata ) ) {
											$imported++;
											if ( ( $imported + $updated ) >= $limit ) {
												break;
											}
										}
									}
								} else if ( $update_users ) {
									$valid++;
									if ( !$test ) {
										if ( self::update_user( $userdata ) ) {
											$updated++;
											if ( ( $imported + $updated ) >= $limit ) {
												break;
											}
										}
									}
								}
							} else {
								$skipped++;
							}
							if ( $stop_on_errors && ( $errors > 0 ) ) {
								break;
							}

							if ( !$skip_limit_checks ) {
								// memory guard
								if ( is_numeric( $memory_limit ) ) {
									$old_bytes = $bytes;
									$bytes     = memory_get_usage( true );
									$remaining = $memory_limit - $bytes;
									$delta = self::BASE_DELTA;
									if ( $bytes > $old_bytes ) {
										$delta += intval( ( $bytes - $old_bytes ) * self::DELTA_F );
									}
									if ( $remaining < $delta ) {
										self::$admin_messages[] = sprintf( __( 'Warning, stopped after line %d to avoid exhausting the available memory for PHP. Consider raising <a href="https://php.net/manual/en/ini.core.php#ini.memory-limit">memory_limit</a> or reducing the number of records imported.', 'affiliates-import' ), $line_number );
										break;
									}
								}

								// time guard
								if ( function_exists( 'getrusage' ) ) {
									$resource_usage = getrusage();
									if ( isset( $resource_usage['ru_utime.tv_sec'] ) ) {
										$execution_time = $resource_usage['ru_stime.tv_sec'] + $resource_usage['ru_utime.tv_sec'] + 2; // add 2 as top value for the sum of ru_stime.tv_usec and ru_utime.tv_usec
										$d = ceil( $execution_time - $initial_execution_time );
										if ( intval( $d * self::DELTA_F ) > ( $max_execution_time - $d ) ) {
											self::$admin_messages[] = sprintf( __( 'Warning, stopped after line %d to avoid reaching the maximum execution time for PHP. Consider raising <a href="https://php.net/manual/en/info.configuration.php#ini.max-execution-time">max_execution_time</a> or reducing the number of records imported.', 'affiliates-import' ), $line_number );
											break;
										}
									}
								}
							}
						}
						@fclose( $h );

						self::$admin_messages[] = sprintf( _n( '1 valid entry has been read.', '%d valid entries have been read.', $valid, 'affiliates-import' ), $valid );
						self::$admin_messages[] = sprintf( _n( '1 entry has been skipped.', '%d entries have been skipped.', $skipped, 'affiliates-import' ), $skipped );
						self::$admin_messages[] = sprintf( _n( '1 user has been imported.', '%d users have been imported.', $imported, 'affiliates-import' ), $imported );
						self::$admin_messages[] = sprintf( _n( '1 user has been updated.', '%d users have been updated.', $updated, 'affiliates-import' ), $updated );

					} else {
						self::$admin_messages[] = __( 'Import failed (error opening temporary file).', 'affiliates-import' );
					}
				}
			} else if ( $_FILES['file']['error'] == UPLOAD_ERR_NO_FILE ) {
				self::$admin_messages[] = __( 'Please choose a file to import from.', 'affiliates-import' );
			}
		}
		
	}

	/**
	 * Create a new user account and add it as an affiliate.
	 *
	 * @param array $userdata
	 */
	private static function insert_user( $userdata = array() ) {

		if ( ! (
			function_exists( 'affiliates_user_is_affiliate' ) &&
			class_exists( 'Affiliates_Registration' ) &&
			method_exists( 'Affiliates_Registration', 'store_affiliate' ) &&
			method_exists( 'Affiliates_Registration', 'create_affiliate' ) &&
			method_exists( 'Affiliates_Registration', 'update_affiliate_user' )
		) ) {
			return null;
		}

		if ( empty( $userdata['user_email'] ) || empty( $userdata['password'] ) ) {
			return null;
		}

		$result = null;

		if ( empty( $userdata['user_login'] ) ) {
			$userdata['user_login'] = $userdata['user_email'];
		}
		if ( empty( $userdata['first_name'] ) ) {
			$userdata['first_name'] = '';
		}
		if ( empty( $userdata['last_name'] ) ) {
			$userdata['last_name'] = '';
		}

		$user_id = Affiliates_Registration::create_affiliate( $userdata );
		if ( !is_wp_error( $user_id ) ) {
			if ( self::$notify_users ) {
				Affiliates_Registration::new_user_notification( $user_id, $userdata['password'] );
			}
			if ( !affiliates_user_is_affiliate( $user_id ) ) {
				add_filter( 'pre_option_aff_notify_admin', array( __CLASS__, 'pre_option_aff_notify_admin' ), 10, 2 );
				if ( $affiliate_id = Affiliates_Registration::store_affiliate( $user_id, $userdata, 'active' ) ) {
					$result = $user_id;
					Affiliates_Registration::update_affiliate_user( $affiliate_id, $userdata );
				}
				remove_filter( 'pre_option_aff_notify_admin', array( __CLASS__, 'pre_option_aff_notify_admin' ), 10 );
			}
		}
		return $result;
	}

	/**
	 * Add an existing user account as an affiliate.
	 *
	 * @param array $userdata
	 */
	private static function update_user( $userdata = array() ) {

		if ( ! (
			function_exists( 'affiliates_user_is_affiliate' ) &&
			class_exists( 'Affiliates_Registration' ) &&
			method_exists( 'Affiliates_Registration', 'store_affiliate' ) &&
			method_exists( 'Affiliates_Registration', 'update_affiliate_user' )
		) ) {
			return null;
		}

		$result = null;

		$user = false;
		if ( !empty( $userdata['user_email'] ) ) {
			$user = get_user_by( 'email', $userdata['user_email'] );
		}
		if ( !$user && !empty( $userdata['user_login'] ) ) {
			$user = get_user_by( 'login', $userdata['user_login'] );
		}
		if ( $user !== false ) {
			$user_id = $user->ID;
			if ( !affiliates_user_is_affiliate( $user_id ) ) {
				add_filter( 'pre_option_aff_notify_admin', array( __CLASS__, 'pre_option_aff_notify_admin' ), 10, 2 );
				if ( $affiliate_id = Affiliates_Registration::store_affiliate( $user_id, $userdata, 'active' ) ) {
					$result = $user_id;
					Affiliates_Registration::update_affiliate_user( $affiliate_id, $userdata );
				}
				remove_filter( 'pre_option_aff_notify_admin', array( __CLASS__, 'pre_option_aff_notify_admin' ), 10 );
			}
		}
		return $result;
	}

	/**
	 * Filters the aff_notify_admin option to avoid administrator notifications on imported affiliates.
	 *
	 * @return null (can't return false as that won't take any effect)
	 */
	public static function pre_option_aff_notify_admin( $value, $option ) {
		return null;
	}
}
Affiliates_Import_Process::init();
