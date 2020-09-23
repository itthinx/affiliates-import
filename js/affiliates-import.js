/**
 * affiliates-import.js
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

var ix_affiliates_import = {
	running          : false,
	importing        : false,
	timeout          : null,
	limit            : null,
	status_importing : 'Importing',
	status_running   : 'Running',
	status_stopped   : 'Stopped',
	update_note      : 'Total'
};

/**
 * Post importer query.
 */
ix_affiliates_import.import = function() {

	if ( typeof args === "undefined" ) {
		args = {};
	}

	var $status = jQuery( "#importer-status" ),
		$update = jQuery( "#importer-update" ),
		$blinker = jQuery( "#importer-blinker" );

	$blinker.addClass( 'blinker' );
	$status.html( '<p>' + ix_affiliates_import.status_importing + '</p>' );
	if ( !ix_affiliates_import.importing ) {
		ix_affiliates_import.importing = true;
		jQuery.ajax( {
				type : 'POST',
				url  : ix_affiliates_import.url,
				data : {
					'action' : 'affiliates_import',
					'nonce' : ix_affiliates_import.nonce
				},
				complete : function() {
					ix_affiliates_import.importing = false;
					$blinker.removeClass( 'blinker' );
				},
				success : function ( data ) {
					if ( typeof data.total !== 'undefined' ) {
						$update.html( '<p>' + ix_affiliates_import.update_note + ' : ' + data.total + '</p>' );
						if ( ix_affiliates_import.limit !== null ) {
							if ( data.total >= ix_affiliates_import.limit ) {
								ix_affiliates_import.stop();
							}
						}
					}
				},
				dataType : 'json'
		} );
	}
};

ix_affiliates_import.start = function( url, nonce ) {
	if ( !ix_affiliates_import.running ) {
		ix_affiliates_import.running = true;
		ix_affiliates_import.url = url;
		ix_affiliates_import.nonce = nonce;
		ix_affiliates_import.exec();
		var $status = jQuery( '#importer-status' );
		$status.html( '<p>' + ix_affiliates_import.status_running + '</p>' );
	}
};

ix_affiliates_import.exec = function() {
	ix_affiliates_import.timeout = setTimeout(
		function() {
			if ( ix_affiliates_import.running ) {
				if ( !ix_affiliates_import.importing ) {
					ix_affiliates_import.generate();
				}
				ix_affiliates_import.exec();
			}
		},
		1000
	);
};

ix_affiliates_import.stop = function() {
	if ( ix_affiliates_import.running ) {
		ix_affiliates_import.running = false;
		clearTimeout( ix_affiliates_import.timeout );
		var $status = jQuery( '#importer-status' );
		$status.html( '<p>' + ix_affiliates_import.status_stopped + '</p>' );
	}
};
