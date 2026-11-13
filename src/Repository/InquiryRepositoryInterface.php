<?php

declare(strict_types=1);

namespace SD\InquiryTracker\Repository;

defined( 'ABSPATH' ) || exit;

interface InquiryRepositoryInterface {
    public const STATUS_NEW = 'new';
    public const STATUS_RESOLVED = 'resolved';

    public function get_topics(): array;

    public function create ( int $topic_id, string $name, string $email, string $phone, string $message ): int;

    public function find_by_status( string $status, int $limit = 50): array;

    public function mark_resolved( int $id ): bool;

    public function count_by_topic( string $status ): array;
}