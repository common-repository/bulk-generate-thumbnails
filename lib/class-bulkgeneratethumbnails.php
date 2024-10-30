<?php
/**
 * Bulk Generate Thumbnails
 *
 * @package    Bulk Generate Thumbnails
 * @subpackage BulkGenerateThumbnails Main function
/*  Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$bulkgeneratethumbnails = new BulkGenerateThumbnails();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class BulkGenerateThumbnails {

	/** ==================================================
	 * URL
	 *
	 * @var $upload_url URL.
	 */
	private $upload_url;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$wp_uploads = wp_upload_dir();
		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$relationalpath = substr( $wp_uploads['baseurl'], $relation_path_true );
			$basepath       = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url     = $this->realurl( $basepath, $relationalpath );
		} else {
			$upload_url = $wp_uploads['baseurl'];
		}
		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}
		$this->upload_url = untrailingslashit( $upload_url );

		/* Original hook */
		add_filter( 'bgth_get_allimages', array( $this, 'get_allimages' ), 10, 2 );
		add_filter( 'bgth_mail_messages', array( $this, 'mail_messages' ), 10, 3 );
		add_action( 'bgth_mail_generate_message', array( $this, 'mail_generate_message' ), 10, 3 );
		add_action( 'bgth_generate', array( $this, 'generate' ), 10, 1 );
		add_action( 'bgth_filter_form', array( $this, 'filter_form' ), 10, 1 );
		add_action( 'bgth_per_page_set', array( $this, 'per_page_set' ), 10, 1 );
		add_filter( 'bgth_use_mime_types', array( $this, 'use_mime_types' ), 10, 1 );

		/* Ajax */
		$action1 = 'bulkgeneratethumbnails-ajax-action';
		$action2 = 'bulkgeneratethumbnails_message';
		add_action( 'wp_ajax_' . $action1, array( $this, 'bulkgeneratethumbnails_update_callback' ) );
		add_action( 'wp_ajax_' . $action2, array( $this, 'bulkgeneratethumbnails_message_callback' ) );
	}

	/** ==================================================
	 * IDs Callback
	 *
	 * @since 1.00
	 */
	public function bulkgeneratethumbnails_update_callback() {

		$action1 = 'bulkgeneratethumbnails-ajax-action';
		if ( check_ajax_referer( $action1, 'nonce', false ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				if ( ! empty( $_POST['id'] ) ) {
					$id = absint( $_POST['id'] );
				} else {
					return;
				}
				if ( ! wp_get_attachment_url( $id ) ) {
					$error_string = __( 'No media!', 'bulk-generate-thumbnails' );
					$output_html = '<div>ID: ' . $id . ' <span style="color: red;">' . $error_string . '</span></div>';
				} else {
					if ( get_user_option( 'bgth_mail_send', get_current_user_id() ) ) {
						$post = get_post( $id, ARRAY_A );
						$user_id = intval( $post['post_author'] );
						if ( ! get_user_option( 'bgth_messages', get_current_user_id() ) ) {
							$messages = array();
						} else {
							$messages = get_user_option( 'bgth_messages', get_current_user_id() );
						}
						list( $message, $messages ) = $this->mail_messages( $messages, $id, $user_id );
						update_user_option( get_current_user_id(), 'bgth_messages', $messages );
					}
					$output_html = $this->output_html( $id );
				}

				header( 'Content-type: text/html; charset=UTF-8' );
				$allowed_output_html = array(
					'a'   => array(
						'href' => array(),
						'target' => array(),
						'rel' => array(),
						'style' => array(),
					),
					'img'   => array(
						'src' => array(),
						'width' => array(),
						'height' => array(),
						'style' => array(),
					),
					'div'   => array(
						'style' => array(),
						'class' => array(),
					),
					'font'   => array(
						'color' => array(),
					),
					'ul' => array(),
					'li' => array(),
					'span'   => array(
						'class' => array(),
						'style' => array(),
					),
				);
				echo wp_kses( $output_html, $allowed_output_html );
			}
		} else {
			status_header( '403' );
			echo 'Forbidden';
		}

		wp_die();
	}

	/** ==================================================
	 * Messages Callback
	 *
	 * @since 1.00
	 */
	public function bulkgeneratethumbnails_message_callback() {

		$action2 = 'bulkgeneratethumbnails_message';
		if ( check_ajax_referer( $action2, 'nonce', false ) ) {
			$error_count = 0;
			$error_update = null;
			$success_count = 0;
			if ( ! empty( $_POST['error_count'] ) ) {
				$error_count = absint( $_POST['error_count'] );
			}
			if ( ! empty( $_POST['error_update'] ) ) {
				$error_update = sanitize_text_field( wp_unslash( $_POST['error_update'] ) );
			}
			if ( ! empty( $_POST['success_count'] ) ) {
				$success_count = absint( $_POST['success_count'] );
			}

			$back_html = '<strong><a style="text-decoration: none;" href="' . admin_url( 'upload.php?page=bulkgeneratethumbnails' ) . '">' . __( 'Back' ) . '</a></strong>';

			$output_html = null;
			if ( $error_count > 0 ) {
				/* translators: error message %1$d: media count %2$s: back link */
				$error_message = sprintf( __( 'Errored to the generation of %1$d medias.', 'bulk-generate-thumbnails' ), $error_count, $back_html );
				$output_html .= '<div class="notice notice-error is-dismissible"><ul><li><div>' . $error_message . '</div>' . $error_update . '</li></ul></div>';
			}
			if ( $success_count > 0 ) {
				/* translators: success message %1$d: media count %2$s: back link */
				$success_message = sprintf( __( 'Succeeded to the generation of %1$d medias for Media Library. %2$s', 'bulk-generate-thumbnails' ), $success_count, $back_html );
				$output_html .= '<div class="notice notice-success is-dismissible"><ul><li><div>' . $success_message . '</li></ul></div>';
			}

			if ( get_user_option( 'bgth_mail_send', get_current_user_id() ) ) {
				$messages = get_user_option( 'bgth_messages', get_current_user_id() );
				do_action( 'bgth_mail_generate_message', $messages, $success_count, get_current_user_id() );
				delete_user_option( get_current_user_id(), 'bgth_messages' );
			}

			header( 'Content-type: text/html; charset=UTF-8' );
			$allowed_output_html = array(
				'div' => array(
					'class' => array(),
				),
				'ul' => array(),
				'li' => array(),
				'strong' => array(),
				'a' => array(
					'style' => array(),
					'href' => array(),
				),
			);
			echo wp_kses( $output_html, $allowed_output_html );
		}

		wp_die();
	}

	/** ==================================================
	 * Generate
	 *
	 * @param array $attach_id  attach_id.
	 * @return array $metadata
	 * @since 1.10
	 */
	public function generate( $attach_id ) {

		include_once ABSPATH . 'wp-admin/includes/image.php';

		$metadata_org = wp_get_attachment_metadata( $attach_id );
		if ( is_array( $metadata_org ) && array_key_exists( 'original_image', $metadata_org ) ) {
			$file = wp_get_original_image_path( $attach_id );
		} else {
			$file = get_attached_file( $attach_id );
		}
		$metadata = wp_generate_attachment_metadata( $attach_id, $file );
		if ( is_array( $metadata_org ) && is_array( $metadata ) &&
			! array_key_exists( 'original_image', $metadata ) && array_key_exists( 'original_image', $metadata_org ) ) {
			update_post_meta( $attach_id, '_wp_attached_file', $metadata['file'] );
		}
		wp_update_attachment_metadata( $attach_id, $metadata );

		return $metadata;
	}

	/** ==================================================
	 * Output html for Ajax
	 *
	 * @param array $attach_id  attach_id.
	 * @since 1.00
	 */
	private function output_html( $attach_id ) {

		/* Generate thumbnails */
		$metadata = $this->generate( $attach_id );
		/* Thumbnail urls */
		list( $image_thumbnail, $imagethumburls ) = $this->thumbnail_urls( $attach_id, $metadata, $this->upload_url );
		/* Output datas*/
		list( $attachment_link, $attachment_url, $filename, $original_image_url, $original_filename, $mime_type, $stamptime, $file_size ) = $this->output_datas( $attach_id, $metadata );

		$post = get_post( $attach_id, ARRAY_A );
		$user_id = intval( $post['post_author'] );

		$output_html = '<div style="border-bottom: 1px solid; padding-top: 5px; padding-bottom: 5px;">';
		$output_html .= '<img width="40" height="40" src="' . $image_thumbnail . '" style="float: left; margin: 5px;">';
		$output_html .= '<div style="overflow: hidden;">';
		$output_html .= '<div>ID: ' . $attach_id . '</div>';
		$output_html .= '<div>' . __( 'User' ) . ': ' . get_user_meta( $user_id, 'nickname', true ) . '</div>';
		$output_html .= '<div>' . __( 'Title' ) . ': ' . get_the_title( $attach_id ) . '</div>';
		$output_html .= '<div>' . __( 'Permalink:' ) . ' <a href="' . $attachment_link . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $attachment_link . '</a></div>';
		$output_html .= '<div>URL: <a href="' . $attachment_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $attachment_url . '</a></div>';
		$output_html .= '<div>' . __( 'File name:' ) . ' ' . $filename . '</div>';
		if ( ! empty( $original_image_url ) ) {
			$output_html .= '<div>' . __( 'Original URL', 'bulk-generate-thumbnails' ) . ': <a href="' . $original_image_url . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $original_image_url . '</a></div>';
			$output_html .= '<div>' . __( 'Original File name', 'bulk-generate-thumbnails' ) . ': ' . $original_filename . '</div>';
		}
		$output_html .= '<div>' . __( 'Date/Time' ) . ': ' . $stamptime . '</div>';
		$output_html .= '<div>' . __( 'File type:' ) . ' ' . $mime_type . '</div>';
		$output_html .= '<div>' . __( 'File size:' ) . ' ' . $file_size . '</div>';
		if ( ! empty( $imagethumburls ) ) {
			$output_html .= '<div>' . __( 'Images' ) . ': ';
			foreach ( $imagethumburls as $thumbsize => $imagethumburl ) {
				$output_html .= '[<a href="' . $imagethumburl . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; word-break: break-all;">' . $thumbsize . '</a>]';
			}
			$output_html .= '</div>';
		}
		$output_html .= '</div></div>';

		return $output_html;
	}

	/** ==================================================
	 * Thumbnail urls
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param string $upload_url  upload_url.
	 * @return array $image_thumbnail(string), $imagethumburls(array)
	 * @since 1.00
	 */
	private function thumbnail_urls( $attach_id, $metadata, $upload_url ) {

		$image_attr_thumbnail = wp_get_attachment_image_src( $attach_id, 'thumbnail', true );
		$image_thumbnail = $image_attr_thumbnail[0];

		$imagethumburls = array();
		if ( ! empty( $metadata ) && array_key_exists( 'sizes', $metadata ) ) {
			$thumbnails  = $metadata['sizes'];
			$path_file  = get_post_meta( $attach_id, '_wp_attached_file', true );
			$filename   = wp_basename( $path_file );
			$media_path = str_replace( $filename, '', $path_file );
			$media_url  = $upload_url . '/' . $media_path;
			foreach ( $thumbnails as $key => $key2 ) {
				$imagethumburls[ $key ] = $media_url . $key2['file'];
			}
		}

		return array( $image_thumbnail, $imagethumburls );
	}

	/** ==================================================
	 * Output datas
	 *
	 * @param int   $attach_id  attach_id.
	 * @param array $metadata  metadata.
	 * @return array (string) $attachment_link, $attachment_url, $filename, $original_image_url, $original_filename, $mime_type(string), $stamptime, $file_size
	 * @since 1.00
	 */
	private function output_datas( $attach_id, $metadata ) {

		$attachment_link = get_attachment_link( $attach_id );
		$attachment_url = wp_get_attachment_url( $attach_id );
		$filename = wp_basename( $attachment_url );

		if ( ! empty( $metadata ) && array_key_exists( 'original_image', $metadata ) && ! empty( $metadata['original_image'] ) ) {
			$original_image_url = wp_get_original_image_url( $attach_id );
			$original_filename = wp_basename( $original_image_url );
		} else {
			$original_image_url = null;
			$original_filename = null;
		}

		$mime_type = get_post_mime_type( $attach_id );
		$stamptime = get_the_time( 'Y-n-j ', $attach_id ) . get_the_time( 'G:i:s', $attach_id );

		if ( ! empty( $metadata ) && array_key_exists( 'filesize', $metadata ) && ! empty( $metadata['filesize'] ) ) {
			$file_size = $metadata['filesize'];
		} else {
			$file_size = @filesize( get_attached_file( $attach_id ) );
		}
		if ( ! $file_size ) {
			$file_size = '<font color="red">' . __( 'Could not retrieve.', 'bulk-generate-thumbnails' ) . '</font>';
		} else {
			$file_size = size_format( $file_size );
		}

		return array( $attachment_link, $attachment_url, $filename, $original_image_url, $original_filename, $mime_type, $stamptime, $file_size );
	}

	/** ==================================================
	 * Get All Images
	 *
	 * @param int  $uid  user ID.
	 * @param bool $all  get all users images.
	 * @since 1.10
	 */
	public function get_allimages( $uid, $all ) {

		$mime_types = $this->use_mime_types( $uid );
		$mime_type = implode( ',', $mime_types );

		if ( $all ) {
			/* All users */
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => $mime_type,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);
		} else {
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'author'         => $uid,
				'post_mime_type' => $mime_type,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);
		}

		$posts = get_posts( $args );

		$post_ids = array();
		$no_file_ids = array();
		foreach ( $posts as $post ) {
			if ( file_exists( get_attached_file( $post->ID ) ) ) {
				$post_ids[] = $post->ID;
			} else {
				$no_file_ids[] = $post->ID;
			}
		}

		return array( $post_ids, $no_file_ids );
	}

	/** ==================================================
	 * Mail messages hook
	 *
	 * @param array $messages  messages.
	 * @param int   $attach_id  attach ID.
	 * @param int   $user_id  user ID.
	 *
	 * @since 3.00
	 */
	public function mail_messages( $messages, $attach_id, $user_id ) {

		$metadata = wp_get_attachment_metadata( $attach_id );

		$message = null;
		if ( $metadata ) {
			/* Thumbnail urls */
			list( $image_thumbnail, $imagethumburls ) = $this->thumbnail_urls( $attach_id, $metadata, $this->upload_url );
			/* Output datas*/
			list( $attachment_link, $attachment_url, $filename, $original_image_url, $original_filename, $mime_type, $stamptime, $file_size ) = $this->output_datas( $attach_id, $metadata );

			$message = "\n";
			$message .= 'ID: ' . $attach_id . "\n";
			$message .= __( 'User' ) . ': ' . get_user_meta( $user_id, 'nickname', true ) . "\n";
			$message .= __( 'Title' ) . ': ' . get_the_title( $attach_id ) . "\n";
			$message .= __( 'Permalink:' ) . ' ' . $attachment_link . "\n";
			$message .= 'URL: ' . $attachment_url . "\n";
			$message .= __( 'File name:' ) . ' ' . $filename . "\n";
			if ( ! empty( $original_image_url ) ) {
				$message .= __( 'Original URL:', 'plus-webp' ) . ' ' . $original_image_url . "\n";
				$message .= __( 'Original File name:', 'plus-webp' ) . ' ' . $original_filename . "\n";
			}
			$message .= __( 'Date/Time' ) . ': ' . $stamptime . "\n";
			$message .= __( 'File size:' ) . ' ' . $file_size . "\n";
			if ( ! empty( $imagethumburls ) ) {
				foreach ( $imagethumburls as $thumbsize => $imagethumburl ) {
					$message .= $thumbsize . ': ' . $imagethumburl . "\n";
				}
			}
			$message .= "\n";
			$messages[] = $message;
		}

		return array( $message, $messages );
	}

	/** ==================================================
	 * Mail sent for Generate Messages
	 *
	 * @param array $messages  mail messages.
	 * @param int   $count  convert count.
	 * @param int   $uid  user ID.
	 * @since 3.00
	 */
	public function mail_generate_message( $messages, $count, $uid ) {

		if ( function_exists( 'wp_date' ) ) {
			$now_date_time = wp_date( 'Y-m-d H:i:s' );
		} else {
			$now_date_time = date_i18n( 'Y-m-d H:i:s' );
		}
		/* translators: Date and Time */
		$message_head = sprintf( __( 'Bulk Generate Thumbnails : %s', 'bulk-generate-thumbnails' ), $now_date_time ) . "\r\n\r\n";
		/* translators: Generated count */
		$message_head .= sprintf( __( 'Thumbnails generated %d.', 'bulk-generate-thumbnails' ), $count ) . "\r\n\r\n";

		$user_data = get_userdata( $uid );
		/* translators: blogname for subject */
		$subject = sprintf( __( '[%s] Thumbnails generate', 'bulk-generate-thumbnails' ), get_option( 'blogname' ) );
		wp_mail( $user_data->user_email, $subject, $message_head . implode( $messages ) );
	}

	/** ==================================================
	 * Filter form
	 *
	 * @param int $uid  current user id.
	 * @since 2.00
	 */
	public function filter_form( $uid ) {

		$scriptname = admin_url( 'upload.php?page=bulkgeneratethumbnails' );

		?>
		<div style="margin: 0px 0px 0px 120px; padding: 5px;">
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
		<?php
		wp_nonce_field( 'bgth_filter', 'bulk_generate_thumbnails_filter' );

		$users = get_users(
			array(
				'orderby' => 'nicename',
				'order' => 'ASC',
			)
		);

		if ( current_user_can( 'manage_options' ) ) {
			$user_filter = get_user_option( 'bgth_filter_user', $uid );
			?>
			<select name="user_id">
			<?php
			$selected_user = false;
			foreach ( $users as $user ) {
				if ( user_can( $user->ID, 'upload_files' ) ) {
					if ( $user_filter == $user->ID ) {
						?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" selected><?php echo esc_html( $user->display_name ); ?></option>
						<?php
						$selected_user = true;
					} else {
						?>
						<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
						<?php
					}
				}
			}
			if ( ! $selected_user ) {
				?>
				<option value="" selected><?php esc_html_e( 'All users', 'bulk-generate-thumbnails' ); ?></option>
				<?php
			} else {
				?>
				<option value=""><?php esc_html_e( 'All users', 'bulk-generate-thumbnails' ); ?></option>
				<?php
			}
			?>
			</select>
			<?php
		}

		$mimes = $this->use_mime_types( $uid );
		$mime_filter = get_user_option( 'bgth_filter_mime_type', get_current_user_id() );
		?>
		<select name="mime_type">
		<?php
		$selected_mime_type = false;
		foreach ( $mimes as $mime ) {
			if ( $mime_filter === $mime ) {
				?>
				<option value="<?php echo esc_attr( $mime ); ?>" selected><?php echo esc_html( $mime ); ?></option>
				<?php
				$selected_mime_type = true;
			} else {
				?>
				<option value="<?php echo esc_attr( $mime ); ?>"><?php echo esc_html( $mime ); ?></option>
				<?php
			}
		}
		if ( ! $selected_mime_type ) {
			?>
			<option value="" selected><?php esc_html_e( 'All MIME type', 'bulk-generate-thumbnails' ); ?></option>
			<?php
		} else {
			?>
			<option value=""><?php esc_html_e( 'All MIME type', 'bulk-generate-thumbnails' ); ?></option>
			<?php
		}
		?>
		</select>
		<?php

		global $wpdb;
		$attachments = $wpdb->get_col(
			"
				SELECT	ID
				FROM	{$wpdb->prefix}posts
				WHERE	post_type = 'attachment'
				ORDER BY post_date DESC
			"
		);

		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $pid ) {
				$year = get_the_time( 'Y', $pid );
				$month = get_the_time( 'F', $pid );
				/* translators: month year for media archive */
				$year_month = sprintf( __( '%1$s %2$s', 'bulk-generate-thumbnails' ), $month, $year );
				$archive_list[ $year_month ][] = $pid;
			}
			$monthly_filter = get_user_option( 'bgth_filter_monthly', get_current_user_id() );
			?>
			<select name="monthly">
			<?php
			$selected_monthly = false;
			foreach ( $archive_list as $key => $value ) {
				$pid_csv = implode( ',', $value );
				if ( $value == $monthly_filter ) {
					?>
					<option value="<?php echo esc_attr( $pid_csv ); ?>" selected><?php echo esc_html( $key ); ?></option>
					<?php
					$selected_monthly = true;
				} else {
					?>
					<option value="<?php echo esc_attr( $pid_csv ); ?>"><?php echo esc_html( $key ); ?></option>
					<?php
				}
			}
			if ( ! $selected_monthly ) {
				?>
				<option value="" selected><?php esc_html_e( 'All dates' ); ?></option>
				<?php
			} else {
				?>
				<option value=""><?php esc_html_e( 'All dates' ); ?></option>
				<?php
			}
			?>
			</select>
			<?php
		}

		$search_text = get_user_option( 'bgth_search_text', $uid );
		if ( ! $search_text ) {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="" placeholder="<?php esc_attr_e( 'Search' ); ?>">
			<?php
		} else {
			?>
			<input style="vertical-align: middle;" name="search_text" type="text" value="<?php echo esc_attr( $search_text ); ?>">
			<?php
		}

		submit_button( __( 'Filter' ), 'large', 'bulk-generate-thumbnails-filter', false );
		?>
		</form>
		</div>
		<?php
	}

	/** ==================================================
	 * Use Mime Types
	 *
	 * @param int $uid  current user id.
	 * @since 2.00
	 */
	public function use_mime_types( $uid ) {

		$mimes = array();
		$all_mimes = get_allowed_mime_types( $uid );
		foreach ( $all_mimes as $type => $mime ) {
			$types = explode( '|', $type );
			foreach ( $types as $value ) {
				if ( 'image' === wp_ext2type( $value ) || 'pdf' === $value ) {
					$mimes[] = $mime;
				}
			}
		}
		$mimes = array_unique( $mimes );
		$mimes = array_values( $mimes );

		return $mimes;
	}

	/** ==================================================
	 * Per page input form
	 *
	 * @param int $uid  current user id.
	 * @since 2.00
	 */
	public function per_page_set( $uid ) {

		?>
		<div style="margin: 0px; text-align: right;">
			<input name="mail_send" type="checkbox" value="1" <?php checked( get_user_option( 'bgth_mail_send', $uid ), true ); ?> form="bulkgeneratethumbnails_settings"><?php esc_html_e( 'Send result email', 'bulk-generate-thumbnails' ); ?>&nbsp;&nbsp;|&nbsp;&nbsp;
			<?php esc_html_e( 'Number of items per page:' ); ?><input type="number" step="1" min="1" max="9999" style="width: 80px;" name="per_page" value="<?php echo esc_attr( get_user_option( 'bgth_per_page', $uid ) ); ?>" form="bulkgeneratethumbnails_settings" />
			<?php submit_button( __( 'Change' ), 'large', 'option_change', false, array( 'form' => 'bulkgeneratethumbnails_settings' ) ); ?>
		</div>
		<?php
	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  1.00
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}
	}
}
