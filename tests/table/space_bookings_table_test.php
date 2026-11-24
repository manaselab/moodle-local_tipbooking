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

namespace local_tipbooking\table;

use local_tipbooking\booking_manager;
use local_tipbooking\form\space_bookings_filter_form as filters;
use local_tipbooking\models\booking;
use local_tipbooking\slot_calculator;

/**
 * Tests for the filters of the bookings of a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(space_bookings_table::class)]
final class space_bookings_table_test extends \advanced_testcase {
    /** @var int Identifier of the space. */
    private int $spaceid;

    /** @var array Name => booking id. */
    private array $bookings = [];

    /** @var array Name => day of the booking, as YYYYMMDD. */
    private array $days = [];

    /**
     * Three bookings of a space, by three people on two days; one of them cancelled.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_tipbooking');
        $space = $generator->create_space();
        $other = $generator->create_space();
        $this->spaceid = $space->id;
        $calculator = new slot_calculator($space);

        foreach ([['Aida', 'Usenova', 2], ['Timur', 'Bekov', 2], ['Elif', 'Kaya', 5]] as [$first, $last, $ahead]) {
            $user = $this->getDataGenerator()->create_user([
                'firstname' => $first,
                'lastname' => $last,
                'email' => strtolower($first) . '@example.com',
            ]);
            $date = $generator->day_ahead($space, $ahead);
            $slots = $calculator->day_slots($date);
            $index = $first === 'Timur' ? 3 : 0;
            $slot = $slots[$index];
            $this->bookings[$first] = $generator->create_booking([
                'spaceid' => $space->id, 'userid' => $user->id,
                'timestart' => $slot['start'], 'timeend' => $slot['end'],
            ])->id;
            $this->days[$first] = slot_calculator::day_key($date);

            // A booking of the same person in another space never shows.
            $otherslot = (new slot_calculator($other))->day_slots($date)[$index];
            $generator->create_booking([
                'spaceid' => $other->id, 'userid' => $user->id,
                'timestart' => $otherslot['start'], 'timeend' => $otherslot['end'],
            ]);
        }
        booking_manager::cancel($this->bookings['Elif'], (int) get_admin()->id);
    }

    /**
     * Names of the bookings that match some filters.
     *
     * @param  array $filters The filters.
     * @return array          Names, sorted.
     */
    private function matching(array $filters): array {
        global $DB;

        [$where, $params] = space_bookings_table::filter_sql($this->spaceid, $filters);
        $ids = $DB->get_fieldset_sql(
            'SELECT b.id FROM {' . booking::TABLE . '} b JOIN {user} u ON u.id = b.userid WHERE ' . $where,
            $params
        );
        $names = array_keys(array_intersect($this->bookings, array_map('intval', $ids)));
        sort($names);
        return $names;
    }

    /**
     * Without filters every booking of the space shows; the state filter splits them.
     *
     * @return void
     */
    public function test_status(): void {
        $this->assertSame(['Aida', 'Elif', 'Timur'], $this->matching([]));
        $this->assertSame(['Aida', 'Timur'], $this->matching(['status' => filters::STATUS_ACTIVE]));
        $this->assertSame(['Elif'], $this->matching(['status' => filters::STATUS_CANCELLED]));
    }

    /**
     * The days include both ends.
     *
     * @return void
     */
    public function test_days(): void {
        $this->assertSame(['Aida', 'Timur'], $this->matching(['to' => $this->days['Aida']]));
        $this->assertSame(['Elif'], $this->matching(['from' => $this->days['Aida'] + 1]));
        $this->assertSame(['Elif'], $this->matching(['from' => $this->days['Elif'], 'to' => $this->days['Elif']]));
    }

    /**
     * The person is found by part of the full name or of the email, in any case.
     *
     * @return void
     */
    public function test_user(): void {
        $this->assertSame(['Timur'], $this->matching(['user' => 'tIMUR be']));
        $this->assertSame(['Aida'], $this->matching(['user' => 'aida@']));
        $this->assertSame([], $this->matching(['user' => '%']));
    }

    /**
     * The table loads its rows with the filters and downloads without the actions column.
     *
     * @return void
     */
    public function test_rows(): void {
        $space = \local_tipbooking\space_manager::get($this->spaceid);
        $table = new space_bookings_table($space, new \core\url('/local/tipbooking/bookings.php'), [
            'status' => filters::STATUS_ALL,
        ]);
        $table->setup();
        $table->query_db(20, false);

        $this->assertCount(3, $table->rawdata);
        $first = reset($table->rawdata);
        $this->assertStringContainsString('Aida Usenova', $table->col_bookername($first));
        $this->assertStringContainsString('local_tipbooking-cancel', $table->col_actions($first));

        $cancelled = $table->rawdata[$this->bookings['Elif']];
        $this->assertSame('', $table->col_actions($cancelled));
        $this->assertStringContainsString('Admin User', $table->col_timecancelled($cancelled));
    }
}
