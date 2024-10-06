@mod @mod_mrproject
Feature: Teachers are warned about scheduling conflicts
  In order to create useful meetings
  As a teacher
  I need to take care not to create conflicting schedules.

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname | email                |
      | manager1 | Manager     | 1        | manager1@example.com |
      | teacher1 | Teacher     | 1        | teacher1@example.com |
      | teacher2 | Teacher     | 2        | teacher2@example.com |
      | student1 | Student     | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | teacher1  | C1 | editingteacher |
      | teacher2  | C1 | editingteacher |
      | student1  | C1 | student        |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "activities" exist:
      | activity  | name             | intro | course | idnumber   | groupmode | mrprojectmode | maxbookings |
      | mrproject | Test mrproject A | n     | C1     | mrprojectA | 0         | oneonly       | 1           |
      | mrproject | Test mrproject B | n     | C1     | mrprojectB | 0         | oneonly       | 1           |

  @javascript
  Scenario: A teacher edits a single meeting and is warned about conflicts

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add 5 meetings 5 days ahead in "Test mrproject A" mrproject and I fill the form with:
      | Location | My office |
    And I am on "Course 1" course homepage
    And I add a meeting 5 days ahead at 1000 in "Test mrproject B" mrproject and I fill the form with:
      | Location | My office |

    When I am on "Course 1" course homepage
    And I follow "Test mrproject A"
    And I click on "Edit" "link" in the "2:00 AM" "table_row"
    And I set the following fields to these values:
      | starttime[minute] | 40 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And "Save changes" "button" should exist
    And I should see "3:00 AM"
    And I should not see "2:00 AM"

    When I set the following fields to these values:
      | starttime[hour]   | 09 |
      | starttime[minute] | 55 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "in course C1, mrproject Test mrproject B"
    And I should see "10:00 AM"
    And I should not see "2:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 09 |
      | starttime[minute] | 55 |
      | Ignore scheduling conflicts | 1 |
    And I click on "Save changes" "button"
    Then I should see "meeting updated"
    And "9:55 AM" "table_row" should exist
    And I log out

  @javascript
  Scenario: A manager edits meetings for several teachers, creating conflicts

    Given I log in as "manager1"
    And I follow "Site home"
    And I navigate to "Turn editing on" in current page administration
    And I add the "Navigation" block if not present
    And I click on "Courses" "link" in the "Navigation" "block"
    And I am on "Course 1" course homepage
    And I add 6 meetings 5 days ahead in "Test mrproject A" mrproject and I fill the form with:
      | Location | Office T1 |
      | Teacher  | Teacher 1 |
    And I am on "Course 1" course homepage
    And I add 5 meetings 5 days ahead in "Test mrproject B" mrproject and I fill the form with:
      | Location | Office T2 |
      | Teacher  | Teacher 2 |

    When I am on "Course 1" course homepage
    And I follow "Test mrproject A"
    And I click on "Edit" "link" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | starttime[hour]   | 6  |
      | starttime[minute] | 40 |
      | duration          | 5  |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "6:00 AM"
    And I should see "in this mrproject"
    And I should not see "3:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 5  |
      | starttime[minute] | 40 |
      | duration          | 5  |
      | Teacher           | Teacher 2 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "5:00 AM"
    And I should see "in course C1, mrproject Test mrproject B"
    And I should not see "3:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 6  |
      | starttime[minute] | 40 |
      | duration          | 5  |
      | Teacher           | Teacher 2 |
    And I click on "Save changes" "button"
    Then I should not see "conflict"
    And I should see "meeting updated"
    And "6:40 AM" "table_row" should exist
    And "Save changes" "button" should not exist
    And I log out

  @javascript
  Scenario: A teacher adds a series of meetings, creating conflicts

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add a meeting 5 days ahead at 0125 in "Test mrproject A" mrproject and I fill the form with:
      | Location  | My office |
      | duration  | 15        |
    # Blocks 3 other meetings on a 1-hour grid
    And I am on "Course 1" course homepage
    And I add a meeting 5 days ahead at 0225 in "Test mrproject A" mrproject and I fill the form with:
      | Location  | My office |
      | duration  | 100       |
    # Booked meeting - must not be deleted as conflict
    And I am on "Course 1" course homepage
    And I add a meeting 5 days ahead at 0855 in "Test mrproject A" mrproject and I fill the form with:
      | Location  | My office |
      | duration  | 10        |
      | studentid[0]  | Student 1 |
    # Slot in other mrproject - must not be deleted as conflict
    And I am on "Course 1" course homepage
    And I add a meeting 5 days ahead at 0605 in "Test mrproject B" mrproject and I fill the form with:
      | Location  | My office |
      | duration  | 20        |

    When I am on "Course 1" course homepage
    And I add 10 meetings 5 days ahead in "Test mrproject A" mrproject and I fill the form with:
      | Location | Lecture hall |
    Then I should see "conflicting meetings"
    And I should not see "deleted"
    And I should see "4 meetings have been added"
    And  "1:25 AM" "table_row" should exist
    And  "2:25 AM" "table_row" should exist
    And  "8:55 AM" "table_row" should exist
    And  "1:00 AM" "table_row" should not exist
    And  "2:00 AM" "table_row" should not exist
    And  "3:00 AM" "table_row" should not exist
    And  "4:00 AM" "table_row" should not exist
    And  "5:00 AM" "table_row" should exist
    And  "6:00 AM" "table_row" should not exist
    And  "7:00 AM" "table_row" should exist
    And  "8:00 AM" "table_row" should exist
    And  "9:00 AM" "table_row" should not exist
    And "10:00 AM" "table_row" should exist
    And I am on "Course 1" course homepage
    And I follow "Test mrproject B"
    And "6:05 AM" "table_row" should exist

    When I am on "Course 1" course homepage
    And I add 10 meetings 5 days ahead in "Test mrproject A" mrproject and I fill the form with:
      | Location | Lecture hall |
      | Force when overlap | 1  |
    Then I should see "conflicting meetings"
    And I should see "deleted"
    And I should see "8 meetings have been added"
    And  "1:25 AM" "table_row" should not exist
    And  "2:25 AM" "table_row" should not exist
    And  "9:55 AM" "table_row" should not exist
    And  "1:00 AM" "table_row" should exist
    And  "2:00 AM" "table_row" should exist
    And  "3:00 AM" "table_row" should exist
    And  "4:00 AM" "table_row" should exist
    And  "5:00 AM" "table_row" should exist
    And  "6:00 AM" "table_row" should not exist
    And  "7:00 AM" "table_row" should exist
    And  "8:00 AM" "table_row" should exist
    And  "9:00 AM" "table_row" should not exist
    And "10:00 AM" "table_row" should exist
    And I am on "Course 1" course homepage
    And I follow "Test mrproject B"
    And "6:05 AM" "table_row" should exist

    And I log out
