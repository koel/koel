declare namespace Cypress {
  interface Chainable {
    $login(redirectTo?: string): Chainable
    $loginAsNonAdmin(redirectTo?: string): Chainable
    $each(dataset: Array<Array<any>>, callback: Function): void
    $confirm(): void
  }
}
