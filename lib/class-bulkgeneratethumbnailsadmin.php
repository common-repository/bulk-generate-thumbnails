<?php
/**
 * Bulk Generate Thumbnails
 *
 * @package    Bulk Generate Thumbnails
 * @subpackage BulkGenerateThumbnailsAdmin Management screen
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

$bulkgeneratethumbnailsadmin = new BulkGenerateThumbnailsAdmin();

/** ==================================================
 * Management screen
 */
class BulkGenerateThumbnailsAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

		if ( ! class_exists( 'TT_BulkGenerateThumbnails_List_Table' ) ) {
			require_once __DIR__ . '/class-tt-bulkgeneratethumbnails-list-table.php';
		}
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param array  $links  links array.
	 * @param string $file  file.
	 * @return array $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'bulk-generate-thumbnails/bulkgeneratethumbnails.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'upload.php?page=bulkgeneratethumbnails' ) . '">' . esc_html__( 'Bulk Generate', 'bulk-generate-thumbnails' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.00
	 */
	public function add_pages() {
		add_media_page(
			__( 'Generate Thumbnails', 'bulk-generate-thumbnails' ),
			__( 'Generate Thumbnails', 'bulk-generate-thumbnails' ),
			'upload_files',
			'bulkgeneratethumbnails',
			array( $this, 'manage_page' )
		);
	}

	/** ==================================================
	 * Main page
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();
		$scriptname = admin_url( 'upload.php?page=bulkgeneratethumbnails' );

		if ( is_multisite() ) {
			$dgt_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=disable-generate-thumbnails' );
			$rts_install_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=ratio-thumbnails-size' );
		} else {
			$dgt_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=disable-generate-thumbnails' );
			$rts_install_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=ratio-thumbnails-size' );
		}
		$dgt_install_html = '<a href="' . $dgt_install_url . '" style="text-decoration: none; word-break: break-all;">Disable Generate Thumbnails</a>';
		$rts_install_html = '<a href="' . $rts_install_url . '" style="text-decoration: none; word-break: break-all;">Ratio Thumbnails Size</a>';

		?>
		<div class="wrap">
		<h2>Bulk Generate Thumbnails</h2>
			<form method="post" id="bulkgeneratethumbnails_settings" action="<?php echo esc_url( $scriptname ); ?>">
			<?php wp_nonce_field( 'bgth_settings', 'bulk_generate_thumbnails_settings' ); ?>
			</form>
			<div id="bulkgeneratethumbnails-loading-container">
				<details style="margin-bottom: 5px;">
				<summary><strong><?php esc_html_e( 'Various links of this plugin', 'bulk-generate-thumbnails' ); ?></strong></summary>
				<?php $this->credit(); ?>
				</details>

				<details style="margin-bottom: 5px;">
				<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Added Features', 'bulk-generate-thumbnails' ); ?></strong></summary>
					<strong><?php esc_html_e( 'The following plugin adds a function for generating thumbnails.', 'bulk-generate-thumbnails' ); ?></strong>
					<div style="margin: 5px; padding: 5px;">
					<p class="description">
					<?php echo( wp_kses_post( __( 'Select the thumbnails and functions to disable it.', 'bulk-generate-thumbnails' ) . ' : ' . $dgt_install_html ) ); ?>
					</p>
					<p class="description">
					<?php echo( wp_kses_post( __( 'Specify the ratio of thumbnails generation.', 'bulk-generate-thumbnails' ) . ' : ' . $rts_install_html ) ); ?>
					</p>
					</div>
				</details>

				<div style="margin: 5px; padding: 5px;">
				<?php
				if ( current_user_can( 'manage_options' ) ) {
					list( $post_ids, $no_file_ids ) = apply_filters( 'bgth_get_allimages', get_current_user_id(), true );
					$button_plus_text = __( 'All users', 'bulk-generate-thumbnails' );
				} else {
					list( $post_ids, $no_file_ids ) = apply_filters( 'bgth_get_allimages', get_current_user_id(), false );
					$button_plus_text = get_user_meta( get_current_user_id(), 'nickname', true );
				}
				if ( ! empty( $post_ids ) ) {
					/* for Ajax */
					$handle  = 'bulkgeneratethumbnails-js';
					$action1 = 'bulkgeneratethumbnails-ajax-action';
					$action2 = 'bulkgeneratethumbnails_message';
					wp_enqueue_script( $handle, plugin_dir_url( __DIR__ ) . 'js/jquery.bulkgeneratethumbnails.js', array( 'jquery' ), '1.00', false );

					wp_localize_script(
						$handle,
						'bulkgeneratethumbnails',
						array(
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'action' => $action1,
							'nonce' => wp_create_nonce( $action1 ),
						)
					);
					wp_localize_script(
						$handle,
						'bulkgeneratethumbnails_mes',
						array(
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'action' => $action2,
							'nonce' => wp_create_nonce( $action2 ),
						)
					);
					wp_localize_script(
						$handle,
						'bulkgeneratethumbnails_data',
						array(
							'count' => count( $post_ids ),
							'id' => wp_json_encode( $post_ids, JSON_UNESCAPED_SLASHES ),
						)
					);
					wp_localize_script(
						$handle,
						'bulkgeneratethumbnails_text',
						array(
							'stop_button' => __( 'Stop', 'bulk-generate-thumbnails' ),
							'stop_message' => __( 'Stopping now..', 'bulk-generate-thumbnails' ),
						)
					);

					submit_button( __( 'Bulk Generate', 'bulk-generate-thumbnails' ) . '[' . $button_plus_text . ']', 'primary', 'bulkgeneratethumbnails_ajax_update', true );
				}

				?>
				<hr>
				<?php

				$bulk_generate_thumbnails_list_table = new TT_BulkGenerateThumbnails_List_Table();
				$bulk_generate_thumbnails_list_table->prepare_items();
				submit_button( __( 'Select Generate', 'bulk-generate-thumbnails' ), 'primary', 'selectgeneratethumbnails_ajax_update1', false );
				do_action( 'bgth_per_page_set', get_current_user_id() );
				?>
				<form method="post" id="selectgeneratethumbnails_forms">
				<?php $bulk_generate_thumbnails_list_table->display(); ?>
				</form>
				<?php submit_button( __( 'Select Generate', 'bulk-generate-thumbnails' ), 'primary', 'selectgeneratethumbnails_ajax_update2', false ); ?>
				</div>

			</div>
		</div>
		<?php
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_user_option( 'bgth_per_page', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bgth_per_page', 20 );
		}
		if ( ! get_user_option( 'bgth_filter_user', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bgth_filter_user', null );
		}
		if ( ! get_user_option( 'bgth_filter_mime_type', get_current_user_id() ) ) {
			$mime_types = apply_filters( 'bgth_use_mime_types', get_current_user_id() );
			$mime_type = implode( ',', $mime_types );
			update_user_option( get_current_user_id(), 'bgth_filter_mime_type', $mime_type );
		}
		if ( ! get_user_option( 'bgth_filter_monthly', get_current_user_id() ) ) {
			update_user_option( get_current_user_id(), 'bgth_filter_monthly', null );
		}
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 2.00
	 */
	private function options_updated() {

		if ( isset( $_POST['option_change'] ) && ! empty( $_POST['option_change'] ) ) {
			if ( check_admin_referer( 'bgth_settings', 'bulk_generate_thumbnails_settings' ) ) {
				if ( ! empty( $_POST['mail_send'] ) ) {
					update_user_option( get_current_user_id(), 'bgth_mail_send', true );
				} else {
					update_user_option( get_current_user_id(), 'bgth_mail_send', false );
				}
				if ( ! empty( $_POST['per_page'] ) ) {
					$per_page = absint( $_POST['per_page'] );
					update_user_option( get_current_user_id(), 'bgth_per_page', $per_page );
				}
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
			}
		}
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'bulk-generate-thumbnails' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'bulk-generate-thumbnails' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'bulk-generate-thumbnails' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}
}
