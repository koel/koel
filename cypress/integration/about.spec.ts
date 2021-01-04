context('About Koel', () => {
  beforeEach(() => cy.$login())

  it('displays the About modal', () => {
    cy.findByTestId('about-btn').click()
    cy.findByTestId('about-modal').should('be.visible').within(() => cy.get('[data-test=close-modal-btn]').click())
    cy.findByTestId('about-modal').should('not.exist')
  })
})
