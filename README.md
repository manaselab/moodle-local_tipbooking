<p align="center">
  <img src="pix/icon.svg" alt="" height="100">
</p>

<h1 align="center">Space booking</h1>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.0-informational" alt="Version">
  <a href="https://moodle.org"><img src="https://img.shields.io/badge/Moodle-4.5%2B-orange?logo=moodle" alt="Moodle"></a>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/License-GPL--3.0-green" alt="License">
  <a href="https://tresipunt.com"><img src="https://img.shields.io/badge/made%20by-Tresipunt-F84015" alt="Made by Tresipunt"></a>
</p>

<p align="center"><b>A simple showcase of bookable spaces: pick a free range of time slots and it is yours.</b></p>

A local plugin that lists bookable spaces (rooms, labs, printers, classrooms) once each, with
search and an availability filter. Any signed-in user books a range of consecutive time slots
of one day, and the range is blocked for everybody else at once. There is no approval step and
no waiting list. It does not modify Moodle core or any theme.

> **Languages:** English, Russian, Kyrgyz and Turkish.

---

## ✨ What it does

- **Spaces with their own schedule** — multilingual name and description, image, bookable
  date range, opening hours, slot length (1 h, 2 h, 5 h…), bookable weekdays and time zone.
- **Showcase** — each space is shown once, as a card, with text search and a "free on" date
  filter.
- **Booking by range** — a booking is a range of consecutive slots of one day, from one slot
  to the whole opening hours; one booking per person, space and day.
- **Cancellation** — people cancel their own upcoming bookings; space managers cancel any.
- **Notifications** — the person who books and the managers of the space are notified when a
  booking is made or cancelled.
- **Calendar** — each booking adds an event with the same start and end to the calendar of
  the person who booked; nobody else sees it. It goes away when the booking is cancelled, and
  it cannot be edited by hand.

## 🧭 Use cases

### A maker lab lending its equipment

A lab has a 3D printer and a laser cutter. Each one is a space with 1-hour slots on weekdays.
Students book the hours they need and the lab technician is notified of every booking.

### Meeting rooms for a startup programme

Mentors and teams book a meeting room in 2-hour slots. Each person can hold one booking per
room and day, which keeps the room available for everyone.

## 📋 Requirements

| Requirement | Version |
|---|---|
| Moodle | 4.5 or later (tested on 5.2) |
| PHP | 8.1 or later |
| Other plugins | None |

## 🚀 Installation

1. Copy the code to `local/tipbooking/` (`public/local/tipbooking/` on Moodle 5.1 and later).
2. Complete the installation from **Site administration › Notifications**
   (or `php admin/cli/upgrade.php --non-interactive`).
3. Purge the caches (**Site administration › Development › Purge caches**
   or `php admin/cli/purge_caches.php`).

## 🗺️ Pages

| Page | Who | What |
|---|---|---|
| `/local/tipbooking/index.php` | Signed-in users | The showcase, with text search and the "free on" filter |
| `/local/tipbooking/space.php?id=` | Signed-in users | A space: its week, the slots of a day, booking a range and cancelling one's own |
| `/local/tipbooking/mybookings.php` | Signed-in users | One's upcoming and past bookings, with cancellation |
| `/local/tipbooking/manage.php` | Managers | Every space: create, edit, hide, delete |
| `/local/tipbooking/bookings.php?spaceid=` | Managers of the space | The bookings of one space, with filters, download and cancellation |

**Names and descriptions in several languages** use the multilang filter. In the description,
write one `<span lang="xx" class="multilang">` per language **inside** each paragraph: a
span wrapped around a paragraph is not valid HTML, and Moodle cleans it before the filter
runs, so every language shows at once.

## 🔐 Permissions

| Capability | Default | Allows |
|---|---|---|
| `local/tipbooking:view` | Authenticated user | Browse the spaces and their availability |
| `local/tipbooking:book` | Authenticated user | Book and cancel one's own bookings (never guests) |
| `local/tipbooking:manage` | Manager | Manage spaces and any booking |

## 🔧 Settings

This plugin has no settings page. Spaces are managed from
**Site administration › Plugins › Local plugins › Manage spaces**.

## 🗑️ Uninstalling

Uninstalling from **Site administration › Plugins › Plugins overview** removes the spaces,
their images, every booking and their calendar events.

## 📄 License

[GNU GPL v3 or later](https://www.gnu.org/copyleft/gpl.html) — 2026 [Tresipunt](https://tresipunt.com) (contacte@tresipunt.com)

---

<p align="center">
  <a href="https://tresipunt.com"><img src="pix/tresipunt_logo.svg" alt="Tresipunt" height="100"></a>
</p>
