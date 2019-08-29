<div id="password-reset-form">
	<?php if( $attributes['show_title'] ) : ?>
        <h1><?php _e( 'Pick a New Password', 'custom-user-flow' ); ?></h1>
    <?php endif; ?>

    <div class="page_form">
        <form name="resetpassform" id="signupform" autocomplete="off" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
            <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off">
            <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>">

            <!-- errors if any -->
            <?php if( count( $attributes['errors'] ) > 0 ) : ?>
                <?php foreach( $attributes['errors'] as $error ) : ?>
                    <p class="login-error">
                        <?php echo $error; ?>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>

            <p class="form-row">
                <label for="pass1"><?php _e( 'Create Password', 'custom-user-flow' ); ?></label>
                <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" placeholder="<?php _e( 'Create Password', 'custom-user-flow' ); ?>">
            </p>

            <p class="form-row">
                <label for="pass2"><?php _e( 'Repeat Password', 'custom-user-flow' ); ?></label>
                <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" placeholder="<?php _e( 'Repeat Password', 'custom-user-flow' ); ?>">
            </p>

            <p class="form-row">
                <input type="submit" name="submit" id="resetpass-button" class="primary-btn" value="<?php _e( 'Set Password', 'custom-user-flow' ); ?>">
            </p>
        </form>
    </div>
</div>