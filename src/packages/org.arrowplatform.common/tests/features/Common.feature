Feature: [Common] Check administration panel

  Background:
    Given account "test_dev" with password "321tes_dev" in "Administrators" group

  @javascript
  Scenario: When I gave empty data I should see empty fields warning
    Given I am logged as "test_dev"
    And I am on "/admin"
    Then I should not see "Fatal error"
    And I should not see "Exception occured"


