<?php

include_once plugin_dir_path(__FILE__) . 'include/main.php';

if ( ! class_exists( 'Gems_Helper' ) ) {
	class Gems_Helper extends GEMS_PRODUCTS_PLUGIN {


		/**
		 * @param $filename
		 *
		 * @return null|string
		 */
		public static function getImageIdByName( $filename ) {
			global $wpdb;
			$query = "SELECT ID  FROM {$wpdb->posts} WHERE post_title='$filename' LIMIT 1";

			return $wpdb->get_var( $query );
		}

		/**
		 * @param $file
		 *
		 * @return mixed
		 */
		public static function get_mime_type( $file = '' ) {

			// our list of mime types
			$mime_types = array(
				"gif"  => "image/gif",
				"png"  => "image/png",
				"jpeg" => "image/jpg",
				"jpg"  => "image/jpg",
				"mp4"  => "video/mp4",
			);

			if ( empty( $file ) || strpos( $file, '.' ) == false ) {
				return 'image/jpg';
			}

			$extension = explode( '.', $file );

			if ( ! empty( $extension ) ) {
				$extension = strtolower( end( $extension ) );
				return $mime_types[ $extension ];
			}


		}

		/**
		 * retrieves the attachment ID from the file URL
		 *
		 * @param string $image_url
		 *
		 * @return int|bool
		 */
		public static function getImageIdByUrl( $image_url ) {
			global $wpdb;
			$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );

			return $attachment[0];
		}

		/**
		 * @param $id
		 * @param $url
		 */
		public static function updateQuid( $id, $url ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'guid' => $url ), array( 'ID' => $id ) );
		}


		/**
		 * @param $error_msg
		 *
		 * error log function
		 * $this->gems_log('error massage') -> exemple to use;
		 *
		 */
		public static function gems_log( $error_msg ) {
			$gemslog_status = get_option( 'gems_logs' );

			if ( $gemslog_status == 'on' ) {
				$log_folder = apply_filters( 'gemslog_folder', plugin_dir_path( __FILE__ ) . '../error_logs' );


				if ( ! is_dir( $log_folder ) ) {
					if ( ! mkdir( $log_folder, 0755 ) ) {
						esc_html_e( "Unable to create new folder. Maybe you don't have permission", 'gems-importer' );
						die();
					}
				}


				$log_file_name = $log_folder . apply_filters( 'gemslog_file_name', '/error_log_' . date( "d-M-Y" ) . '.log', date( "d-M-Y" ) );
				file_put_contents( $log_file_name, '[' . date( 'd-M-Y h:i:sa' ) . '] ' . $error_msg . "\n", FILE_APPEND );
			}
		}

		public static function gems_log_html( $error_msg ) {
			$gemslog_status = get_option( 'gems_logs' );

			if ( $gemslog_status == 'on' ) {
				$log_folder = apply_filters( 'gemslog_folder', plugin_dir_path( __FILE__ ) . '../error_logs' );


				if ( ! is_dir( $log_folder ) ) {
					if ( ! mkdir( $log_folder, 0755 ) ) {
						esc_html_e( "Unable to create new folder. Maybe you don't have permission", 'gems-importer' );
						die();
					}
				}


				$log_file_name = $log_folder . apply_filters( 'gemslog_file_name', '/error_log_' . date( "d-M-Y" ) . '.html', date( "d-M-Y" ) );
				file_put_contents( $log_file_name, '[' . date( 'd-M-Y h:i:sa' ) . '] ' . $error_msg . "\n", FILE_APPEND );
			}
		}

		/**
		 * @param        $site_url
		 * @param        $file_name
		 * @param string $name_for_log
		 *
		 * @return null
		 */
		public static function check_remote_url( $site_url, $file_name, $name_for_log = '' ) {

			// get image from url
			$image    = $site_url . $file_name;
			$response = wp_remote_get( $image, array( 'sslverify', false ) );


			// if http (wp) error
			if ( is_wp_error( $response ) ) {
				Gems_Helper::gems_log( $name_for_log . $response->get_error_message() );

				return null;
			}

			// if response code not 200
			if ( ! empty( $response['response']['code'] ) && $response['response']['code'] != '200' ) {
				Gems_Helper::gems_log( $name_for_log . $response['response']['code'] . ' ' . $response['response']['message'] );

				return null;
			}
		}

	}

}
