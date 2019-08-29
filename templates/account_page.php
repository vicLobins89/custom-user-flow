<?php
if( is_user_logged_in() ) { ?>
    <h1>My account</h1>
    <hr />
    <p><a class="primary-btn" href="<?php echo wp_logout_url(); ?>">Logout</a></p>
    <hr />
<?php } else {
    echo '<p>To view your account please login below:';
    echo do_shortcode('[custom-login-form]');
    echo '<p>Or if you don\'t have an account please register with your email address:</p>';
    echo do_shortcode('[custom-register-form]');
}