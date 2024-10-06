@mod @mod_mrproject
Feature: Teachers can write notes on meetings and tasks
  In order to record details about a meeting
  As a teacher
  I need to enter notes for the task

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | edteacher1 | Editingteacher | 1        | edteacher1@example.com |
      | neteacher1 | Nonedteacher   | 1        | neteacher1@example.com |
      | student1   | Student        | 1        | student1@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | edteacher1 | C1     | editingteacher |
      | neteacher1 | C1     | teacher        |
      | student1   | C1     | student        |
    And the following "activities" exist:
      | activity  | name               | intro | course | idnumber   | usenotes |
      | mrproject | Test mrproject     | n     | C1     | mrprojectn | 3        |
    And I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I add 5 meetings 10 days ahead in "Test mrproject" mrproject and I fill the form with:
      | Location  | Here |
    And I log out

  @javascript
  Scenario: Teachers can enter meeting notes and task notes for others to see
    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I follow "Statistics"
    And I follow "All tasks"
    And I click on "Edit" "link" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Comments | Note-for-meeting |
    And I click on "Save" "button"
    Then I should see "meeting updated"
    When I click on "Edit" "link" in the "4:00 AM" "table_row"
    Then I should see "Note-for-meeting"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    Then I should see "Note-for-meeting" in the "4:00 AM" "table_row"
    When I click on "Book meeting" "button" in the "4:00 AM" "table_row"
    Then I should see "Note-for-meeting"
    And I log out

    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I follow "Statistics"
    And I follow "All tasks"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should see ", 4:00 AM" in the "Date and time" "table_row"
    And I should see "4:45 AM" in the "Date and time" "table_row"
    And I should see "Editingteacher 1" in the "Teacher" "table_row"
    And I set the following fields to these values:
      | Attended | 1 |
      | Notes for task (visible to student) | note-for-task |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-task"
    And I should see "note-confidential"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    Then I should see "Attended meetings"
    And I should see "note-for-task"
    And I should not see "note-confidential"
    And I log out

  @javascript
  Scenario: Teachers see only the comments fields specified in the configuration

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I click on "Book meeting" "button" in the "4:00 AM" "table_row"
    Then I should see "Upcoming meetings"
    And I log out

    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I follow "Statistics"
    And I follow "All tasks"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Notes for task (visible to student) | note-for-task |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-task"
    And I should see "note-confidential"

    When I follow "Test mrproject"
    And I navigate to "Edit settings" in current page administration
    And I set the field "Use notes for tasks" to "0"
    And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should not see "Notes for task"
    And I should not see "note-for-task"
    And I should not see "Confidential notes"
    And I should not see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    Then I should not see "note-for-task"
    And I should not see "note-confidential"
    And I log out

    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I navigate to "Edit settings" in current page administration
    And I set the field "Use notes for tasks" to "1"
    And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should see "Notes for task"
    And I should see "note-for-task"
    And I should not see "Confidential notes"
    And I should not see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    Then I should see "note-for-task"
    And I should not see "note-confidential"
    And I log out

    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I navigate to "Edit settings" in current page administration
    And I set the field "Use notes for tasks" to "2"
    And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should not see "Notes for task"
    And I should not see "note-for-task"
    And I should see "Confidential notes"
    And I should see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    Then I should not see "note-for-task"
    And I should not see "note-confidential"
    And I log out

    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test mrproject"
    And I navigate to "Edit settings" in current page administration
    And I set the field "Use notes for tasks" to "3"
    And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should see "Notes for task"
    And I should see "note-for-task"
    And I should see "Confidential notes"
    And I should see "note-confidential"
    And I log out
