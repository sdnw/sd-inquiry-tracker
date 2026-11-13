<?php

declare(strict_types=1);

namespace SD\InquiryTracker\Admin;

use SD\InquiryTracker\Repository\InquiryRepositoryInterface;

defined( 'ABSPATH' ) || exit;

final class AdminPage {

    public const SLUG        = 'sd-inquiries';
    public const ACTION      = 'sd_inquiry_resolve';
    private const CAPABILITY = 'manage_options';

    public function __construct( private readonly InquiryRepositoryInterface $repository ) {}

    public function add_menu(): void {
        add_menu_page(
            __( 'Inquiries', 'sd-inquiry-tracker' ),
            __( 'Inquiries', 'sd-inquiry-tracker' ),
            self::CAPABILITY,
            self::SLUG,
            [ $this, 'render' ],
            'dashicons-email',
            26
        );
    }

    public function render(): void {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return;
        }

        $status = ( isset( $_GET['status'] ) && 'resolved' === $_GET['status'] )
            ? InquiryRepositoryInterface::STATUS_RESOLVED
            : InquiryRepositoryInterface::STATUS_NEW;

        $rows   = $this->repository->find_by_status( $status );
        $counts = $this->repository->count_by_topic( InquiryRepositoryInterface::STATUS_NEW );
        $tabs   = [
            InquiryRepositoryInterface::STATUS_NEW      => __( 'New', 'sd-inquiry-tracker' ),
            InquiryRepositoryInterface::STATUS_RESOLVED => __( 'Resolved', 'sd-inquiry-tracker' ),
        ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Inquiries', 'sd-inquiry-tracker' ); ?></h1>

            <?php if ( isset( $_GET['resolved'] ) ) : ?>
                <?php if ( '1' === $_GET['resolved'] ) : ?>
                    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Inquiry marked as resolved.', 'sd-inquiry-tracker' ); ?></p></div>
                <?php else : ?>
                    <div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'That inquiry was already resolved.', 'sd-inquiry-tracker' ); ?></p></div>
                <?php endif; ?>
            <?php endif; ?>

            <p>
                <strong><?php esc_html_e( 'New by topic:', 'sd-inquiry-tracker' ); ?></strong>
                <?php foreach ( $counts as $count ) : ?>
                    <?php echo esc_html( $count['topic'] ); ?> (<?php echo (int) $count['total']; ?>)
                <?php endforeach; ?>
            </p>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $key => $label ) : ?>
                    <a class="nav-tab <?php echo $key === $status ? 'nav-tab-active' : ''; ?>"
                       href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'status' => $key ], admin_url( 'admin.php' ) ) ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Received</th><th>Topic</th><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( [] === $rows ) : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'No inquiries.', 'sd-inquiry-tracker' ); ?></td></tr>
                <?php endif; ?>

                <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( get_date_from_gmt( $row['created_at'], 'M j, Y g:i a' ) ); ?></td>
                        <td><?php echo esc_html( $row['topic'] ); ?></td>
                        <td><?php echo esc_html( $row['name'] ); ?></td>
                        <td><a href="<?php echo esc_url( 'mailto:' . $row['email'] ); ?>"><?php echo esc_html( $row['email'] ); ?></a></td>
                        <td><?php echo esc_html( $row['phone'] ); ?></td>
                        <td><?php echo nl2br( esc_html( $row['message'] ) ); ?></td>
                        <td>
                            <?php if ( InquiryRepositoryInterface::STATUS_NEW === $row['status'] ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
                                    <input type="hidden" name="id" value="<?php echo esc_attr( $row['id'] ); ?>">
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( self::ACTION . '_' . $row['id'] ) ); ?>">
                                    <button type="submit" class="button"><?php esc_html_e( 'Resolve', 'sd-inquiry-tracker' ); ?></button>
                                </form>
                            <?php else : ?>
                                <?php echo esc_html( get_date_from_gmt( (string) $row['resolved_at'], 'M j, Y' ) ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_resolve(): void {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'You are not allowed to do that.', 'sd-inquiry-tracker' ), 403 );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        check_admin_referer( self::ACTION . '_' . $id );

        $resolved = $this->repository->mark_resolved( $id );

        wp_safe_redirect(
            add_query_arg(
                [ 'page' => self::SLUG, 'resolved' => $resolved ? '1' : '0' ],
                admin_url( 'admin.php' )
            )
        );
        exit;
    }
}
