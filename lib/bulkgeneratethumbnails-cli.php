<?php
/**
 * Cli Name:    Bulk Generate Thumbnails CLI
 * Description: Generate Thumbnails by WP-CLI.
 * Version:     1.02
 * Author:      Katsushi Kawamori
 * Author URI:  https://riverforest-wp.info/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package bulkgeneratethumbnails_cli
 */

/*
	Copyright (c) 2024- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

/** ==================================================
 * Bulk Generate Thumbnails command
 *
 * @param array $args  arguments.
 * @param array $assoc_args  optional arguments.
 * @since 1.00
 */
function bulkgeneratethumbnails_cli_command( $args, $assoc_args ) {

	$input_error_message = __( 'Please enter the arguments.', 'bulk-generate-thumbnails' ) . "\n";
	$input_error_message .= __( '1st argument(string) : mail -> Send results via email, nomail -> Do not send results by email', 'bulk-generate-thumbnails' ) . "\n";
	$input_error_message .= __( 'optional argument(int) : --uid=1 : User ID -> Process only specified User ID.', 'bulk-generate-thumbnails' ) . "\n";
	$input_error_message .= __( 'optional argument(int) : --pid=12152 : Post ID -> Process only specified Post ID.', 'bulk-generate-thumbnails' ) . "\n";

	if ( is_array( $args ) && ! empty( $args ) &&
			( 'mail' === $args[0] || 'nomail' === $args[0] ) ) {

		$uid = 0;
		if ( array_key_exists( 'uid', $assoc_args ) ) {
			$uid = intval( $assoc_args['uid'] );
		}
		$pid = 0;
		if ( array_key_exists( 'pid', $assoc_args ) ) {
			$pid = intval( $assoc_args['pid'] );
		}

		$all_users_flag = false;
		if ( 0 === $uid ) {
			$all_users_args = array(
				'role' => 'administrator',
				'number' => 1,
			);
			$users = get_users();
			$value = (array) $users;
			$value2 = array();
			$value2 = array_column( $value, 'ID' );
			$uid = $value2[0];
			$all_users_flag = true;
		}

		if ( 0 < $pid ) {
			$post = get_post( $pid, ARRAY_A );
			if ( wp_attachment_is_image( $pid ) && intval( $post['post_author'] ) === $uid ) {
				if ( file_exists( get_attached_file( $pid ) ) ) {
					do_action( 'bgth_generate', $pid );
					$messages = array();
					list( $message, $messages ) = apply_filters( 'bgth_mail_messages', $messages, $pid, $uid );
					WP_CLI::success( $message );
					if ( 'mail' === $args[0] ) {
						do_action( 'bgth_mail_generate_message', $messages, 1, $uid );
					}
				} else {
					$message = 'ID: ' . $pid . "\n";
					$message .= __( 'Title' ) . ': ' . get_the_title( $pid ) . "\n";
					$message .= __( 'This media exists in the database, but the file does not, so the creation was not performed.', 'bulk-generate-thumbnails' ) . "\n";
					$message .= "\n";
					WP_CLI::warning( $message );
				}
			} else {
				$message = 'ID: ' . $pid . "\n";
				$message .= __( 'This post ID is not an image or is not a post for the specified user ID.', 'bulk-generate-thumbnails' ) . "\n";
				$message .= "\n";
				WP_CLI::warning( $message );
			}
		} else {
			list( $post_ids, $no_file_ids ) = apply_filters( 'bgth_get_allimages', $uid, $all_users_flag );
			if ( ! empty( $post_ids ) ) {
				$messages = array();
				foreach ( $post_ids as $post_id ) {
					do_action( 'bgth_generate', $post_id );
					list( $message, $messages ) = apply_filters( 'bgth_mail_messages', $messages, $post_id, $uid );
					WP_CLI::success( $message );
				}
				if ( ! empty( $no_file_ids ) ) {
					foreach ( $no_file_ids as $post_id ) {
						$message = 'ID: ' . $post_id . "\n";
						$message .= __( 'Title' ) . ': ' . get_the_title( $post_id ) . "\n";
						$message .= __( 'This media exists in the database, but the file does not, so the creation was not performed.', 'bulk-generate-thumbnails' ) . "\n";
						$message .= "\n";
						WP_CLI::warning( $message );
						$messages[] = $message;
					}
				}
				if ( 'mail' === $args[0] ) {
					do_action( 'bgth_mail_generate_message', $messages, count( $post_ids ), $uid );
				}
			}
		}
	} else {
		WP_CLI::error( $input_error_message );
	}
}
WP_CLI::add_command( 'bgth_cli', 'bulkgeneratethumbnails_cli_command' );
