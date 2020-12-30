import '@testing-library/cypress/add-commands'
import AUTWindow = Cypress.AUTWindow
import Chainable = Cypress.Chainable

Cypress.Commands.add('$login', (redirectTo = '/'): Chainable<AUTWindow> => {
  window.localStorage.setItem('api-token', 'mock-token')

  cy.intercept('api/data', {
    fixture: 'data.json'
  })

  return cy.visit(redirectTo)
})
