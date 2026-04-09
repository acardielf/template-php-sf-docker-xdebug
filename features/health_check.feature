Feature: Health check endpoint
  In order to verify the application is running
  As a monitoring system
  I want to be able to check the application health

  Scenario: Application is healthy
    When I send a GET request to "/api/health"
    Then the response status code should be 200
    And the response should contain JSON key "status" with value "ok"
