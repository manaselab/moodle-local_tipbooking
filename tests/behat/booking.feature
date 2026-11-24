@local @local_tipbooking
Feature: Book a range of slots of a space
  In order to use the rooms, laboratories and equipment of the hub
  As a registered user
  I need to book a free range of time, find it in my bookings and cancel it

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Mia       | Manager  | manager1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  @javascript
  Scenario: A manager creates a space
    Given I log in as "manager1"
    And I visit "/local/tipbooking/manage.php"
    When I press "New space"
    And I set the following fields to these values:
      | Name                  | Ala Too meeting room |
      | Managers of the space | Mia Manager          |
    And I press "Save changes"
    Then I should see "The space has been saved."
    And I should see "Ala Too meeting room"

  @javascript
  Scenario: A user books two consecutive slots and finds them in My bookings
    Given the following "local_tipbooking > spaces" exist:
      | name  | admins   | hourfrom | hourto | slothours | startsin | weekdays |
      | Lab 1 | manager1 | 09:00    | 13:00  | 1         | 3        | 127      |
    And I log in as "student1"
    And I visit "/local/tipbooking/index.php"
    And I follow "Lab 1"
    When I click on "09:00–10:00" "button"
    And I click on "10:00–11:00" "button"
    Then I should see "09:00–11:00 · 2 h" in the "[data-region='local_tipbooking-rangebar']" "css_element"
    And I press "Book range"
    And I click on "Confirm booking" "button" in the "Confirm booking" "dialogue"
    And I should see "You already have a booking this day: 09:00–11:00."
    And I visit "/local/tipbooking/mybookings.php"
    And I should see "Lab 1"
    And I should see "09:00–11:00 · 2 h · Asia/Bishkek"
    And I should see "Confirmed"

  @javascript
  Scenario: A user cancels an upcoming booking from My bookings
    Given the following "local_tipbooking > spaces" exist:
      | name  | admins   | hourfrom | hourto | slothours | startsin | weekdays |
      | Lab 1 | manager1 | 09:00    | 13:00  | 1         | 3        | 127      |
    And the following "local_tipbooking > bookings" exist:
      | space | user     | daysahead | from  | to    |
      | Lab 1 | student1 | 3         | 09:00 | 11:00 |
    And I log in as "student1"
    And I visit "/local/tipbooking/mybookings.php"
    When I click on "Cancel" "button" in the "Lab 1" "list_item"
    And I click on "Cancel booking" "button" in the "Cancel booking" "dialogue"
    Then I should see "Booking cancelled. The time is free again."
    And I should see "Cancelled" in the "Lab 1" "list_item"
    And "Cancel" "button" should not exist in the "Lab 1" "list_item"
