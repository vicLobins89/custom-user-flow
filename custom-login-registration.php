<?php
/**
 * Plugin Name: Custom User Flow
 * Description: This plugin adds custom pages for logging in and registering.
 * License: GPL2
 * Text Domain: custom-user-flow
 */

// Custom User Flow
class Custom_User_Flow {
	
	public function __construct() {
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) );
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
		add_shortcode( 'account-info', array( $this, 'render_account_info_page' ) );

		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
		add_action( 'login_form_register', array( $this, 'do_register_user' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'add_captcha_js_to_footer' ) );
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
		add_action( 'after_setup_theme', array( $this, 'remove_admin_bar' ) );
				
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_filter( 'admin_init', array( $this, 'register_settings_fields' ) );
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message'), 10, 4 );
		add_filter( 'wp_mail_content_type', array( $this, 'replace_email_content_type') );
	}
	
	public function register_settings_fields() {
		//reCaptcha keys
		register_setting( 'general', 'personalise-login-recaptcha-site-key' );
		register_setting( 'general', 'personalise-login-recaptcha-secret-key' );
		
		add_settings_field(
			'personalise-login-recaptcha-site-key',
			'<label for="personalise-login-recaptcha-site-key">' . __( 'reCAPTCHA site key', 'custom-user-flow' ) . '</label>',
			array( $this, 'render_recaptcha_site_key_field' ),
			'general'
		);
		
		add_settings_field(
			'personalise-login-recaptcha-secret-key',
			'<label for="personalise-login-recaptcha-secret-key">' . __( 'reCAPTCHA secret key', 'custom-user-flow' ) . '</label>',
			array( $this, 'render_recaptcha_secret_key_field' ),
			'general'
		);
	}
	
	public function render_recaptcha_site_key_field() {
		$value = get_option( 'personalise-login-recaptcha-site-key', '' );
		echo '<input type="text" id="personalise-login-recaptcha-site-key" class="regular-text" name="personalise-login-recaptcha-site-key" value="' . esc_attr( $value ) . '" />';
	}

	public function render_recaptcha_secret_key_field() {
		$value = get_option( 'personalise-login-recaptcha-secret-key', '' );
		echo '<input type="text" id="personalise-login-recaptcha-secret-key" class="regular-text" name="personalise-login-recaptcha-secret-key" value="' . esc_attr( $value ) . '" />';
	}
	
	// Activation hook
	public static function plugin_activated() {
		// Info for plugin pages
		$page_definitions = array(
			'login' => array(
				'title' => __( 'Sign In', 'custom-user-flow' ),
				'content' => '[custom-login-form]'
			),
			'account' => array(
				'title' => __( 'Your Account', 'custom-user-flow' ),
				'content' => '[account-info]'
			),
			'register' => array(
				'title' => __( 'Register', 'custom-user-flow' ),
				'content' => '[custom-register-form]'
			),
			'password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'custom-user-flow' ),
				'content' => '[custom-password-lost-form]'
			),
			'password-reset' => array(
				'title' => __( 'Password Reset', 'custom-user-flow' ),
				'content' => '[custom-password-reset-form]'
			)
		);
		
		foreach ( $page_definitions as $slug => $page ) {
			// Check if page exists
			$query = new WP_Query( 'pagename=' . $slug );
			if ( !$query->have_posts() ) {
				// Add page
				wp_insert_post(
					array(
						'post_content' 		=> $page['content'],
						'post_name' 		=> $slug,
						'post_title' 		=> $page['title'],
						'post_status' 		=> 'publish',
						'post_type' 		=> 'page',
						'ping_status'		=> 'closed',
						'comment_status'	=> 'closed',
					)
				);
			}
		}
	}
	
	// Shortcodes
	public function render_login_form( $attributes, $content = null ) {
		// Parse shortcode atts
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		$show_title = $attributes['show_title'];
		
		if( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'custom-user-flow' );
			exit;
		}
		
		$attributes['redirect'] = '';
		if( isset( $_REQUEST['redirect_to'] ) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}
		
		// Errors
		$attributes['errors'] = array();
		if( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );
			
			foreach( $error_codes as $code ) {
				$attributes['errors'] []= $this->get_error_message( $code );
			}
		}
		
		// Check if logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;
		
		// Check if user just registered
		$attributes['registered'] = isset( $_REQUEST['registered'] );
		
		// Check if user requested new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';
		
		// Check if user updated password
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';
		
		return $this->get_template_html( 'login_form', $attributes );
	}
	
	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode atts
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		
		// Errors
		$attributes['errors'] = array();
		if( isset( $_REQUEST['register-errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['register-errors'] );
			
			foreach( $error_codes as $code ) {
				$attributes['errors'] []= $this->get_error_message( $code );
			}
		}
		
		// reCaptcha
		$attributes['recaptcha_site_key'] = get_option( 'personalise-login-recaptcha-site-key', null );
		
		if( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'custom-user-flow' );
			exit;
		} elseif( !get_option( 'users_can_register' ) ) {
			return __( 'Registering is not allowed.', 'custom-user-flow' );
		} else {
			return $this->get_template_html( 'register_form', $attributes );
		}
	}
	
	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode atts
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		
		// Errors
		$attributes['errors'] = array();
		if( isset( $_REQUEST['errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['errors'] );
			
			foreach( $error_codes as $code ) {
				$attributes['errors'] []= $this->get_error_message( $code );
			}
		}
		
		if( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'custom-user-flow' );
			exit;
		} else {
			return $this->get_template_html( 'password_lost_form', $attributes );
		}
	}
	
	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode atts
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		
		if( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'custom-user-flow' );
			exit;
		} else {
			if( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];
				
				// Errors
				$attributes['errors'] = array();
				if( isset( $_REQUEST['errors'] ) ) {
					$error_codes = explode( ',', $_REQUEST['errors'] );;
					
					foreach( $error_codes as $code ) {
						$attributes['errors'] []= $this->get_error_message( $code );
					}
				}
				
				return $this->get_template_html( 'password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'custom-user-flow' );
			}
		}
	}
	
	public function render_account_info_page( $attributes, $content = null ) {
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		
		if( is_user_logged_in() ) {
			return $this->get_template_html( 'account_page', $attributes );
		} else {
			return __( 'Please log in.', 'custom-user-flow' );
			exit;
		}
	}
	
	// Get template part
	private function get_template_html( $template_name, $attributes = null ) {
		if( !$attributes ) {
			$attributes = array();
		}
		
		ob_start();
		
		do_action( 'personalise_login_before_' . $template_name );
		
		require( 'templates/' . $template_name . '.php' );
		
		do_action( 'personalise_login_after_' . $template_name );
		
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	}
	
	// Redirect user to custom login page instead of wp-login.php
	function redirect_to_custom_login() {
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
			
			if( is_user_logged_in() ) {
				$this->redirect_logged_in_user( $redirect_to );
				exit;
			}
			
			$login_url = home_url( 'login' );
			if( !empty( $redirect_to ) ) {
				$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
			}
			
			wp_redirect( $login_url );
			exit;
		}
	}
	
	// Redirect user to custom register page instead of wp default
	public function redirect_to_custom_register() {
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
			} else {
				wp_redirect( home_url( 'register' ) );
			}
			exit;
		}
	}
	
	// Redirect user to custom lost password page instead of wp default
	public function redirect_to_custom_lostpassword() {
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}
			
			wp_redirect( home_url( 'password-lost' ) );
			exit;
		}
	}
	
	// Redirect user to custom reset page or login page on error
	public function redirect_to_custom_password_reset() {
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if( !$user || is_wp_error( $user ) ) {
				if( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'login?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'login?login=invalidkey' ) );
				}
				exit;
			}
			
			$redirect_url = home_url( 'password-reset' );
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );
			
			wp_redirect( $redirect_url );
			exit;
		}
	}
	
	// Redirects logged user to correct page
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			wp_redirect( home_url( 'account' ) );
		}
	}
	
	// Redirect if errors are found
	function maybe_redirect_at_authenticate( $user, $username, $password ) {
		if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );
				
				$login_url = home_url( 'login' );
				$login_url = add_query_arg( 'login', $error_codes, $login_url );
				
				wp_redirect( $login_url );
				exit;
			}
		}
		
		return $user;
	}
	
	private function get_error_message( $error_code ) {
		switch( $error_code ) {
			case 'empty_username':
				return __( 'The username field is empty', 'custom-user-flow' );
			
			case 'empty_password':
				return __( 'The password field is empty', 'custom-user-flow' );
			
			case 'invalid_username':
				return __( 'Incorrect login details, please try again.<br><a href="%s">Forgot your password?</a>', 'custom-user-flow' );
			
			case 'incorrect_password':
				$err = __('Incorrect login details, please try again.<br><a href="%s">Forgot your password?</a>', 'custom-user-flow');
				return sprintf( $err, wp_lostpassword_Url() );
				
			case 'email':
				return __( 'Email you entered is not valid', 'custom-user-flow' );
				
			case 'user_exists':
				return __( 'User already registered', 'custom-user-flow');
				
			case 'email_exists':
				return __( 'Email already registered', 'custom-user-flow');
				
			case 'closed':
				return __( 'Registration is closed', 'custom-user-flow');
				
			case 'captcha':
				return __( 'reCaptcha failed', 'custom-user-flow' );
				
			case 'password_mismatch':
				return __( "Passwords don't match", 'custom-user-flow' );
				
			case 'password_tooshort':
				return __( "Password needs to be at least 7 characters long", 'custom-user-flow' );
				
			case 'empty_username':
				return __( 'Enter username to continue', 'custom-user-flow' );
				
			case 'invalid_email':
			case 'invalidcombo':
				return __( 'No users with that email', 'custom-user-flow' );
				
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link is not valid anymore', 'custom-user-flow' );
				
			case 'password_reset_mismatch':
				return __( 'The passwords do not match', 'custom-user-flow' );
			
			case 'password_reset_empty':
				return __( 'Cannot accept empty passwords', 'custom-user-flow' );
				
			default:
				break;
		}
		
		return __( 'Unknown error', 'custom-user-flow' );
	}
	
	public function redirect_after_logout() {
		$redirect_url = home_url( 'login?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
	}
	
	// Redirect after login
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = home_url();
		
		if( !isset( $user->ID ) ) {
			return $redirect_url;
		}
		
		if( user_can( $user, 'manage_options' ) ) {
			if( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $redirected_redirect_to;
			}
		} else {
			$redirect_url = home_url( 'account' );
		}
		
		return wp_validate_redirect( $redirect_url, home_url() );
	}
	
	// Remove admin bar for non-admins
	function remove_admin_bar() {
		if (!current_user_can('manage_options') && !is_admin()) {
		  show_admin_bar(false);
		}
	}
	
	// Validates and completes new user sign up process
	private function register_user( $username, $first_name, $last_name, $email ) {
		$errors = new WP_Error();
		
		// Validate both username and email
		if( !is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}
		
		if( username_exists( $username ) ) {
			$errors->add( 'user_exists', $this->get_error_message( 'user_exists' ) );
			return $errors;
		}
		
		if( email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			return $errors;
		}
		
		// Generate pass
		$password = wp_generate_password( 12, false );
		
		$user_data = array(
			'user_login'	=> $username,
			'user_email'	=> $email,
			'user_pass'		=> $password,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'nickname'      => $first_name . ' ' . $last_name,
		);
		
		$user_id = wp_insert_user( $user_data );
		$this->wp_new_user_notification( $user_id, $password );
		return $user_id;
	}
	
	public function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
        $user = new WP_User($user_id);

        $user_login = stripslashes($user->user_login);
        $user_email = stripslashes($user->user_email);
		$key = get_password_reset_key($user);

        $message  = sprintf(__('New user registration on %s:'), get_option('blogname')) . "<br><br>";
        $message .= sprintf(__('Username: %s'), $user_login) . "<br><br>";
        $message .= sprintf(__('E-mail: %s'), $user_email) . "<br>";

        @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), get_option('blogname')), $message);

        if ( empty($plaintext_pass) )
            return;
		
		$message = __('Thank you for registering!') . "<br><br>";
		
		$message .= sprintf(__('Username: %s'), $user_login) . "<br>";
        $message .= sprintf(__('E-mail: %s'), $user_email) . "<br>";
        $message .= sprintf(__('Password: %s'), $plaintext_pass) . "<br><br>";
		
        $message .= __('You can reset your password by  ');
		$message .= '<a href="'.site_url( "wp-login.php?action=rp&key=$key&login=" . rawurldecode( $user_login ), 'login' ).'">';
		$message .= __('clicking here') . "</a>.<br><br>";

		$message .= sprintf( __('%s'), get_bloginfo( 'name' ) );

        wp_mail($user_email, 'Registration confirmation', $message);
    }
	
	// Handles the new user reg
	public function do_register_user() {
		if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$redirect_url = home_url( 'register' );
			
			if( !get_option( 'users_can_register' ) ) {
				// Reg closed
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif( !$this->verify_recaptcha() ) {
				// Recaptcha failed
				$redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
			} else {
				$username = sanitize_text_field($_POST['username']);
				$first_name = sanitize_text_field($_POST['first_name']);
				$last_name = sanitize_text_field($_POST['last_name']);
				$email = sanitize_email($_POST['email']);
				
				$result = $this->register_user( $username, $first_name, $last_name, $email );
				
				if( is_wp_error( $result ) ) {
					// Parse errors into string and append as parameter to redirect
					$errors = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
				} else {
					// Success
					$redirect_url = add_query_arg( 'registered', $email, home_url( 'login' ) );
				}
			}
			
			wp_redirect( $redirect_url );
			exit;
		}
	}
	
	// Initiates password reset
	public function do_password_lost() {
		if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$errors = retrieve_password();
			if( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = home_url( 'password-lost' );
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				$redirect_url = home_url( 'login' );
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
			}
			
			wp_redirect( $redirect_url );
			exit;
		}
	}
	
	// Resets user pass
	public function do_password_reset() {
		if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];
			
			$user = check_password_reset_key( $rp_key, $rp_login );
			
			if( !$user || is_wp_error( $user ) ) {
				if( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( home_url( 'login?login=expiredkey' ) );
				} else {
					wp_redirect( home_url( 'login?login=invalidkey' ) );
				}
				exit;
			}
			
			if( isset( $_POST['pass1'] ) ) {
				if( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match
					$redirect_url = home_url( 'password-reset' );
					
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'errors', 'password_reset_mismatch', $redirect_url );
					
					wp_redirect( $redirect_url );
					exit;
				}
				
				if( strlen($_POST['pass1']) < 7 ) {
					// Password too short
					$redirect_url = home_url( 'password-reset' );
					
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'errors', 'password_tooshort', $redirect_url );
					
					wp_redirect( $redirect_url );
					exit;
				}
				
				if( empty( $_POST['pass1'] ) ) {
					// Password is empty
					$redirect_url = home_url( 'password-reset' );
					
					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'errors', 'password_reset_empty', $redirect_url );
					
					wp_redirect( $redirect_url );
					exit;
				}
				
				// Checks OK
				reset_password( $user, $_POST['pass1'] );
				wp_redirect( home_url( 'login?password=changed' ) );
			} else {
				_e( 'Invalid request', 'custom-user-flow' );
			}
			
			exit;
		}
	}
	
	// reCaptcha JS file
	public function add_captcha_js_to_footer() {
		echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
	}
	
	// Verify reCaptcha
	private function verify_recaptcha() {
		// This field is set by recaptcha if successful
		if( isset( $_POST['g-recaptcha-response'] ) ) {
			$captcha_response = $_POST['g-recaptcha-response'];
		} else {
			return false;
		}
		
		// Verify response from Google
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret' => get_option( 'personalise-login-recaptcha-secret-key' ),
					'response' => $captcha_response
				)
			)
		);
		
		$success = false;
		if( $response && is_array( $response ) ) {
			$decoded_response = json_decode( $response['body'] );
			$success = $decoded_response->success;
		}
		
		return $success;
	}
	
	//Message body of password reset email
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		$msg = __( 'Hello!', 'custom-user-flow' ) . "<br><br>";
		$msg .= sprintf( __( 'You asked to reset your password for the account: %s', 'custom-user-flow' ), $user_login ) . "<br><br>";
		$msg .= __( "If this is a mistake, ignore this email.", 'custom-user-flow' ) . "<br><br>";
		$msg .= __( 'To reset your password, follow this link:', 'custom-user-flow' ) . "<br><br>";
		$msg .= esc_url( site_url( "wp-login.php?action=rp&key=".$key."&login=".$user_login, 'login' ) ) . "<br><br>";
		$msg .= sprintf( __( '%s', 'custom-user-flow' ), get_bloginfo( 'name' ) );
		
		return $msg;
	}
	
	public function replace_email_content_type(){
		return "text/html";
	}
}

// Initialise plugin
$personalised_user_flow = new Custom_User_Flow();

// Register pages on plugin activation
register_activation_hook( __FILE__, array( 'Custom_User_Flow', 'plugin_activated' ) );