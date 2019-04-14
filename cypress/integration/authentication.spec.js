describe('authentication', () => {
  it('requires login', () => {
    cy.visit('/')
    cy.get('[data-cy=loginForm]').should('be.visible')
  })

  it('logs in with valid username and password', () => {
    cy.login()
    cy.get('[data-cy=appHeader]').should('be.visible')
  })

  it('logs out', () => {
    cy.login()
    cy.route('DELETE', '/api/me', [])
    cy.get('[data-cy=btnLogOut').click()
    cy.get('[data-cy=loginForm]').should('be.visible')
    cy.get('[data-cy=appHeader]').should('not.be.visible')
  })
})