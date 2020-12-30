/// <reference types="cypress" />

declare namespace Cypress {
  interface Chainable {
    $login(redirectTo?: string): Chainable
    $each(dataset: Array<Array<any>>, callback: Function): void
  }
}
