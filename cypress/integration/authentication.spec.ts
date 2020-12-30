/// <reference types="cypress" />

context('Authentication', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  const submitLoginForm = () => {
    cy.get('[type=email]').type('admin@koel.test')
    cy.get('[type=password]').type('super-secret')
    cy.get('[type=submit]').click()
  }

  it('logs in with valid credentials', () => {
    cy.intercept('POST', '/api/me', {
      token: 'mock-token'
    })

    cy.intercept('/api/data', {
      fixture: 'data.json'
    })

    submitLoginForm()
    cy.get('[id=main]').should('be.visible')
  })

  it('fails to log in with invalid credentials', () => {
    cy.intercept('POST', '/api/me', {
      statusCode: 401
    })

    submitLoginForm()
    cy.findByTestId('login-form')
      .should('be.visible')
      .and('have.class', 'error')
  })
})
