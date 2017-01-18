Feature: LDAP

  Scenario: Creating an new, empty configuration
    Given As an "admin"
    When sending "POST" to "/apps/user_ldap/api/v1/config"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Delete a non-existing configuration
    Given As an "admin"
    When sending "DELETE" to "/apps/user_ldap/api/v1/config/s666"
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Delete an invalid configuration
    Given As an "admin"
    When sending "DELETE" to "/apps/user_ldap/api/v1/config/hack0r"
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"

  # TODO: Scenario deleting an existing config ID (needs to be created before)
