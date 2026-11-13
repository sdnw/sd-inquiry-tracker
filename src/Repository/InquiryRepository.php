<?php

declare(strict_types=1);

namespace SD\InquiryTracker\Repository;

defined( 'ABSPATH' ) || exit;

final class InquiryRepository implements InquiryRepositoryInterface {
    public const TOPICS_TABLE = 'sd_inquiry_topics';
    public const INQUIRIES_TABLE = 'sd_inquiries';

    private readonly string $topics;
    private readonly string $inquiries;

    public function __construct( private readonly \wpdb $wpdb ) {
        $this->topics = $wpdb->prefix . self::TOPICS_TABLE;
        $this->inquiries = $wpdb->prefix . self::INQUIRIES_TABLE;
    }

    public function seed_topics( array $names ): void {
        foreach ( $names as $name ) {
            $this->wpdb->query(
                $this->wpdb->prepare( 'INSERT IGNORE INTO %i (name) VALUES (%s)', $this->topics, $name )
            );
        }
    }

    public function get_topics(): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare( 'SELECT id, name FROM %i ORDER BY name ASC', $this->topics ),
            ARRAY_A
        );
    }

    public function create( int $topic_id, string $name, string $email, string $phone, string $message): int {
        $inserted = $this->wpdb->insert(
            $this->inquiries,
            [
                'topic_id' => $topic_id,
                'name' => $name,
                'email' => $email,
                'phone'      => $phone,
                'message'    => $message,
                'status'     => self::STATUS_NEW,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            throw new \RuntimeException( 'Could not save inquiry: ' . $this->wpdb->last_error );
        }

        return (int) $this->wpdb->insert_id;
    }

    public function find_by_status( string $status, int $limit = 50 ): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT i.id, i.name, i.email, i.phone, i.message, i.status,
                        i.created_at, i.resolved_at, t.name AS topic
                FROM %i i
                INNER JOIN %i t ON t.id = i.topic_id
                WHERE i.status = %s
                ORDER BY i.created_at DESC
                LIMIT %d',
                $this->inquiries,
                $this->topics,
                $status,
                $limit
            ),
            ARRAY_A
        );
    }

    public function mark_resolved( int $id ): bool {
        $updated = $this->wpdb->update(
            $this->inquiries,
            [ 'status' => self::STATUS_RESOLVED, 'resolved_at' => current_time( 'mysql', true ) ],
            [ 'id' => $id, 'status' => self::STATUS_NEW ],
            [ '%s', '%s' ],
            [ '%d', '%s' ]
        );
        return 1 === $updated;
    }

    public function count_by_topic( string $status ): array  {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT t.name AS topic, COUNT(i.id) AS total
                FROM %i t
                LEFT JOIN %i i ON i.topic_id = t.id AND i.status = %s
                GROUP BY t.id, t.name
                ORDER BY total DESC, t.name ASC',
               $this->topics,
               $this->inquiries,
               $status
            ),
            ARRAY_A
        );
    }

}