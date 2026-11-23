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

namespace local_tipbooking;

use context_system;
use local_tipbooking\models\space;
use stdClass;

/**
 * Tests for the notifications of bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(notifier::class)]
final class notifier_test extends \advanced_testcase {
    /** @var space The space under test. */
    private space $space;

    /** @var stdClass[] The two managers of the space. */
    private array $managers;

    /** @var array Slots of a day four days ahead. */
    private array $slots;

    /**
     * Creates a space with two managers.
     *
     * @return void
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $this->managers = [];
        foreach ([1, 2] as $unused) {
            $manager = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->role_assign($roleid, $manager->id, context_system::instance()->id);
            $this->managers[] = $manager;
        }

        $generator = $this->getDataGenerator()->get_plugin_generator('local_tipbooking');
        $this->space = $generator->create_space([
            'name' => 'Prusa MK4',
            'admins' => array_column($this->managers, 'id'),
        ]);
        $this->slots = (new slot_calculator($this->space))->day_slots($generator->day_ahead($this->space, 4));
    }

    /**
     * Messages of a sink, by recipient and provider.
     *
     * @param  array $messages Messages caught by the sink.
     * @return array           List of "userid:provider".
     */
    private function summarise(array $messages): array {
        $summary = array_map(fn($m) => $m->useridto . ':' . $m->eventtype, $messages);
        sort($summary);
        return $summary;
    }

    /**
     * Booking sends a confirmation to the holder and a notice to each manager.
     *
     * @return void
     */
    public function test_booking_created(): void {
        $holder = $this->getDataGenerator()->create_user();
        $sink = $this->redirectMessages();

        booking_manager::book($this->space->id, $holder->id, $this->slots[0]['start'], $this->slots[1]['end']);

        $messages = $sink->get_messages();
        $expected = [
            $holder->id . ':bookingconfirmed',
            $this->managers[0]->id . ':adminbookingcreated',
            $this->managers[1]->id . ':adminbookingcreated',
        ];
        sort($expected);
        $this->assertSame($expected, $this->summarise($messages));

        $tome = array_values(array_filter($messages, fn($m) => (int) $m->useridto === (int) $holder->id))[0];
        $this->assertStringContainsString('Prusa MK4', $tome->subject);
        $this->assertStringContainsString('Asia/Bishkek', $tome->fullmessage);
        $this->assertStringContainsString('/local/tipbooking/space.php', $tome->contexturl);
    }

    /**
     * Cancelling sends a notice to the holder and to each manager.
     *
     * @return void
     */
    public function test_booking_cancelled(): void {
        $holder = $this->getDataGenerator()->create_user();
        $booking = booking_manager::book($this->space->id, $holder->id, $this->slots[0]['start'], $this->slots[0]['end']);
        $sink = $this->redirectMessages();

        booking_manager::cancel($booking->id, $this->managers[0]->id);

        $expected = [
            $holder->id . ':bookingcancelled',
            $this->managers[0]->id . ':adminbookingcancelled',
            $this->managers[1]->id . ':adminbookingcancelled',
        ];
        sort($expected);
        $this->assertSame($expected, $this->summarise($sink->get_messages()));
    }

    /**
     * A manager who books gets the message of the holder only.
     *
     * @return void
     */
    public function test_manager_who_books_gets_one_message(): void {
        $sink = $this->redirectMessages();

        booking_manager::book($this->space->id, $this->managers[0]->id, $this->slots[2]['start'], $this->slots[2]['end']);

        $expected = [
            $this->managers[0]->id . ':bookingconfirmed',
            $this->managers[1]->id . ':adminbookingcreated',
        ];
        sort($expected);
        $this->assertSame($expected, $this->summarise($sink->get_messages()));
    }
}
