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
 * Texts that describe a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\output;

use context_system;
use local_tipbooking\models\space;

/**
 * Writes the period, the opening hours, the slot length and the days of a space, in the
 * language of the reader and the time zone of the space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_summary {
    /**
     * Name of the space, ready to print.
     *
     * @param  space  $space The space.
     * @return string        The name in the language of the reader.
     */
    public static function name(space $space): string {
        return format_string($space->name, true, ['context' => context_system::instance()]);
    }

    /**
     * Description of the space, ready to print.
     *
     * @param  space  $space The space.
     * @return string        The description as HTML.
     */
    public static function description(space $space): string {
        return format_text($space->description, $space->descriptionformat, ['context' => context_system::instance()]);
    }

    /**
     * Start of the description as plain text.
     *
     * @param  space  $space  The space.
     * @param  int    $length Characters at most.
     * @return string         The excerpt.
     */
    public static function excerpt(space $space, int $length = 160): string {
        $text = trim(html_to_text(self::description($space), 0, false));
        return shorten_text($text, $length);
    }

    /**
     * Bookable period.
     *
     * @param  space  $space The space.
     * @return string        "1 Dec 2026 – 31 Mar 2027".
     */
    public static function period(space $space): string {
        $zone = $space->get_timezone()->getName();
        $format = get_string('strftimedate', 'langconfig');
        return userdate($space->datefrom, $format, $zone) . ' – ' . userdate($space->dateto, $format, $zone);
    }

    /**
     * Opening hours with the time zone.
     *
     * @param  space  $space The space.
     * @return string        "08:30–17:30 · Asia/Bishkek".
     */
    public static function hours(space $space): string {
        return get_string('table:hoursvalue', 'local_tipbooking', (object) [
            'from' => self::clock($space->hourfrom),
            'to' => self::clock($space->hourto),
            'timezone' => $space->get_timezone()->getName(),
        ]);
    }

    /**
     * Slot length.
     *
     * @param  space  $space The space.
     * @return string        "Slots of 2 h".
     */
    public static function slot(space $space): string {
        return get_string('summary:slots', 'local_tipbooking', round($space->slotduration / 60, 2));
    }

    /**
     * Bookable days of the week.
     *
     * @param  space  $space The space.
     * @return string        "Mon, Tue, Wed, Thu, Fri".
     */
    public static function days(space $space): string {
        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            if ($space->is_weekday_bookable($day)) {
                $days[] = get_string('form:weekdayshort' . $day, 'local_tipbooking');
            }
        }
        return implode(', ', $days);
    }

    /**
     * Minutes from midnight as HH:MM.
     *
     * @param  int    $minutes Minutes from midnight.
     * @return string          The time.
     */
    public static function clock(int $minutes): string {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
