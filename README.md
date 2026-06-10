# Course Booking (local_coursebooking)

A Moodle **local plugin** that turns selected courses into a public, self-service booking catalogue. Visitors can browse available courses and book a place for themselves or for a group of delegates — with accounts, enrolments and confirmation emails created automatically.

> Built for Moodle 4.5+ • Maturity: Stable • GPL v3

## Features

- **Public course catalogue** — A branded booking page listing every course flagged as bookable, including price, start date and remaining places.
- **Individual & group bookings** — Visitors can book a single place or add multiple named delegates in one transaction.
- **Automatic account & enrolment handling** — Delegate accounts are created (or matched) and enrolled onto the course, with sign-in instructions emailed automatically.
- **Per-course configuration** — Cost per place, maximum registrations and public visibility are controlled through custom course fields.
- **Capacity management** — Live "places remaining" / "fully booked" status with capacity validation at submission time.
- **Report Builder integration** — Custom *Course bookings* data source with booking and delegate entities for native Moodle reporting.
- **Privacy API support** — Full GDPR metadata for booking contacts and delegates.
- **Configurable** — Toggle the public page, set the enrolment role, group-size limits, currency symbol and catalogue intro text from the admin settings.

## Requirements

| | |
|---|---|
| Moodle | 4.5 (build 2024100700) or later |
| PHP | As required by your Moodle version |

## Installation

1. Copy the plugin into your Moodle install:
   ```
   /path/to/moodle/local/coursebooking
   ```
2. Log in as an administrator and visit **Site administration → Notifications** to complete the database upgrade.
3. Configure the plugin under **Site administration → Plugins → Local plugins → Course Booking**.

## Usage

1. **Enable bookings on a course** — Edit a course and set the *Course booking* custom fields: cost per place, maximum registrations, and tick *Show on public booking page*.
2. **Share the catalogue** — Visitors browse and book at `/local/coursebooking/index.php`. Staff with the *view bookings* capability also get a link in the navigation.
3. **Review bookings** — Use Report Builder with the *Course bookings* data source to report on bookings and delegates.

## Capabilities

| Capability | Purpose |
|---|---|
| `local/coursebooking:manage` | Manage course booking settings |
| `local/coursebooking:viewbookings` | View course bookings |

## License

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).

© 2026 Wellingtone
