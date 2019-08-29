<div id="login-form-container">
	<?php if( $attributes['show_title'] ) : ?>
        <h1><?php _e( 'Sign In', 'custom-user-flow' ); ?></h1>
    <?php endif; ?>

    <!-- errors if any -->
    <?php if( count( $attributes['errors'] ) > 0 ) : ?>
        <?php foreach( $attributes['errors'] as $error ) : ?>
            <p class="login-error">
                <?php echo $error; ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- registered message -->
    <?php if( $attributes['registered'] ) : ?>
        <p class="login-info">
            <?php
                printf( __( 'You have successfully registered to <b>%s</b>.', 'custom-user-flow' ), get_bloginfo( 'name' ) );
            ?>
        </p>
    <?php endif; ?>

    <!-- password reset message -->
    <?php if( $attributes['lost_password_sent'] ) : ?>
        <p class="login-info">
            <?php _e( 'Check your email for a link to reset your password', 'custom-user-flow' ); ?>
        </p>
    <?php endif; ?>

    <!-- password changed message -->
    <?php if( $attributes['password_updated'] ) : ?>
        <p class="login-info">
            <?php _e( 'You have successfully reset your password', 'custom-user-flow' ); ?>
        </p>
    <?php endif; ?>

    <!-- logged out message -->
    <?php if( $attributes['logged_out'] ) : ?>
        <p class="login-info">
            <?php _e( 'Signed out', 'custom-user-flow' ); ?>
        </p>
    <?php endif; ?>

    <div class="page_form">
        <form id="signupform" action="<?php echo wp_login_url(); ?>" method="post" autocomplete="off">

            <p class="form-row">
                <label for="user_login"><?php _e( 'Username', 'custom-user-flow' ); ?></label>
                <input type="text" name="log" id="user_login" autocomplete="off" placeholder="<?php _e( 'Username', 'custom-user-flow' ); ?>">
            </p>

            <p class="form-row">
                <label for="user_pass"><?php _e( 'Password', 'custom-user-flow' ); ?></label>
                <input type="password" name="pwd" id="user_pass" autocomplete="off" placeholder="<?php _e( 'Password', 'custom-user-flow' ); ?>">
            </p>

            <p class="signup-submit">
                <input type="submit" name="wp-submit" class="primary-btn copy-s" id="wp-submit" value="<?php _e( 'Login', 'custom-user-flow' ); ?>" />
                <input type="hidden" name="redirect_to" value="">
            </p>
        </form>
    </div>

    <a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>">
        <?php _e( 'Forgot your password?', 'custom-user-flow' ); ?>
    </a>
</div>