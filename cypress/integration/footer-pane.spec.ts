context('Footer Pane', () => {
  beforeEach(() => {
    cy.$login()
    cy.$mockPlayback()

    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper .song-item:first-child').dblclick().within(function () {
      cy.get('.title').invoke('text').as('title')
      cy.get('.album').invoke('text').as('album')
      cy.get('.artist').invoke('text').as('artist')
    })
  })

  it('displays current song information', () => {
    cy.findByTestId('footer-middle-pane').within(function () {
      cy.get('.title').should('have.text', this.title)
      cy.get('.album').should('have.text', this.album)
      cy.get('.artist').should('have.text', this.artist)
    })
  })

  it('invokes artist screen', () => {
    cy.findByTestId('footer-middle-pane').within(() => cy.get('.artist').click())
    cy.get('#artistWrapper').should('be.visible')
  })

  it('invokes album screen', () => {
    cy.findByTestId('footer-middle-pane').within(() => cy.get('.album').click())
    cy.get('#albumWrapper').should('be.visible')
  })

  it('has a context menu for the current song', () => {
    cy.get('#mainFooter').rightclick()
    cy.findByTestId('song-context-menu').should('be.visible')
  })
})
