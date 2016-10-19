Feature: [Settings] Feature test saving settings

  Background:
    Given account "test_admin" with password "321tes_dev" in "Administrators" group
    And I am logged as "test_admin"

  @javascript @settings
  Scenario: I am trying to save access setting
    Given I am on "utils::settings/list"
    When I follow "org.arrowplatform.access"
    And I wait "500"
    And I fill in "data[access][MAX_BAD_LOGIN]" with "23"
    And I follow "zapisz"
    Then the "data[access][MAX_BAD_LOGIN]" element should have value "23"


  @javascript @settings
  Scenario: I am trying to save access setting once more with different value
    Given I am on "utils::settings/list"
    When I follow "org.arrowplatform.access"
    And I wait "500"
    And I fill in "data[access][MAX_BAD_LOGIN]" with "13"
    And I follow "zapisz"
    Then the "data[access][MAX_BAD_LOGIN]" element should have value "13"

