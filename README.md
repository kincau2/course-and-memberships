# HKOTA Courses and Memberships

A comprehensive WordPress plugin for managing courses, enrollments, and memberships for the Hong Kong Occupational Therapy Association (HKOTA).

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL--2.0%2B-green)

## Description

This plugin provides a complete solution for managing professional training courses, membership subscriptions, CPD (Continuing Professional Development) tracking, and certificate generation. It integrates seamlessly with WooCommerce for payment processing.

## Features

### Course Management
- **Custom Post Type** for courses with extensive metadata
- Multiple course types: Training, Co-organized Events, External Links
- Course scheduling with date/time management
- Rundown/agenda builder
- Course materials and file attachments
- Automated poster generation
- Survey and quiz functionality

### Enrollment System
- Multi-step enrollment workflow
- Enrollment status management (enrolled, pending, awaiting approval, waiting list, rejected, on-hold)
- Attendance tracking (not attended, partially attended, fully attended)
- Automated waiting list management
- File upload support for enrollment documents

### Certificate Generation
- Automated PDF certificate generation using TCPDF
- QR code verification embedded in certificates
- Customizable certificate templates
- Automatic issuance upon full attendance
- Fallback cron job for failed generations

### Membership Management
- Membership application and renewal workflows
- Membership card generation
- CPD points tracking
- OT practitioner list management

### WooCommerce Integration
- Seamless cart and checkout experience
- Custom pricing per enrollment
- Order status synchronization with enrollment status
- Support for member/non-member pricing tiers

### Email Notifications
- Enrollment confirmation emails
- Payment request notifications
- Certificate issuance alerts
- Waiting list notifications
- Admin rejection notices

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce 4.0 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Run `composer install` to install PHP dependencies
3. Activate the plugin through the WordPress admin panel
4. Configure WooCommerce dummy products in plugin settings

## File Structure

```
hkota-courses-and-memberships/
├── hkota-courses-and-memberships.php  # Main plugin file
├── include/
│   ├── init.php                       # Custom post type registration
│   ├── class-course.php               # Course class
│   ├── class-enrollment.php           # Enrollment class
│   ├── class-hkota-membership.php     # Membership class
│   ├── ajax.php                       # AJAX handlers
│   ├── core-functions.php             # Display & shortcode functions
│   ├── enrollment-function.php        # WooCommerce hooks
│   ├── edit-course-metaboxes.php      # Admin metaboxes
│   ├── certificate.php                # PDF certificate generation
│   └── js/
│       ├── backend-ajax.js            # Admin JavaScript
│       └── frontend-ajax.js           # Frontend JavaScript
├── template/
│   ├── metabox-course-details.php     # Course edit metabox
│   ├── metabox-course-status.php      # Course status metabox
│   ├── course-dashboard.php           # Enrollment management
│   ├── registration-form.php          # Frontend registration
│   └── end-survey.php                 # Post-course survey
├── email/                             # Email templates
├── asset/                             # Static assets
├── lib/                               # Third-party libraries
└── vendor/                            # Composer dependencies
```

## Database Tables

The plugin creates the following custom tables:

| Table | Description |
|-------|-------------|
| `wp_hkota_course_enrollment` | Course enrollment records |
| `wp_hkota_cpd_records` | CPD points tracking |
| `wp_hkota_ot_list` | OT practitioner registry |
| `wp_hkota_options` | Plugin configuration |

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[display_course]` | Display course listing |
| `[course_calendar]` | Course calendar view |
| `[membership_application]` | Membership application form |

## Scheduled Tasks

The plugin registers the following WordPress cron jobs:

- **Daily**: Clean expired WooCommerce sessions and associated uploads
- **Daily**: Auto-reject pending enrollments older than 5 days
- **Daily**: Process failed certificate generations (fallback)

## File Storage

Files are stored in the following directories under `wp-content/uploads/`:

| Directory | Purpose |
|-----------|---------|
| `course-files/` | Course materials and attachments |
| `course-poster/` | Generated course posters |
| `course-qr-code/` | QR codes for courses |
| `certificate/` | Generated PDF certificates |
| `pupil-uploaded-files/` | User-uploaded enrollment documents |

## Dependencies

### PHP Libraries (via Composer)
- `tecnickcom/tcpdf` - PDF generation
- `endroid/qr-code` - QR code generation
- `dompdf/dompdf` - HTML to PDF conversion
- `setasign/fpdi` - PDF manipulation
- `jurosh/pdf-merge` - PDF merging

### JavaScript Libraries
- jQuery UI - Date/time pickers
- jQuery Tablesorter - Sortable tables
- Clockpicker - Time selection

## Security

- Custom capabilities for course management
- Nonce verification on all AJAX requests
- File type and size validation on uploads
- Prepared SQL statements for database queries
- `.htaccess` protection for certificate directory

## Development

### Adding a New Course Field

1. Add HTML input in `template/metabox-course-details.php`
2. Update save handler in `edit-course-metaboxes.php`
3. Add property to `Course` class constructor
4. Display in frontend templates as needed

### Adding a New AJAX Handler

```php
// In include/ajax.php
add_action('wp_ajax_my_action', 'handle_my_action');
add_action('wp_ajax_nopriv_my_action', 'handle_my_action'); // If public

function handle_my_action() {
    check_ajax_referer('my_nonce_action', 'nonce');
    // Handler logic here
    wp_send_json_success($data);
}
```

## Author

Louis Au 

## License

This plugin is licensed under the GPL-2.0+ License. See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt) for more information.
