<?php
/**
 * @package Import_Users_from_CSV
 */
/*
Plugin Name: Import Users from CSV
Plugin URI: http://pubpoet.com/plugins/
Description: Import Users data and metadata from a csv file.
Version: 0.5.1
Author: PubPoet
Author URI: http://pubpoet.com/
License: GPL2
Text Domain: import-users-from-csv
*/
/*  Copyright 2011  Ulrich Sossou  (https://github.com/sorich87)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain( 'import-users-from-csv', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'IS_IU_CSV_DELIMITER' ) )
	define ( 'IS_IU_CSV_DELIMITER', ',' );

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class IS_IU_Import_Users {
	private static $log_dir_path = '';
	private static $log_dir_url  = '';

	/**
	 * Initialization
	 *
	 * @since 0.1
	 **/
	public function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_pages' ) );
		add_action( 'init', array( __CLASS__, 'process_csv' ) );

		$upload_dir = wp_upload_dir();
		self::$log_dir_path = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url  = trailingslashit( $upload_dir['baseurl'] );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( __( 'Import From CSV' , 'import-users-from-csv'), __( 'Import From CSV' , 'import-users-from-csv'), 'create_users', 'import-users-from-csv', array( __CLASS__, 'users_page' ) );
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function process_csv() {
		if ( isset( $_POST['_wpnonce-is-iu-import-users-users-page_import'] ) ) {
			check_admin_referer( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' );

			if ( isset( $_FILES['users_csv']['tmp_name'] ) ) {
				// Setup settings variables
				$filename				= $_FILES['users_csv']['tmp_name'];
				$user_type				= isset( $_POST['user_type'] ) ? $_POST['user_type'] : "student";
				$student_type			= isset( $_POST['student_type'] ) ? $_POST['student_type'] : array("online-student");
				$online_package			= isset( $_POST['online-package'] ) ? $_POST['online-package'] : "";
				$password_nag			= isset( $_POST['password_nag'] ) ? $_POST['password_nag'] : false;
				$new_user_notification	= isset( $_POST['new_user_notification'] ) ? $_POST['new_user_notification'] : false;

				$results = self::import_csv( $filename, $password_nag, $new_user_notification, $user_type, $student_type, $online_package );

				// No users imported?
				if ( ! $results['user_ids'] )
					wp_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );

				// Some users imported?
				elseif ( $results['errors'] )
					wp_redirect( add_query_arg( 'import', 'errors', wp_get_referer() ) );

				// All users imported? :D
				else
					wp_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );

				exit;
			}

			wp_redirect( add_query_arg( 'import', 'file', wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function users_page() {
		if ( ! current_user_can( 'create_users' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.' , 'import-users-from-csv') );
?>

<div class="wrap">
	<h2><?php _e( 'Import users from a CSV file' , 'import-users-from-csv'); ?></h2>
	
	<p><label>Name of the colums your file can have:</label> user_login, user_pass, user_email, user_url, user_nicename,
			display_name, user_registered, first_name, last_name, nickname, description,
			rich_editing, comment_shortcuts, admin_color, use_ssl, show_admin_bar_front, show_admin_bar_admin, role, teacher_email,casenex_id</p>
	
	<?php
	global $wpdb;
	
	$error_log_file = self::$log_dir_path . 'is_iu_errors.log';
	$error_log_url  = self::$log_dir_url . 'is_iu_errors.log';
	
	switch_to_blog(3);
	// Get online products
	$post_sql = "SELECT ID, post_content, post_title, post_name
				FROM {$wpdb -> posts}
				LEFT JOIN {$wpdb -> term_relationships} ON({$wpdb -> posts}.ID = {$wpdb -> term_relationships}.object_id)
				LEFT JOIN {$wpdb -> term_taxonomy} ON({$wpdb -> term_relationships}.term_taxonomy_id = {$wpdb -> term_taxonomy}.term_taxonomy_id)
				LEFT JOIN {$wpdb -> terms} ON({$wpdb -> term_taxonomy}.term_id = {$wpdb -> terms}.term_id)
				WHERE {$wpdb -> posts}.post_type = 'ECPProduct' 
				AND {$wpdb -> posts}.post_status = 'publish'
				AND {$wpdb -> term_taxonomy}.taxonomy = 'ecp-products'
				AND {$wpdb -> terms}.slug = 'satact-edge-online-course' 
				ORDER BY {$wpdb -> posts}.menu_order ASC;";
				
	$products = $wpdb -> get_results($wpdb -> prepare($post_sql));
	restore_current_blog();

	if ( ! file_exists( $error_log_file ) ) {
		if ( ! @fopen( $error_log_file, 'x' ) )
			echo '<div class="updated"><p><strong>' . sprintf( __( 'Notice: please make the directory %s writable so that you can see the error log.' , 'import-users-from-csv'), self::$log_dir_path ) . '</strong></p></div>';
	}

	if ( isset( $_GET['import'] ) ) {
		$error_log_msg = '';
		if ( file_exists( $error_log_file ) )
			$error_log_msg = sprintf( __( ', please <a href="%s">check the error log</a>' , 'import-users-from-csv'), $error_log_url );

		switch ( $_GET['import'] ) {
			case 'file':
				echo '<div class="error"><p><strong>' . __( 'Error during file upload.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'data':
				echo '<div class="error"><p><strong>' . __( 'Cannot extract data from uploaded file or no file was uploaded.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'fail':
				echo '<div class="error"><p><strong>' . sprintf( __( 'No user was successfully imported%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'errors':
				echo '<div class="error"><p><strong>' . sprintf( __( 'Some users were successfully imported but some were not%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'success':
				echo '<div class="updated"><p><strong>' . __( 'Users import was successful.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			default:
				break;
		}
	}
	?>
	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for"users_csv"><?php _e( 'CSV file' , 'import-users-from-csv'); ?></label></th>
				<td><input type="file" id="users_csv" name="users_csv" value="" class="all-options" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Users type' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Users type' , 'import-users-from-csv'); ?></span></legend>
					<div>
						<label><input name="user_type" type="radio" value="student" checked /> Students</label>
					</div>
					<div>
						<label><input name="user_type" type="radio" value="teacher" /> Teachers</label>
					</div>
				</fieldset></td>
			</tr>
			<tr valign="top" id="student-types-container">
				<th scope="row"><?php _e( 'Student type' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Student type' , 'import-users-from-csv'); ?></span></legend>
					<div>
						<label><input name="student_type[]" type="checkbox" value="online-student" checked /> Online</label>
					</div>
					<div>
						<label><input name="student_type[]" type="checkbox" value="offline-student" /> Offline</label>
					</div>
					<div>
						<label><input name="student_type[]" type="checkbox" value="school-student" /> School</label>
					</div>
					<div>
						<label><input name="student_type[]" type="checkbox" value="demo-student" /> Demo</label>
					</div>
				</fieldset></td>
			</tr>
			<tr valign="top" id="online-package-container">
				<th scope="row"><?php _e( 'Add online package' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Online package' , 'import-users-from-csv'); ?></span></legend>
					<?php foreach($products as $k=>$product): ?>
					<div>
						<label><input name="online-package" type="radio" value="<?php echo $product->ID ?>" <?php if($k==0) echo "checked";?> />
						<?php echo $product->post_title ?></label>
					</div>
					<?php endforeach; ?>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Notification' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Notification' , 'import-users-from-csv'); ?></span></legend>
					<label for="new_user_notification">
						<input id="new_user_notification" name="new_user_notification" type="checkbox" value="1" />
						Send to new users
					</label>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Password nag' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Password nag' , 'import-users-from-csv'); ?></span></legend>
					<label for="password_nag">
						<input id="password_nag" name="password_nag" type="checkbox" value="1" />
						Show password nag on new users signon
					</label>
				</fieldset></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Import' , 'import-users-from-csv'); ?>" />
		</p>
	</form>
	
	<style>
		label{font-weight:bold;}
	</style>
	
	<script type="text/javascript">
		jQuery(function($){
			$('input[name=user_type]').click(function() {
				if($('input[name=user_type]:checked').val() == "teacher") {
					$("#student-types-container").hide();
					$("#online-package-container").hide();
				} else {
					$("#student-types-container").show();
					$('input[name^=student_type]:checked').each(function() {
						if($(this).val() == "online-student") {
							$("#online-package-container").show();
						}
					});
				}
			});
			$('input[name^=student_type]').click(function() {
					$("#online-package-container").hide();
				$('input[name^=student_type]:checked').each(function() {
					if($(this).val() == "online-student") {
						$("#online-package-container").show();
					}
				});
				
			});
		});
	</script>
	
<?php
	}

	/**
	 * Import a csv file
	 *
	 * @since 0.5
	 */
	public static function import_csv( $filename, $password_nag = false, $new_user_notification = false, $user_type = "student", $student_type = array("online-student" ), $online_package = "") {
		$errors = $user_ids = array();
		
		// User data fields list used to differentiate with user meta
		$userdata_fields = array(
			'ID', 'user_login', 'user_pass',
			'user_email', 'user_url', 'user_nicename',
			'display_name', 'user_registered', 'first_name',
			'last_name', 'nickname', 'description',
			'rich_editing', 'comment_shortcuts', 'admin_color',
			'use_ssl', 'show_admin_bar_front', 'show_admin_bar_admin',
			'role', 'teacher_email', 'casenex_id'
		);

		include( plugin_dir_path( __FILE__ ) . 'class-readcsv.php' );

		// Loop through the file lines
		$file_handle = fopen( $filename, 'r' );
		$csv_reader = new ReadCSV( $file_handle, IS_IU_CSV_DELIMITER, "\xEF\xBB\xBF" ); // Skip any UTF-8 byte order mark.

		$first = true;
		$rkey = 0;
		while ( ( $line = $csv_reader->get_row() ) !== NULL ) {
			// If the first line is empty, abort
			// If another line is empty, just skip it
			if ( empty( $line ) ) {
				if ( $first )
					break;
				else
					continue;
			}
			
			// If we are on the first line, the columns are the headers
			if ( $first ) {
				$headers = $line;
				$first = false;
				continue;
			}
			
			// Separate user data from meta
			$userdata = $usermeta = array();
			foreach ( $line as $ckey => $column ) {
				$column_name = $headers[$ckey];
				$column = trim( $column );

				if ( empty( $column ) )
					continue;

				if ( in_array( $column_name, $userdata_fields ) ) {
					$userdata[$column_name] = $column;
				} else {
					$usermeta[$column_name] = $column;
				}
			}
			
			// If the row has no email, skip
			if ( ! $update && empty( $userdata['user_email'] ) ) {
				continue;
			}

			// A plugin may need to filter the data and meta
			$userdata = apply_filters( 'is_iu_import_userdata', $userdata, $usermeta );
			$usermeta = apply_filters( 'is_iu_import_usermeta', $usermeta, $userdata );
            
			// If no user data, bailout!
			if ( empty( $userdata ) )
				continue;

			// Something to be done before importing one user?
			do_action( 'is_iu_pre_user_import', $userdata, $usermeta );

			// Are we updating an old user or creating a new one?
			$update = false;
			$user_id = 0;
            
			if ( ! empty( $userdata['last_name'] ) ) {
                $std = new WP_User_Query( array( 'search' => $userdata['user_email'], 'search_columns'=>'user_email' ) );
                            
                //if there are no teachers with the entered email, just skip this process.
                if ( !empty( $std->results ) ) {
                    $update = true;
                    foreach($std->results as $current){
                        //getting current user id
                        $user_id = $current->ID;
                        break;
                    }
                }
			}
			
			// If creating a new user and no user_login was set, use the email.
			if ( ! $update && empty( $userdata['user_login'] ) )
				$userdata['user_login'] = $userdata['user_email'];

			// If creating a new user and no password was set, auto-generate one.
			if ( ! $update && empty( $userdata['user_pass'] ) )
				$userdata['user_pass'] = wp_generate_password( 12, false );
			
			// If creating a new user and no user_registered was set, use current date
			if ( ! $update && empty( $userdata['user_registered'] ) )
				$userdata['user_registered'] = date('Y-m-d H:i:s');
			
			// If creating a new user and no user_nicename was set, use first and last name
			if ( ! $update && empty( $userdata['user_nicename'] ) )
				$userdata['user_nicename'] = trim($userdata['first_name']." ".$userdata['last_name']);
			
			// If creating a new user and no display_name was set, use first and last name
			if ( ! $update && empty( $userdata['display_name'] ) )
				$userdata['display_name'] = trim($userdata['first_name']." ".$userdata['last_name']);
			
			
			// Create custom user meta
			$usermeta['_IDGL_elem_Username'] = $userdata['user_login'];
			$usermeta['_IDGL_elem_FirstName'] = $userdata['first_name'];
			$usermeta['_IDGL_elem_LastName'] = $userdata['last_name'];
			$usermeta['_IDGL_elem_Nickname'] = $userdata['user_nicename'];
			$usermeta['_IDGL_elem_Email'] = $userdata['user_email'];
			$usermeta['_IDGL_elem_user_type'] = $user_type;
			$usermeta['_IDGL_elem_registration_date'] = time();
			
			if($user_type == "student") {
				$usermeta['_IDGL_elem_userSubtype'] = serialize($student_type);
				if(in_array("online-student", $student_type)) {
					$usermeta['_IDGL_elem_ECP_user_order'] = serialize(array($online_package));
				}
                
                //search for teacher if the field is set in the file
                if(isset($userdata['teacher_email'])){
                    $teacher = new WP_User_Query( array( 'search' => $userdata['teacher_email'], 'search_columns'=>'user_email' ) );
                            
                    //if there are no teachers with the entered email, just skip this process.
                    if ( !empty( $teacher->results ) ) {
                        foreach($teacher->results as $current){
                            //getting current teacher id
                            $teacher_id = $current->ID;
                            $metadata = get_user_meta($teacher_id,_IDGL_elem_user_type,true);
                            //you can't set a student as a teacher
                            if($metadata != 'teacher'){
                                unset($teacher_id);
                            }
                            break;
                        }
                    }
                    unset($userdata['teacher_email']);
                }
                
                
			}

			// Insert or update.
			// If only user ID was provided, we don't need to do anything at all.
			if ( array( 'ID' => $user_id ) == $userdata )
				$user_id = get_userdata( $user_id )->ID; // To check if the user id exists
			else if ( $update ){
                $userdata['ID'] = $user_id;
				$user_id = wp_update_user( $userdata );
			}else
				$user_id = wp_insert_user( $userdata );
                
			// Is there an error?
			if ( is_wp_error( $user_id ) ) {
				$errors[$rkey]['error'] = $user_id;
				$errors[$rkey]['userdata'] = $userdata;
			} else {
				// If no error, let's update the user meta too!
				if ( $usermeta ) {
					foreach ( $usermeta as $metakey => $metavalue ) {
						$metavalue = maybe_unserialize( $metavalue );
						update_user_meta( $user_id, $metakey, $metavalue );
					}
                    // order details metadata
                    global $wpdb;
                    
                    $post_sql = "SELECT ID, post_name, meta_value
                                FROM wp_3_posts post
                                JOIN wp_3_postmeta meta ON post.ID = meta.post_id 
                                WHERE post.ID = $online_package
                                AND meta_key = 'universal_price'";
                    
                    $product = $wpdb -> get_results($wpdb -> prepare($post_sql));
                    
                    $previous_orders_details = get_user_meta($user_id, "_IDGL_elem_ECP_user_orders_details", true);
                    
                    $orders_details = (empty($previous_orders_details)) ? array() : $previous_orders_details;
                    $product_as_array = array();
                    if( ! empty($product)){
                        foreach($product as $key => $product){
                            $product_as_array[$key]["id"] = $product->ID;
                            $product_as_array[$key]["type"] = $product->post_name;
                            $product_as_array[$key]["price"] = $product->meta_value;
                        }
                        $orders_details[time()]=$product_as_array;
                    }
                    update_user_meta($user_id, "_IDGL_elem_ECP_user_orders_details", $orders_details);
				}
                
                //create student-teacher relationship
                if(isset($teacher_id)){
                    global $wpdb;
                    $q = "INSERT IGNORE INTO wp_teacherstudent (student_ID,teacher_ID) VALUES ";
                    $q .= "($user_id, $teacher_id)";			
                    $wpdb->query($q);
                }

				// If we created a new user, maybe set password nag and send new user notification?
				if ( ! $update ) {
					if ( $password_nag )
						update_user_option( $user_id, 'default_password_nag', true, true );

					if ( $new_user_notification )
						wp_new_user_notification( $user_id, $userdata['user_pass'] );
				}

				// Some plugins may need to do things after one user has been imported. Who know?
				do_action( 'is_iu_post_user_import', $user_id );

				$user_ids[] = $user_id;
			}

			$rkey++;
		}
		fclose( $file_handle );

		// One more thing to do after all imports?
		do_action( 'is_iu_post_users_import', $user_ids, $errors );

		// Let's log the errors
		self::log_errors( $errors );

		return array(
			'user_ids' => $user_ids,
			'errors'   => $errors
		);
	}

	/**
	 * Log errors to a file
	 *
	 * @since 0.2
	 **/
	private static function log_errors( $errors ) {
		if ( empty( $errors ) )
			return;

		$log = @fopen( self::$log_dir_path . 'is_iu_errors.log', 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , 'import-users-from-csv'), date( 'Y-m-d H:i:s', time() ) ) . "\n" );
		foreach ( $errors as $key => $error ) {
			$line = $key + 1;
			$message = $error['error']->get_error_message();
			$user_login = $error['userdata']['user_login'];
			@fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'import-users-from-csv'), $line, $message . "(" . $user_login . ")" ) . "\n" );
		}
		@fclose( $log );
	}
}

IS_IU_Import_Users::init();
