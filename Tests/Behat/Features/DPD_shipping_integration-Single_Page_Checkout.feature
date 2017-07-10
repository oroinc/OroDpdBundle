@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroDPDBundle:Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
Feature: DPD integration Single Page Checkout

  Scenario: Create DPD integration (Flat Rate)
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    When I fill "Integration Form" with:
      | Type                 | DPD                  |
      | Name                 | DPD                  |
      | Label                | DPD                  |
      | Test Mode            | true                 |
      | Cloud User Id        | 2783                 |
      | Cloud User Token     | 39653536665162576759 |
      | Shipping Services    | DPD Classic          |
      | Unit of weight       | kilogram             |
      | Rate Policy          | Flat Rate            |
      | Flat Rate Price      | 10                   |
      | Label Size           | PDF_A6               |
      | Label Start Position | Upper Right          |
      | Status               | Active               |
      | Default owner        | John Doe             |
    And save and close form
    Then I should see "Integration saved" flash message
    And I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true |
      | Name       | DPD  |
      | Sort Order | 1    |
      | Currency   | $    |
      | Method     | DPD  |
    And fill "DPD Classic Form" with:
      | Enable       | true |
      | Handling fee | 10   |
    When save and close form
    Then should see "Shipping rule has been saved" flash message
    And I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "DPD Payment Rule Form" with:
      | Enable     | true           |
      | Name       | Payment Terms  |
      | Sort Order | 1              |
      | Currency   | $              |
      | Method     | [Payment Term] |
    When save and close form
    Then should see "Payment rule has been saved" flash message

  Scenario: Enable SinglePage checkout
    And go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Check out with DPD integration
    Given Currency is set to USD
    And I enable the existing warehouses
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" from "Select Billing Address"
    And I select "VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" from "Select Shipping Address"
    When I should see "DPD Classic: $20.00"
    And click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
