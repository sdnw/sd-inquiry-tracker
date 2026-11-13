<?php

declare(strict_types=1);

namespace SD\InquiryTracker;
use SD\InquiryTracker\Repository\InquiryRepository;

defined( 'ABSPATH' ) || exit;

final class Activator {

public function __construct(
    private readonly \wpdb $wpdb,
    private readonly InquiryRepository $repository,
) {}

    public function activate(): void {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset   = $this->wpdb->get_charset_collate();
        $topics    = $this->wpdb->prefix . InquiryRepository::TOPICS_TABLE;
        $inquiries = $this->wpdb->prefix . InquiryRepository::INQUIRIES_TABLE;

        // Parent table first: the foreign key needs it to exist.
        dbDelta( "CREATE TABLE {$topics} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) {$charset};" );

dbDelta( "CREATE TABLE {$inquiries} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  topic_id bigint(20) unsigned NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  phone varchar(30) NOT NULL,
  message text NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'new',
  created_at datetime NOT NULL,
  resolved_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY topic_id (topic_id),
  KEY status_created (status,created_at)
) {$charset};" );

        $this->add_foreign_key( $inquiries, $topics );
        $this->repository->seed_topics( [ 'General', 'Quote request', 'Support' ] );
    }

    private function add_foreign_key( string $inquiries, string $topics ): void {
        $constraint = $inquiries . '_topic_fk';

        $exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = %s AND TABLE_NAME = %s AND CONSTRAINT_NAME = %s',
                DB_NAME,
                $inquiries,
                $constraint
            )
        );

        if ( null !== $exists ) {
            return;
        }

        $this->wpdb->query(
            $this->wpdb->prepare(
                'ALTER TABLE %i ADD CONSTRAINT %i FOREIGN KEY (topic_id) REFERENCES %i (id) ON DELETE RESTRICT',
                $inquiries,
                $constraint,
                $topics
            )
        );
    }
}