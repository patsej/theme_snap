# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
#
# Tests for cover image uploading.
#
# @package    theme_snap
# @copyright  Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, cover image can be set for site and courses.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | defaulthomepage | 0 |

  @javascript
  Scenario: Editing teachers can change course cover image.
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher2  | 1        | teacher2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | admin    | C1     | editingteacher |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
    And I log in as "teacher1" (theme_snap)
    And I open the personal menu
    And I follow "Course 1"
    And I wait until the page is ready
   Then I should see "Change image"
    And I should not see cover image in page header
    And I upload cover image "testpng_small.png"
    # Test cancelling upload
    And I wait until ".btn.cancel" "css_element" is visible
    And I click on ".btn.cancel" "css_element"
   Then I should not see cover image in page header
    And I should see "Change image"
    # Test confirming upload
    And I upload cover image "testpng_small.png"
    And I wait until ".btn.ok" "css_element" is visible
    And I click on ".btn.ok" "css_element"
    And I wait until "label[for=\"coverfiles\"]" "css_element" is visible
   Then I should see cover image in page header
    And I log out (theme_snap)
    And I log in as "teacher2" (theme_snap)
    And I open the personal menu
    And I follow "Course 1"
   Then I should not see "Change image"

  @javascript
  Scenario: A cover image cannot exceed the site maximum upload size.
    Given the following config values are set as admin:
      | theme | snap |
      | defaulthomepage | 0 |
      | maxbytes | 2097152 |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1" (theme_snap)
    And I open the personal menu
    And I follow "Course 1"
    And I wait until the page is ready
   Then I should see "Change image"
    And I should not see cover image in page header
    And I upload cover image "bpd_bikes_3888px.jpg"
   Then I should see "Cover image exceeds the site level maximum allowed file size"
    And I upload cover image "testpng_small.png"
   Then I should not see "Cover image exceeds the site level maximum allowed file size"

  @javascript
  Scenario: A warning will be presented if the cover image is of a low resolution.
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1" (theme_snap)
    And I open the personal menu
    And I follow "Course 1"
    And I wait until the page is ready
   Then I should see "Change image"
    And I should not see cover image in page header
    And I upload cover image "testpng_lt800px.png"
   Then I should see "For best quality, we recommend a larger image of at least 800px width"
    And I upload cover image "testpng_small.png"
   Then I should not see "For best quality, we recommend a larger image of at least 800px width"

  @javascript
  Scenario: Admin user can change site cover image.
    Given
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | 1        | user1@example.com    |
    And I log in as "admin" (theme_snap)
    And I am on site homepage
    And I wait until the page is ready
   Then I should see "Change image"
    And I should not see cover image in page header
    And I upload cover image "testpng_small.png"
    # Test cancelling upload
    And I wait until ".btn.cancel" "css_element" is visible
    And I click on ".btn.cancel" "css_element"
   Then I should not see cover image in page header
    And I should see "Change image"
    # Test confirming upload
    And I upload cover image "testpng_small.png"
    And I wait until ".btn.ok" "css_element" is visible
    And I click on ".btn.ok" "css_element"
    And I wait until "label[for=\"coverfiles\"]" "css_element" is visible
   Then I should see cover image in page header
    And I log out (theme_snap)
    And I log in as "user1" (theme_snap)
    And I am on site homepage
    And I wait until the page is ready
   Then I should not see "Change image"


