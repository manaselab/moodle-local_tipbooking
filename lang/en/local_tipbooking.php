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
 * English strings for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bookingcancelled'] = 'Booking cancelled. The time is free again.';
$string['bookingconfirmed'] = 'Booking confirmed. We have sent you a message.';
$string['bookingsofnamed'] = 'Bookings of {$a}';
$string['cancel:confirm'] = 'Cancel booking';
$string['cancel:question'] = 'Cancel your booking of {$a->range} on {$a->day}? The whole range will be free for other people and the managers of the space will be told.';
$string['cancel:title'] = 'Cancel booking';
$string['catalogue:count'] = 'Spaces: {$a}';
$string['catalogue:countondate'] = 'Spaces with free slots on {$a->date}: {$a->count}';
$string['catalogue:createfirst'] = 'Create the first space';
$string['catalogue:intro'] = 'Choose a space and book a free range of time.';
$string['catalogue:mybookingshere'] = 'Your bookings here: {$a}';
$string['catalogue:noresults'] = 'No space matches your filters';
$string['catalogue:noresults_help'] = 'Try another word or remove the date filter.';
$string['catalogue:nospaces'] = 'There are no spaces to book yet';
$string['catalogue:title'] = 'Bookings';
$string['catalogue:viewavailability'] = 'See availability';
$string['confirm:book'] = 'Confirm booking';
$string['confirm:day'] = 'Day';
$string['confirm:note'] = 'It is your only booking of this space for this day. You will get a message, and you can cancel it until it starts.';
$string['confirm:range'] = 'Range';
$string['confirm:space'] = 'Space';
$string['confirm:title'] = 'Confirm booking';
$string['confirm:yourtime'] = 'Your time';
$string['confirmdeletespace'] = 'Delete the space "{$a}"? Its image and all its bookings are deleted too. This cannot be undone.';
$string['confirmdeletespacebookings'] = 'It has {$a} upcoming bookings: they will be cancelled and their holders notified.';
$string['day:closed'] = 'Closed';
$string['day:free'] = '{$a} free';
$string['day:full'] = 'Full';
$string['day:mine'] = 'Your booking';
$string['day:past'] = 'Past';
$string['deletespacenamed'] = 'Delete {$a}';
$string['duration:hours'] = '{$a} h';
$string['duration:minutes'] = '{$a} min';
$string['editspace'] = 'Edit space';
$string['editspacenamed'] = 'Edit {$a}';
$string['error:alreadybookedtoday'] = 'You already have a booking of this space on this day. Cancel it to choose another range.';
$string['error:cannotcancel'] = 'You cannot cancel this booking.';
$string['error:datetobeforefrom'] = 'The last bookable day cannot be before the first one.';
$string['error:invalidadmin'] = 'Only people who can manage spaces can be managers of a space.';
$string['error:invalidrange'] = 'Choose a range of consecutive free slots of one day.';
$string['error:noadmins'] = 'Choose at least one manager for the space.';
$string['error:noslotfits'] = 'The opening hours must be at least as long as one slot.';
$string['error:notactive'] = 'This booking has already been cancelled.';
$string['error:noweekdays'] = 'Choose at least one day of the week.';
$string['error:rangeinpast'] = 'This range has already started. Choose a later one.';
$string['error:slottaken'] = 'Somebody has just booked part of this range. Choose another one.';
$string['error:spacenotavailable'] = 'This space is not available for booking.';
$string['event:bookingcancelled'] = 'Booking cancelled';
$string['event:bookingcreated'] = 'Booking created';
$string['event:spacecreated'] = 'Space created';
$string['event:spacedeleted'] = 'Space deleted';
$string['event:spaceupdated'] = 'Space updated';
$string['filter:apply'] = 'Filter';
$string['filter:clear'] = 'Clear';
$string['filter:clearall'] = 'Clear filters';
$string['filter:date'] = 'Free on';
$string['filter:from'] = 'From';
$string['filter:search'] = 'Search';
$string['filter:searchplaceholder'] = 'Name or description';
$string['filter:statusactive'] = 'Active';
$string['filter:statusall'] = 'All';
$string['filter:statuscancelled'] = 'Cancelled';
$string['filter:to'] = 'To';
$string['filter:toggle'] = 'Filters';
$string['filter:userplaceholder'] = 'Name or email';
$string['form:admins'] = 'Managers of the space';
$string['form:admins_help'] = 'They are notified of every booking and cancellation of the space and can cancel any of its bookings. Only people who can manage spaces are listed.';
$string['form:availability'] = 'Availability';
$string['form:datefrom'] = 'Bookable from';
$string['form:dateto'] = 'Bookable until';
$string['form:description'] = 'Description';
$string['form:durationhours'] = '{$a} h';
$string['form:futurebookingskept'] = 'This space has {$a} upcoming bookings. Changing the opening hours or the slot length keeps them as they are.';
$string['form:general'] = 'General';
$string['form:hourfrom'] = 'Opening time';
$string['form:hours'] = 'Opening hours';
$string['form:hourto'] = 'Closing time';
$string['form:image'] = 'Image';
$string['form:name'] = 'Name';
$string['form:name_help'] = 'To show the name in each language, write it with multilang tags, for example: &lt;span lang="en" class="multilang"&gt;3D printer&lt;/span&gt;&lt;span lang="ru" class="multilang"&gt;3D-принтер&lt;/span&gt;. Up to 255 characters, tags included.';
$string['form:noadmins'] = 'No managers chosen';
$string['form:notifications'] = 'Notifications';
$string['form:slotduration'] = 'Length of each slot';
$string['form:slotpreview'] = 'Slots';
$string['form:slotpreviewleftover'] = 'The time from {$a->from} to {$a->to} does not fit a whole slot and will not be offered.';
$string['form:slotpreviewlist'] = 'Slots: {$a}.';
$string['form:slotpreviewnone'] = 'No slot fits in these opening hours.';
$string['form:timezone'] = 'Time zone';
$string['form:timezone_help'] = 'The opening hours and the bookable days are read in this time zone, whatever the time zone of each person.';
$string['form:visible'] = 'Visible';
$string['form:visible_desc'] = 'Shown in the showcase';
$string['form:weekday1'] = 'Monday';
$string['form:weekday2'] = 'Tuesday';
$string['form:weekday3'] = 'Wednesday';
$string['form:weekday4'] = 'Thursday';
$string['form:weekday5'] = 'Friday';
$string['form:weekday6'] = 'Saturday';
$string['form:weekday7'] = 'Sunday';
$string['form:weekdays'] = 'Days of the week';
$string['form:weekdayshort1'] = 'Mon';
$string['form:weekdayshort2'] = 'Tue';
$string['form:weekdayshort3'] = 'Wed';
$string['form:weekdayshort4'] = 'Thu';
$string['form:weekdayshort5'] = 'Fri';
$string['form:weekdayshort6'] = 'Sat';
$string['form:weekdayshort7'] = 'Sun';
$string['grid:cancelmine'] = 'Cancel my booking';
$string['grid:closed'] = 'This space cannot be booked this day.';
$string['grid:full'] = 'No free slots left this day.';
$string['grid:mybooking'] = 'You already have a booking this day: {$a}. Only one per space and day is allowed; to choose another range, cancel this one.';
$string['grid:nextfree'] = 'See the next day with free slots';
$string['grid:nonextfree'] = 'No free slots in the next {$a} days.';
$string['hidespacenamed'] = 'Hide {$a}';
$string['legend:busy'] = 'Taken';
$string['legend:free'] = 'Free';
$string['legend:mine'] = 'Your booking';
$string['legend:range'] = 'Chosen range';
$string['legend:unavailable'] = 'Not available';
$string['managespaces'] = 'Manage spaces';
$string['message:adminbookingcancelled:body'] = 'The booking of {$a->user} for {$a->space} on {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), was cancelled by {$a->canceller}. The time is free again.';
$string['message:adminbookingcancelled:subject'] = 'Booking cancelled: {$a->space}, {$a->day}';
$string['message:adminbookingcreated:body'] = '{$a->user} booked {$a->space} on {$a->day}, {$a->from}–{$a->to} ({$a->timezone}).';
$string['message:adminbookingcreated:subject'] = 'New booking: {$a->space}, {$a->day}';
$string['message:bookingcancelled:body'] = 'Your booking of {$a->space} on {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), has been cancelled. You can book another time from the page of the space.';
$string['message:bookingcancelled:subject'] = 'Booking cancelled: {$a->space}, {$a->day}';
$string['message:bookingconfirmed:body'] = 'Your booking of {$a->space} on {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), is confirmed. You can cancel it from the page of the space until it starts.';
$string['message:bookingconfirmed:subject'] = 'Booking confirmed: {$a->space}, {$a->day}';
$string['messageprovider:adminbookingcancelled'] = 'A booking of a space I manage is cancelled';
$string['messageprovider:adminbookingcreated'] = 'Somebody books a space I manage';
$string['messageprovider:bookingcancelled'] = 'One of my bookings is cancelled';
$string['messageprovider:bookingconfirmed'] = 'One of my bookings is confirmed';
$string['mybookings'] = 'My bookings';
$string['mybookings:cancel'] = 'Cancel';
$string['mybookings:cancelnamed'] = 'Cancel the booking of {$a->space}, {$a->range}';
$string['mybookings:nopast'] = 'You have no past bookings';
$string['mybookings:noupcoming'] = 'You have no upcoming bookings';
$string['mybookings:noupcoming_help'] = 'Find a space and book a free range of time.';
$string['mybookings:past'] = 'Past';
$string['mybookings:seespaces'] = 'See spaces';
$string['mybookings:statuscancelled'] = 'Cancelled';
$string['mybookings:statusconfirmed'] = 'Confirmed';
$string['mybookings:statusfinished'] = 'Finished';
$string['mybookings:statusinprogress'] = 'In progress';
$string['mybookings:upcomingcount'] = 'Upcoming ({$a})';
$string['newspace'] = 'New space';
$string['pluginname'] = 'Space booking';
$string['privacy:metadata:booking'] = 'Bookings of spaces made by people.';
$string['privacy:metadata:booking:cancelledby'] = 'The person who cancelled the booking, if it was cancelled.';
$string['privacy:metadata:booking:spaceid'] = 'The space booked.';
$string['privacy:metadata:booking:status'] = 'Whether the booking is active or cancelled.';
$string['privacy:metadata:booking:timecancelled'] = 'When the booking was cancelled.';
$string['privacy:metadata:booking:timecreated'] = 'When the booking was made.';
$string['privacy:metadata:booking:timeend'] = 'End of the booked range.';
$string['privacy:metadata:booking:timestart'] = 'Start of the booked range.';
$string['privacy:metadata:booking:userid'] = 'The person who made the booking.';
$string['privacy:metadata:core_message'] = 'Messages are sent to the person who books and to the managers of the space when a booking is made or cancelled.';
$string['privacy:metadata:space'] = 'Bookable spaces.';
$string['privacy:metadata:space:timemodified'] = 'When the space was last changed.';
$string['privacy:metadata:space:usermodified'] = 'The person who last changed the space.';
$string['privacy:metadata:space_admin'] = 'Managers of each space, who are notified of its bookings.';
$string['privacy:metadata:space_admin:spaceid'] = 'The space managed.';
$string['privacy:metadata:space_admin:userid'] = 'The manager.';
$string['privacy:path:bookings'] = 'Bookings';
$string['privacy:path:cancelled'] = 'Bookings cancelled for other people';
$string['privacy:path:spaces'] = 'Spaces';
$string['range:book'] = 'Book range';
$string['range:clear'] = 'Clear';
$string['range:hint'] = 'Choose another consecutive free slot to extend it.';
$string['range:notcontiguous'] = 'Choose consecutive free slots only. Start again from the first one.';
$string['range:title'] = 'Chosen range:';
$string['showspacenamed'] = 'Show {$a}';
$string['space:chooseday'] = 'Choose a day';
$string['space:ended'] = 'Bookings closed since {$a}.';
$string['space:nextweek'] = 'Next week';
$string['space:prevweek'] = 'Previous week';
$string['space:rule'] = 'Bookings';
$string['space:rule_desc'] = 'One per person and day; you can choose several consecutive slots.';
$string['space:timesin'] = 'Times in {$a->zone} (UTC{$a->offset})';
$string['space:today'] = 'Back to today';
$string['space:usertime'] = 'Your time zone is {$a}; the confirmation shows the range in both times.';
$string['spacebookings:cancelnamed'] = 'Cancel the booking of {$a->user}, {$a->range}';
$string['spacebookings:cancelquestion'] = 'Cancel the booking of {$a->user}, {$a->range} on {$a->day}? The range will be free for other people and {$a->user} will be told.';
$string['spacebookings:empty'] = 'This space has no bookings that match the filters.';
$string['spacebookings:viewbookings'] = 'View bookings';
$string['spacebookings:viewpage'] = 'View page';
$string['spacebookings:zone'] = 'Times in the time zone of the space: {$a}';
$string['spacedeleted'] = 'The space "{$a}" has been deleted.';
$string['spacesaved'] = 'The space has been saved.';
$string['summary:slots'] = 'Slots of {$a} h';
$string['table:admins'] = 'Managers';
$string['table:adminsmore'] = '{$a->names} and {$a->more} more';
$string['table:bookedon'] = 'Booked on';
$string['table:cancelled'] = 'Cancelled by';
$string['table:day'] = 'Day';
$string['table:days'] = 'Days';
$string['table:futurebookings'] = 'Upcoming bookings';
$string['table:hours'] = 'Opening hours';
$string['table:hoursvalue'] = '{$a->from}–{$a->to} · {$a->timezone}';
$string['table:period'] = 'Bookable period';
$string['table:range'] = 'Range';
$string['table:slot'] = 'Slot';
$string['table:space'] = 'Space';
$string['table:status'] = 'State';
$string['table:user'] = 'Person';
$string['table:visible'] = 'Visible';
$string['tipbooking:book'] = 'Book spaces';
$string['tipbooking:manage'] = 'Manage spaces and their bookings';
$string['tipbooking:view'] = 'View the spaces available for booking';
