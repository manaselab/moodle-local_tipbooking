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

namespace local_tipbooking\form;

use DateTimeImmutable;
use DateTimeZone;
use local_tipbooking\models\space;

/**
 * Tests for the form of a space and its conversion to the model.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(space_form::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(space_form_data::class)]
final class space_form_test extends \advanced_testcase {
    /**
     * Valid submitted values.
     *
     * @return array The values.
     */
    private function valid_data(): array {
        $data = [
            'name' => 'Room',
            'datefrom' => make_timestamp(2026, 12, 1),
            'dateto' => make_timestamp(2027, 3, 31),
            'hourfrom' => 480,
            'hourto' => 780,
            'slotduration' => 120,
            'timezone' => 'Asia/Bishkek',
            'admins' => [2],
        ];
        for ($day = 1; $day <= 7; $day++) {
            $data['weekday' . $day] = $day <= 5 ? 1 : 0;
        }
        return $data;
    }

    /**
     * A form ready to validate.
     *
     * @return space_form The form.
     */
    private function form(): space_form {
        return new space_form(null, ['managers' => [2 => 'Admin User']]);
    }

    /**
     * Valid values pass.
     *
     * @return void
     */
    public function test_valid(): void {
        $this->resetAfterTest();
        $this->assertSame([], $this->form()->validation($this->valid_data(), []));
    }

    /**
     * Each rule of the form gives its error.
     *
     * @return void
     */
    public function test_errors(): void {
        $this->resetAfterTest();
        $data = $this->valid_data();
        $data['dateto'] = make_timestamp(2026, 11, 1);
        $data['hourto'] = 540;
        for ($day = 1; $day <= 7; $day++) {
            $data['weekday' . $day] = 0;
        }
        $data['admins'] = [];

        $errors = $this->form()->validation($data, []);

        $this->assertArrayHasKey('dateto', $errors);
        $this->assertArrayHasKey('hours', $errors);
        $this->assertArrayHasKey('weekdays', $errors);
        $this->assertSame(get_string('error:noadmins', 'local_tipbooking'), $errors['admins']);

        $data = $this->valid_data();
        $data['admins'] = [99];
        $this->assertSame(
            get_string('error:invalidadmin', 'local_tipbooking'),
            $this->form()->validation($data, [])['admins']
        );
    }

    /**
     * The calendar day travels between the time zone of the editor and the one of the space.
     *
     * @return void
     */
    public function test_days_keep_across_time_zones(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['timezone' => 'Europe/London']);
        $this->setUser($user);

        $data = (object) $this->valid_data();
        $data->description = '';
        $space = space_form_data::to_space($data, new space());

        $zone = new DateTimeZone('Asia/Bishkek');
        $local = fn(int $timestamp): string => (new DateTimeImmutable('@' . $timestamp))->setTimezone($zone)->format('Y-m-d H:i');
        $this->assertSame('2026-12-01 00:00', $local($space->datefrom));
        $this->assertSame('2027-03-31 00:00', $local($space->dateto));
        $this->assertSame(31, $space->weekdays);

        $space->id = 1;
        $back = space_form_data::to_form($space, [2]);
        $this->assertSame(make_timestamp(2026, 12, 1), $back->datefrom);
        $this->assertSame(1, $back->weekday1);
        $this->assertSame(0, $back->weekday6);
    }
}
