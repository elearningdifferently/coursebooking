<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for the Course Booking plugin.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Booking';

// Capabilities.
$string['coursebooking:manage'] = 'Manage course booking settings';
$string['coursebooking:viewbookings'] = 'View course bookings';

// Custom course fields.
$string['fieldcategory'] = 'Course booking';
$string['field_cost'] = 'Cost per place';
$string['field_cost_help'] = 'The price charged per delegate place on this course. Leave empty for free.';
$string['field_maxreg'] = 'Maximum number of registrations';
$string['field_maxreg_help'] = 'The maximum number of delegates that may be booked onto this course. Leave empty or 0 for unlimited.';
$string['field_expose'] = 'Show on public booking page';
$string['field_expose_help'] = 'When ticked, this course is displayed on the public course booking catalogue and may be booked by visitors.';

// Catalogue page.
$string['catalogue'] = 'Course booking';
$string['catalogueheading'] = 'Available courses';
$string['catalogueintro'] = 'Browse our available courses below and book your place.';
$string['nocourses'] = 'There are no courses available for booking at the moment. Please check back soon.';
$string['starts'] = 'Starts';
$string['nostartdate'] = 'Flexible start date';
$string['perplace'] = 'per place';
$string['free'] = 'Free';
$string['placesleft'] = '{$a} place(s) remaining';
$string['fullybooked'] = 'Fully booked';
$string['booknow'] = 'Book now';
$string['unlimitedplaces'] = 'Places available';

// Booking form.
$string['bookcourse'] = 'Book: {$a}';
$string['bookingtype'] = 'Booking type';
$string['bookingtype_individual'] = 'Individual booking (just me)';
$string['bookingtype_group'] = 'Group booking (multiple delegates)';
$string['individualdetails'] = 'Your details';
$string['yourname'] = 'Your name';
$string['youremail'] = 'Your email address';
$string['groupbookerdetails'] = 'Your details (person making the booking)';
$string['groupdetails'] = 'Group booking details';
$string['groupdelegates'] = 'Delegates';
$string['groupleaderemail'] = 'Your email address';
$string['groupleaderemail_help'] = 'The main contact for this group booking. Booking confirmations are sent here.';
$string['groupleadername'] = 'Your name';
$string['delegate'] = 'Delegate {$a}';
$string['delegatename'] = 'Delegate name';
$string['delegatefirstname'] = 'First name';
$string['delegatelastname'] = 'Last name';
$string['delegateemail'] = 'Email address';
$string['adddelegate'] = 'Add another delegate';
$string['adddelegates'] = 'Add more delegates';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['email'] = 'Email address';
$string['submitbooking'] = 'Confirm booking';
$string['cancel'] = 'Cancel';

// Validation / messages.
$string['error_required'] = 'This field is required.';
$string['error_invalidemail'] = 'Please enter a valid email address.';
$string['error_duplicateemail'] = 'The email address "{$a}" has been entered more than once in this booking.';
$string['error_nodelegates'] = 'Please add at least one delegate.';
$string['error_notbookable'] = 'This course is not available for booking.';
$string['error_capacity'] = 'There are only {$a} place(s) left on this course. Please reduce the number of delegates.';
$string['error_fullybooked'] = 'Sorry, this course is now fully booked.';
$string['error_invalidsesskey'] = 'The form session expired. Please try again.';
$string['bookingsuccess'] = 'Thank you! Your booking has been confirmed.';
$string['bookingsuccessdetail'] = 'We have created an account for each delegate and emailed them sign-in instructions. A confirmation has been sent to {$a}.';
$string['backtocatalogue'] = 'Back to course list';

// Settings.
$string['settings_general'] = 'General settings';
$string['setting_enabled'] = 'Enable public booking page';
$string['setting_enabled_desc'] = 'When disabled, the public booking catalogue returns a "not available" message.';
$string['setting_enrolrole'] = 'Enrolment role';
$string['setting_enrolrole_desc'] = 'The role assigned to delegates when they are enrolled onto a booked course.';
$string['setting_maxgroupsize'] = 'Maximum group size';
$string['setting_maxgroupsize_desc'] = 'The maximum number of delegates allowed in a single group booking (hard upper limit, separate from per-course capacity).';
$string['setting_intro'] = 'Catalogue introduction';
$string['setting_intro_desc'] = 'Text shown at the top of the public booking catalogue.';
$string['setting_currency'] = 'Currency symbol';
$string['setting_currency_desc'] = 'The currency symbol displayed alongside course prices.';
$string['disabledmessage'] = 'The booking system is currently unavailable. Please try again later.';

// Report builder.
$string['entity_booking'] = 'Course booking';
$string['entity_delegate'] = 'Booking delegate';
$string['datasource_bookings'] = 'Course bookings';
$string['booking_reference'] = 'Booking reference';
$string['booking_type'] = 'Booking type';
$string['booking_status'] = 'Status';
$string['booking_contactname'] = 'Contact name';
$string['booking_contactemail'] = 'Contact email';
$string['booking_places'] = 'Number of places';
$string['booking_unitprice'] = 'Price per place';
$string['booking_totalprice'] = 'Total price';
$string['booking_timecreated'] = 'Booking date';
$string['delegate_firstname'] = 'Delegate first name';
$string['delegate_lastname'] = 'Delegate last name';
$string['delegate_email'] = 'Delegate email';
$string['delegate_isleader'] = 'Is group leader';
$string['delegate_newaccount'] = 'New account created';
$string['status_confirmed'] = 'Confirmed';
$string['status_pending'] = 'Pending';
$string['status_cancelled'] = 'Cancelled';

// Privacy.
$string['privacy:metadata:local_coursebooking_book'] = 'Information about course place bookings.';
$string['privacy:metadata:local_coursebooking_book:contactname'] = 'The name of the booking contact.';
$string['privacy:metadata:local_coursebooking_book:contactemail'] = 'The email of the booking contact.';
$string['privacy:metadata:local_coursebooking_deleg'] = 'Information about individual delegates within a booking.';
$string['privacy:metadata:local_coursebooking_deleg:firstname'] = 'The first name of the delegate.';
$string['privacy:metadata:local_coursebooking_deleg:lastname'] = 'The last name of the delegate.';
$string['privacy:metadata:local_coursebooking_deleg:email'] = 'The email address of the delegate.';
$string['privacy:metadata:local_coursebooking_deleg:userid'] = 'The Moodle user account created or linked for the delegate.';
