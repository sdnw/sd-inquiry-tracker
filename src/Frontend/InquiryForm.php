<?php

declare(strict_types=1);

namespace SD\InquiryTracker\Frontend;

use SD\InquiryTracker\Repository\InquiryRepositoryInterface;

defined( 'ABSPATH' ) || exit;

final class InquiryForm {

    public const SHORTCODE = 'sd_inquiry_form';
    public const ACTION    = 'sd_inquiry_submit';
    private const NONCE    = 'sd_inquiry_nonce';

    public function __construct( private readonly InquiryRepositoryInterface $repository ) {}

    public function render(): string {
        $notice = $this->notice( isset( $_GET['sd_inquiry'] ) ? sanitize_key( wp_unslash( $_GET['sd_inquiry'] ) ) : '' );

        ob_start();
        ?>
        <?php if ( '' !== $notice ) : ?>
            <p class="sd-inquiry-notice"><?php echo esc_html( $notice ); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
            <?php wp_nonce_field( self::ACTION, self::NONCE ); ?>

            <p>
                <label for="sd-topic">Topic</label>
                <select id="sd-topic" name="topic_id" required>
                    <?php foreach ( $this->repository->get_topics() as $topic ) : ?>
                        <option value="<?php echo esc_attr( $topic['id'] ); ?>"><?php echo esc_html( $topic['name'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="sd-name">Name</label>
                <input id="sd-name" type="text" name="name" maxlength="100" required>
            </p>
            <p>
                <label for="sd-email">Email</label>
                <input id="sd-email" type="email" name="email" maxlength="100" required>
            </p>
            <p>
                <label for="sd-phone">Phone</label>
                <input id="sd-phone" type="tel" name="phone" maxlength="30" required>
            </p>
            <p>
                <label for="sd-message">Message</label>
                <textarea id="sd-message" name="message" rows="5" required></textarea>
            </p>
            <p><button type="submit">Send inquiry</button></p>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_submission(): void {
        $nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
            $this->redirect( 'expired' );
        }

        $topic_id = isset( $_POST['topic_id'] ) ? absint( $_POST['topic_id'] ) : 0;
        $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $message  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        $topic_ids = array_map( 'intval', array_column( $this->repository->get_topics(), 'id' ) );

        if (
            ! in_array( $topic_id, $topic_ids, true )
            || '' === $name || mb_strlen( $name ) > 100
            || ! is_email( $email ) || strlen( $email ) > 100
            || '' === $phone || mb_strlen( $phone ) > 30
            || '' === $message
        ) {
            $this->redirect( 'invalid' );
        }

        try {
            $this->repository->create( $topic_id, $name, $email, $phone, $message );
        } catch ( \RuntimeException $e ) {
            error_log( $e->getMessage() );
            $this->redirect( 'failed' );
        }

        $this->redirect( 'sent' );
    }

    private function notice( string $status ): string {
        return match ( $status ) {
            'sent'    => __( 'Thanks! Your inquiry has been sent.', 'sd-inquiry-tracker' ),
            'invalid' => __( 'Please fill in every field with valid information.', 'sd-inquiry-tracker' ),
            'expired' => __( 'Your session expired. Please try again.', 'sd-inquiry-tracker' ),
            'failed'  => __( 'Something went wrong. Please try again later.', 'sd-inquiry-tracker' ),
            default   => '',
        };
    }

    private function redirect( string $status ): never {
        $back = wp_get_referer() ?: home_url( '/' );
        wp_safe_redirect( add_query_arg( 'sd_inquiry', $status, $back ) );
        exit;
    }
}
