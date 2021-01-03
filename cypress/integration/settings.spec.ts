context('Settings', () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Settings')
  })

  it('rescans media', () => {
    cy.intercept('GET', '/api/settings', {})

    cy.get('#settingsWrapper').within(() => {
      cy.get('.screen-header')
        .should('be.visible')
        .and('contain.text', 'Settings')

      cy.get('[name=media_path]').should('have.value', '/media/koel/')
      cy.get('[type=submit]').click()
    })

    cy.get('#overlay').should('be.visible')
  })

  it('confirms before rescanning if media path is changed', () => {
    cy.get('#settingsWrapper').within(() => {
      cy.get('[name=media_path]')
        .should('have.value', '/media/koel/')
        .clear()
        .type('/var/media/koel')

      cy.get('[type=submit]').click()
    })

    cy.$confirm()
    cy.get('#overlay').should('be.visible')
  })
})
