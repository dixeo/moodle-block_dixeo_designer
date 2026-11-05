@block @block_dixeo_designer
Feature: The course designer asks for the Dixeo Terms of Use
  In order to send course data to Dixeo only with my agreement
  As a user who can design courses
  I need to accept the current Dixeo Terms of Use before I use the course designer

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | designer | Dana      | Designer | designer@example.com |
    And the following "roles" exist:
      | shortname    | name          | archetype |
      | dixeocreator | Dixeo creator |           |
    And the following "role capability" exists:
      | role               | dixeocreator |
      | local/dixeo:create | allow        |
    And the following "role assigns" exist:
      | user     | role         | contextlevel | reference |
      | designer | dixeocreator | System       |           |

  @javascript
  Scenario: A user accepts the terms in the designer dialog and returns to the designer
    Given I log in as "designer"
    When I visit "/blocks/dixeo_designer/designer.php"
    Then I should see "Terms of Use and Privacy Notice"
    And I set the field "local-dixeo-consent-terms" to "1"
    And I set the field "local-dixeo-consent-processing" to "1"
    And I click on "Continue" "button" in the "[data-region='local-dixeo-consent-lock']" "css_element"
    And I should not see "Terms of Use and Privacy Notice"
    And "[data-region='local-dixeo-consent-lock']" "css_element" should not exist
    And the url should match "/blocks/dixeo_designer/designer\.php"

  Scenario: A user who already accepted the terms sees no dialog
    Given the following "local_dixeo > terms acceptances" exist:
      | user     |
      | designer |
    And I log in as "designer"
    When I visit "/blocks/dixeo_designer/designer.php"
    Then "[data-region='local-dixeo-consent-lock']" "css_element" should not exist
