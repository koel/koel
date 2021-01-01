import '@testing-library/cypress/add-commands'
import AUTWindow = Cypress.AUTWindow
import Chainable = Cypress.Chainable

function _login(dataFixture, redirectTo = '/'): Chainable<AUTWindow> {
  window.localStorage.setItem('api-token', 'mock-token')

  cy.intercept('api/data', {
    fixture: dataFixture
  })

  return cy.visit(redirectTo)
}

Cypress.Commands.add('$login', (redirectTo = '/') => _login('data.get.200.json', redirectTo))

Cypress.Commands.add('$loginAsNonAdmin', (redirectTo = '/') => _login('data-non-admin.get.200.json', redirectTo))

Cypress.Commands.add('$each', (dataset: Array<Array<any>>, callback: Function) => {
  dataset.forEach(args => callback(...args))
})

Cypress.Commands.add('$confirm', () => {
  cy.get('.alertify .ok')
    .click()
})

Cypress.Commands.add('$findInTestId', (selector: string) => {
  const [testId, ...rest] = selector.split(' ')

  return cy.findByTestId(testId.trim()).find(rest.join(' '))
})

Cypress.Commands.add('$clickSidebarItem', (sidebarItemText: string): Chainable<JQuery> => {
  return cy.get('#sidebar')
    .findByText(sidebarItemText)
    .click()
})

Cypress.Commands.add('$mockPlayback', () => {
  cy.intercept('GET', '/play/**?api_token=mock-token', {
    fixture: 'sample.mp3'
  })

  cy.intercept('GET', '/api/album/**/thumbnail', {
    fixture: 'album-thumbnail.get.200.json'
  })

  cy.intercept('GET', '/api/**/info', {
    fixture: 'info.get.200.json'
  })
})
