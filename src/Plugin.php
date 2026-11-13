<?php

declare(strict_types=1);

namespace SD\InquiryTracker;
use SD\InquiryTracker\Repository\InquiryRepository;
use SD\InquiryTracker\Frontend\InquiryForm;
use SD\InquiryTracker\Admin\AdminPage;


defined( 'ABSPATH' ) || exit;

final class Plugin {
    public function __construct(
        private readonly string $file,
        private readonly \wpdb $wpdb,
    ) {}

    public function register(): void {
        $repository = new InquiryRepository( $this->wpdb );
        $activator  = new Activator( $this->wpdb, $repository );
        register_activation_hook( $this->file, [ $activator, 'activate' ] );
        $form = new InquiryForm( $repository );
        add_shortcode( InquiryForm::SHORTCODE, [ $form, 'render' ] );
        add_action( 'admin_post_nopriv_' . InquiryForm::ACTION, [ $form, 'handle_submission' ] );
        add_action( 'admin_post_' . InquiryForm::ACTION, [ $form, 'handle_submission' ] );

        $admin = new AdminPage( $repository );
        add_action( 'admin_menu', [ $admin, 'add_menu' ] );
        add_action( 'admin_post_' . AdminPage::ACTION, [ $admin, 'handle_resolve' ] );


    }
}
