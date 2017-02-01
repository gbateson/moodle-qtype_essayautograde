@qtype @qtype_essayautograde
Feature: Test duplicating a quiz containing an Assay question
  As a teacher
  In order re-use my courses containing Essay questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name      | template         |
      | Test questions   | essayautograde     | essayautograde-001 | editor           |
      | Test questions   | essayautograde     | essayautograde-002 | editorfilepicker |
      | Test questions   | essayautograde     | essayautograde-003 | plain            |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | essayautograde-001 | 1 |
      | essayautograde-002 | 1 |
      | essayautograde-003 | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"

  @javascript
  Scenario: Backup and restore a course containing 3 Essay questions
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I navigate to "Question bank" node in "Course administration"
    And I should see "essayautograde-001"
    And I should see "essayautograde-002"
    And I should see "essayautograde-003"
    And I click on "Edit" "link" in the "essayautograde-001" "table_row"
    Then the following fields match these values:
      | Question name              | essayautograde-001                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | HTML editor                                             |
      | Require text               | Require the student to enter text                       |
    And I press "Cancel"
    And I click on "Edit" "link" in the "essayautograde-002" "table_row"
    Then the following fields match these values:
      | Question name              | essayautograde-002                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | HTML editor with file picker                            |
      | Require text               | Require the student to enter text                       |
    And I press "Cancel"
    And I click on "Edit" "link" in the "essayautograde-003" "table_row"
    Then the following fields match these values:
      | Question name              | essayautograde-003                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | Plain text                                              |
      | Require text               | Require the student to enter text                       |
