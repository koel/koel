context('Settings', () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Settings')
  })

  it('rescans and reloads', () => {
    cy.intercept('GET', '/api/settings', {})

    cy.get('#settingsWrapper').within(() => {
      cy.get('.screen-header')
        .should('be.visible')
        .and('contain.text', 'Settings')

      cy.get('[name=media_path]').should('have.value', '/media/koel/')

      // @ts-ignore
      cy.window().then(window => window.beforeReload = true)
      cy.window().should('have.prop', 'beforeReload', true)

      cy.get('[type=submit]').click()
      cy.window().should('not.have.prop', 'beforeReload')
    })
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
  })
})
