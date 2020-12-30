context('Authentication', () => {
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

    cy.visit('/')
    submitLoginForm()
    cy.get('[id=main]').should('be.visible')
  })

  it('fails to log in with invalid credentials', () => {
    cy.intercept('POST', '/api/me', {
      statusCode: 401
    })

    cy.visit('/')
    submitLoginForm()
    cy.findByTestId('login-form')
      .should('be.visible')
      .and('have.class', 'error')
  })

  it('logs out', () => {
    cy.intercept('DELETE', '/api/me', {})
    cy.$login()
    cy.findByTestId('btn-logout').click()
    cy.findByTestId('login-form').should('be.visible')
  })
})
