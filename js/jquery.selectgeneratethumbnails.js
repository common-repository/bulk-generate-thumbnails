/**
 * Bulk Generate Thumbnails
 *
 * @package    Bulk Generate Thumbnails
 * @subpackage jquery.selectgeneratethumbnails.js
/*
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

jQuery(
	function ($) {

		/* Control of the Enter key */
		$( 'input[type!="submit"][type!="button"]' ).keypress(
			function (e) {
				if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
					return false;
				} else {
					return true;
				}
			}
		);

		/* Ajax for Register */
		var selectgeneratethumbnails_defer = $.Deferred().resolve();
		$( '#selectgeneratethumbnails_ajax_update1, #selectgeneratethumbnails_ajax_update2' ).click(
			function () {

				var ids = new Array();
				var form_names = $( "#selectgeneratethumbnails_forms" ).serializeArray();
				var j = 0;
				$.each(
					form_names,
					function (i) {
						if ( form_names[i].name.indexOf( "ids" ) != -1 ) {
							ids[j] = form_names[i].value;
							j = j + 1;
						}
					}
				);

				if ( 0 == ids.length ) {
					return;
				}

				$( "#bulkgeneratethumbnails-loading-container" ).empty();

				$( "#bulkgeneratethumbnails-loading-container" ).append( "<div id=\"bulkgeneratethumbnails-update-progress\"><progress value=\"0\" max=\"100\"></progress> 0%</div><button type=\"button\" id=\"bulkgeneratethumbnails_ajax_stop\">" + bulkgeneratethumbnails_text.stop_button + "</button>" );
				$( "#bulkgeneratethumbnails-loading-container" ).append( "<div id=\"bulkgeneratethumbnails-update-result\"></div>" );
				var update_continue = true;
				/* Stop button */
				$( "#bulkgeneratethumbnails_ajax_stop" ).click(
					function () {
						update_continue = false;
						$( "#bulkgeneratethumbnails_ajax_stop" ).text( bulkgeneratethumbnails_text.stop_message );
					}
				);

				var count = 0;
				var success_count = 0;
				var error_count = 0;
				var error_update = "";

				$.each(
					ids,
					function (i) {
						var j = i;
						selectgeneratethumbnails_defer = selectgeneratethumbnails_defer.then(
							function () {
								if ( update_continue == true ) {
									return $.ajax(
										{
											type: 'POST',
											cache : false,
											url: bulkgeneratethumbnails.ajax_url,
											data: {
												'action': bulkgeneratethumbnails.action,
												'nonce': bulkgeneratethumbnails.nonce,
												'maxcount': ids.length,
												'id': ids[j],
											}
										}
									).then(
										function (result) {
											count += 1;
											success_count += 1;
											$( "#bulkgeneratethumbnails-update-result" ).append( result );
											$( "#bulkgeneratethumbnails-update-progress" ).empty();
											var progressper = Math.round( ( count / ids.length ) * 100 );
											$( "#bulkgeneratethumbnails-update-progress" ).append( "<progress value=\"" + progressper + "\" max=\"100\"></progress> " + progressper + "%" );
											if ( count == ids.length || update_continue == false ) {
												$.ajax(
													{
														type: 'POST',
														url: bulkgeneratethumbnails_mes.ajax_url,
														data: {
															'action': bulkgeneratethumbnails_mes.action,
															'nonce': bulkgeneratethumbnails_mes.nonce,
															'error_count': error_count,
															'error_update': error_update,
															'success_count': success_count,
														}
													}
												).done(
													function (result) {
														$( "#bulkgeneratethumbnails-update-progress" ).empty();
														$( "#bulkgeneratethumbnails-update-progress" ).append( result );
														$( "#bulkgeneratethumbnails_ajax_stop" ).hide();
													}
												);
											}
										}
									).fail(
										function ( jqXHR, textStatus, errorThrown) {
											error_count += 1;
											error_update += "<div>" + ids[j] + ": error -> status " + jqXHR.status + ' ' + textStatus.status + "</div>";
										}
									);
								}
							}
						);
					}
				);
				return false;
			}
		);

	}
);
