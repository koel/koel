// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This is will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })

Cypress.Commands.add('login', (path = '/', email = 'me@phanan.net', password = 'secret') => {
  cy.server()
  cy.route('POST', '/api/me', 'fixture:token.json')
  cy.route('GET', '/api/data', 'fixture:data.json')
  cy.route('GET', '/api/ping', '')
  cy.visit(path)
  cy.get('[type=email]').type(email)
  cy.get('[type=password]').type(password)
  cy.get('[type=submit]').click()
})