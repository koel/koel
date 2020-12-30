/// <reference types="cypress" />

declare namespace Cypress {
  interface Chainable {
    $login(redirectTo: string): Chainable
  }
}
