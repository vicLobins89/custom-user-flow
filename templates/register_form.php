<div id="register-form">
	<?php if( $attributes['show_title'] ) : ?>
        <h1><?php _e( 'Register', 'custom-user-flow' ); ?></h1>
    <?php endif; ?>

    <!-- errors if any -->
    <?php if( count( $attributes['errors'] ) > 0 ) : ?>
        <?php foreach( $attributes['errors'] as $error ) : ?>
            <p class="register-error">
                <?php echo 'The following errors were found with your submission:<br>'; ?>
                <?php echo $error; ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
        //$special_query_results = get_transient( 'special_query_results' );
    ?>
    <div class="page_form">
        <form id="signupform" action="<?php echo wp_registration_url(); ?>" method="post" autocomplete="off">

            <p class="form-row">
                <label for="username"><?php _e( 'Username', 'custom-user-flow' ); ?><strong>*</strong></label>
                <input type="text" name="username" id="username" autocomplete="off" placeholder="<?php _e( 'Username', 'custom-user-flow' ); ?>">
            </p>

            <p class="form-row">
                <label for="email"><?php _e( 'Email', 'custom-user-flow' ); ?><strong>*</strong></label>
                <input type="text" name="email" id="email" autocomplete="off" placeholder="<?php _e( 'Email', 'custom-user-flow' ); ?>">
            </p>

            <p class="form-row">
                <label for="first_name"><?php _e( 'First name', 'custom-user-flow' ); ?></label>
                <input type="text" name="first_name" id="first-name">
            </p>
    
            <p class="form-row">
                <label for="last_name"><?php _e( 'Last name', 'custom-user-flow' ); ?></label>
                <input type="text" name="last_name" id="last-name">
            </p>
    
            <p class="form-row">
                <?php _e( 'Note: Your password will be generated automatically and sent to your email address.', 'custom-user-flow' ); ?>
            </p>

            <?php if ( $attributes['recaptcha_site_key'] ) : ?>
                <div class="recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="<?php echo $attributes['recaptcha_site_key']; ?>"></div>
                </div>
            <?php endif; ?>

            <p class="signup-submit">
                <input type="submit" name="submit" id="submit-button" class="primary-btn copy-s" value="<?php _e( 'Register', 'custom-user-flow' ); ?>" />
            </p>
        </form>
    </div>
</div>