# SD Inquiry Tracker

A small WordPress plugin that collects inquiries from a front-end form, stores them in custom database tables, and lets an administrator work through them in the dashboard.

## Features

- A `[sd_inquiry_form]` shortcode renders an inquiry form with a topic, name, email, phone, and message.
- Submissions are saved to two custom tables joined by a foreign key. The topic list is seeded on activation with General, Quote request, and Support.
- An **Inquiries** admin screen has New and Resolved tabs, a count of new inquiries by topic, and a Resolve button on each new inquiry.
- Timestamps are stored in UTC and shown in the site's timezone.

## Requirements

- PHP 8.1 or newer
- WordPress 6.2 or newer (the repository uses the `%i` identifier placeholder in `$wpdb->prepare()`)
- A database engine that supports foreign keys, such as InnoDB
- Composer

## Installation

```
cd wp-content/plugins
git clone https://github.com/sdnw/sd-inquiry-tracker.git
cd sd-inquiry-tracker
composer install
```

Then activate **SD Inquiry Tracker** on the Plugins screen. Activation creates the tables and seeds the topics. Add `[sd_inquiry_form]` to any page or post to show the form, and find submissions under **Inquiries** in the admin menu.

## How it is built

```
sd-inquiry-tracker.php        Plugin header, loads the Composer autoloader, boots Plugin
src/
  Plugin.php                  Wires the classes together and registers the WordPress hooks
  Activator.php               Creates the tables, adds the foreign key, seeds the topics
  Repository/
    InquiryRepositoryInterface.php
    InquiryRepository.php     All database access
  Frontend/InquiryForm.php    Shortcode, form markup, and submission handler
  Admin/AdminPage.php         Inquiries screen and the resolve handler
```

- **Class-based OOP.** Classes are `final`, use `declare(strict_types=1)`, and take their dependencies through readonly constructor properties.
- **Repository pattern behind an interface.** `InquiryForm` and `AdminPage` depend on `InquiryRepositoryInterface`. Only `Plugin` builds the concrete `InquiryRepository`, which receives `$wpdb`.
- **Custom tables with a foreign key.** `{prefix}sd_inquiry_topics` and `{prefix}sd_inquiries` are linked by `topic_id` with `ON DELETE RESTRICT`. `dbDelta()` does not manage foreign keys, so the activator adds the constraint with an `ALTER TABLE`, after checking `information_schema` so that reactivating the plugin is safe.
- **Composer PSR-4 autoloading** under the `SD\InquiryTracker\` namespace.

## Security

- **Front-end form.** The nonce is verified before anything else. Input is unslashed and sanitized, the topic must match a known topic ID, the email is validated, and name, email, and phone have length limits.
- **Admin screen.** The Inquiries page and the resolve handler require the `manage_options` capability. Resolving also verifies a per-inquiry nonce with `check_admin_referer()`.
- **Database.** Queries use `$wpdb->prepare()`, including `%i` for table names, and writes use `$wpdb->insert()` and `$wpdb->update()` with format arrays.
- **Output.** Everything printed is escaped with `esc_html()`, `esc_attr()`, or `esc_url()`.
- **Resolving is idempotent.** Only inquiries that are still `new` can be resolved, so resolving twice changes nothing.

## Not included yet

- Email notifications for new inquiries
- Pagination (each tab shows the 50 newest inquiries)
- Cleanup on uninstall (the tables remain if the plugin is deleted)
- Automated tests

## Author

Steven Dawn
