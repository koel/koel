context('Artists', { scrollBehavior: false }, () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Artists')
  })

  it('loads the list of artists', () => {
    cy.get('#artistsWrapper').within(() => {
      cy.get('.screen-header').should('be.visible').and('contain.text', 'Artists')
      cy.get('[data-test=view-mode-thumbnail]').should('be.visible').and('have.class', 'active')
      cy.get('[data-test=view-mode-list]').should('be.visible').and('not.have.class', 'active')
      cy.get('[data-test=artist-card]').should('have.length', 1)
    })
  })

  it('changes display mode', () => {
    cy.get('#artistsWrapper').should('be.visible').within(() => {
      cy.get('[data-test=artist-card]').should('have.length', 1)
      cy.get('[data-test=view-mode-list]').click()
      cy.get('[data-test=artist-card].compact').should('have.length', 1)
      cy.get('[data-test=view-mode-thumbnail]').click()
      cy.get('[data-test=artist-card].full').should('have.length', 1)
    })
  })

  it('plays all songs by an artist', () => {
    cy.$mockPlayback()

    cy.get('#artistsWrapper').within(() => {
      cy.get('[data-test=artist-card]:first-child .control-play')
        .invoke('show')
        .click()
    })

    cy.url().should('contain', '/#!/queue')
    cy.$assertPlaying()
  })

  it('invokes artist screen', () => {
    cy.intercept('/api/artist/3/info', {
      fixture: 'artist-info.get.200.json'
    })

    cy.get('#artistsWrapper').within(() => {
      cy.get('[data-test=artist-card]:first-child .name').click()
      cy.url().should('contain', '/#!/artist/3')
    })

    cy.get('#artistWrapper').within(() => {
      cy.$getVisibleSongRows().should('have.length.at.least', 1)

      cy.get('.screen-header').within(() => {
        cy.findByText('Download All').should('be.visible')
        cy.findByText('Info').click()
      })

      cy.get('[data-test=artist-info]').should('be.visible').within(() => {
        cy.findByText('Artist full bio').should('be.visible')
        cy.get('.cover').should('be.visible')
      })

      cy.get('[data-test=close-modal-btn]').click()
      cy.get('[data-test=artist-info]').should('not.exist')
    })
  })
})
