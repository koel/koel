describe('remote', () => {
  it('requires login', () => {
    cy.visit('/remote')
    cy.get(['data=cy-loginForm']).should('be.visible')
  })

  it('logs in with valid username and password', () => {
    cy.login('/remote')
    cy.get('[data-cy=main]').should('be.visible')
  })
})