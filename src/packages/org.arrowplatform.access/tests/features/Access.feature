Feature: [Access] Login screen, and access management

  Background:
    Given account "test_admin" with password "321tes_dev" in "Administrators" group

  @javascript @access
  Scenario: When I gave empty data I should see empty fields warning
    Given I am on "access::auth/login"
    When I fill in "inputLogin" with ""
    And I fill in "inputPass" with ""
    And I press "Zaloguj"
    Then should see "Podaj login"
    And I wait for ajax
    And should see "Podaj hasło"

  @javascript @access
  Scenario: When I gave wrong data I should see warning
    Given I am on "access::auth/login"
    When I fill in "inputLogin" with "123"
    And I fill in "inputPass" with "xyz"
    And I press "Zaloguj"
    And I wait for ajax

    Then should see "Podany login lub hasło nie zgadzają się."

  @javascript @access
  Scenario: When I gave proper data I should be logged
    Given I am on "access::auth/login"
    When I fill in "inputLogin" with "test_admin"
    And I fill in "inputPass" with "321tes_dev"
    And I press "Zaloguj"
    And I wait for ajax
    Then should see "3code Panel"

  @javascript @access
  Scenario: I want to logout
    Given I am on "access::auth/login"
    And I am logged as "test_admin"
    When I go to "access::/users/account"
    When I follow "wyloguj się"
    Then should see "Zaloguj się aby rozpocząć korzystanie z systemu."

  @javascript @access
  Scenario: Adding and deleting new access group new syntaxt
    Given I am logged as "test_admin"
    And I am on "access::/groups/list"
    When I create:
      | name       | description      |
      | Test group | Test Description |
    And see on list:
      | Nazwa      | Opis             |
      | Test group | Test Description |
    And edit object:
      | name              | description             |
      | Test group edited | Test Description edited |
    And see on list:
      | Nazwa             | Opis                    |
      | Test group edited | Test Description edited |
    And delete
    Then I should not see "Test group"

  @javascript @access
  Scenario: Adding and deleting new access group new syntaxt
    Given I am logged as "test_admin"
    And I am on "access::/users/list"
    And object "access group" with data:
      | name       | description      |
      | Test group | Test Description |
    When I create:
      | login     | password      | repassword    | _check_1       | _check_2 |
      | test_user | test_password | test_password | Administrators | Tak      |
    And see on list:
      | Login     | Grupy Dostępu  | Stan    |
      | test_user | Administrators | aktywny |
    And edit object:
      | login      | password      | repassword    | _check_1   | _check_2 |
      | test_user1 | test_password | test_password | Test group | Nie      |
    And see on list:
      | Login      | Grupy Dostępu             | Stan        |
      | test_user1 | Administrators,Test group | nie aktywny |
    And delete
    Then I should not see "test_user1"


  @javascript @access
  Scenario: If I add new access group to system I should see it in access matrix table
    Given I am logged as "test_admin"
    And object "access group" with data:
      | name       | description      |
      | Test group | Test Description |
    And I am on "access::/access/list"
    Then I should see "Test group"


  @javascript @access
  Scenario: If I dont have access to page I should see access deny page
    Given object "access group" with data:
      | name       | description      |
      | Test group | Test Description
    And account "test_user" with password "321tes_" in "Test group" group
    And I am logged as "test_user"
    And I am on "admin"
    Then I should see "Access deny"


  @javascript @access
  Scenario: If I give access to page I should see that page
    Given object "access group" with data:
      | name       | description      |
      | Test group | Test Description
    And account "test_user" with password "321tes_" in "Test group" group
    And I am logged as "test_admin"
    And I am on "access::/access/list"
    When I check "Test group" on the row containing "/index"
    And I wait for ajax
    And I am logged as "test_user"
    And I am on "common::index"
    Then I should see "3code Panel"
  #Then I should not see "Access deny"


  @javascript @access
  Scenario: If I am in administrators group I should have access to admin index
    Given account "test_user" with password "321tes_" in "Administrators" group
    And I am logged as "test_user"
    And I am on "admin"
    Then I should see "3code Panel"

  @javascript @access
  Scenario: If I am not loged and trying to view som restricted page I should see that page after login
    Given I am on "access::/access/list"
    When I fill in "inputLogin" with "test_admin"
    And I fill in "inputPass" with "321tes_dev"
    And I press "Zaloguj"
    And I wait for ajax
    Then I should see "punkty dostepu"