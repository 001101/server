Feature: LDAP
  Background:
    Given using api version "2"

  Scenario: Creating an new, empty configuration
    Given As an "admin"
    When sending "POST" to "/apps/user_ldap/api/v1/config"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And the response should contain a tag "configID"

  Scenario: Delete a non-existing configuration
    Given As an "admin"
    When sending "DELETE" to "/apps/user_ldap/api/v1/config/s666"
    Then the OCS status code should be "404"
    And the HTTP status code should be "404"

  Scenario: Delete an invalid configuration
    Given As an "admin"
    When sending "DELETE" to "/apps/user_ldap/api/v1/config/hack0r"
    Then the OCS status code should be "400"
    And the HTTP status code should be "400"

  Scenario: Create and delete a configuration
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    When deleting the LDAP configuration
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"

  Scenario: Create and modify a configuration
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    When setting "ldapHost" of the LDAP configuration to "ldaps://my.ldap.server"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    # Testing an invalid config key
    When setting "crack0r" of the LDAP configuration to "foobar"
    Then the OCS status code should be "400"
    And the HTTP status code should be "400"

  Scenario: Modiying a non-existing configuration
    Given As an "admin"
    When sending "PUT" to "/apps/user_ldap/api/v1/config/s666" with
      | key | ldapHost |
      | value | ldaps://my.ldap.server |
    Then the OCS status code should be "404"
    And the HTTP status code should be "404"
