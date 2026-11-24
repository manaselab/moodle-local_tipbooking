# Changelog — local_tipbooking

All notable changes to this plugin are documented here.

## 1.0.0 — 2026-11-24 (`2026112400`)

First release: a showcase of bookable spaces where any signed-in user books a range of
consecutive time slots of one day. Requires Moodle 4.5 or later; tested on Moodle 5.2.

### Added

- **Spaces** — managers create, edit, hide and delete spaces from *Site administration ›
  Plugins › Local plugins › Manage spaces*. Each space has a multilingual name and
  description, an image, a bookable date range, opening hours, a slot length, bookable
  weekdays, a time zone and one or more managers. The form previews the slots the opening
  hours give.
- **Showcase** — each visible space is shown once, as a card, with text search and a "free
  on" date filter that can be shared as a link.
- **Booking by range** — on the page of a space, people choose a day of the week and a
  range of consecutive free slots, and confirm it in a dialog that shows the range in the
  time of the space and in their own. The range is blocked for everybody else at once, also
  when two people confirm at the same time. One booking per person, space and day; no
  approval and no waiting list.
- **My bookings** — upcoming and past bookings, grouped by day, with cancellation of the
  ones that have not started.
- **Bookings of a space** — managers of a space see its bookings in a table with filters by
  day, state and person, download them in every format of the site, and cancel any booking
  that has not ended.
- **Notifications** — the person who books and the managers of the space are notified when a
  booking is made or cancelled, through the messaging preferences of each person.
- **Web services** — `local_tipbooking_get_day_slots`, `local_tipbooking_book_range` and
  `local_tipbooking_cancel_booking`, for the pages of the plugin (AJAX only).
- **Privacy** — bookings, managers and the last change of each space are exported and
  deleted through the privacy API.
- **Languages** — English, Russian, Kyrgyz and Turkish.

### Notes

- Names and descriptions in several languages use the multilang filter. In descriptions,
  put one `<span lang="xx" class="multilang">` per language inside each paragraph.
