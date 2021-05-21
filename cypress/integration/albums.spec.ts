context('Albums', { scrollBehavior: false }, () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Albums')
  })

  it('loads the list of albums', () => {
    cy.get('#albumsWrapper').within(() => {
      cy.get('.screen-header')
        .should('be.visible')
        .and('contain.text', 'Albums')

      cy.get('[data-test=view-mode-thumbnail]')
        .should('be.visible')
        .and('have.class', 'active')

      cy.get('[data-test=view-mode-list]')
        .should('be.visible')
        .and('not.have.class', 'active')
      cy.get('[data-test=album-card]').should('have.length', 7)
    })
  })

  it('changes display mode', () => {
    cy.get('#albumsWrapper').should('be.visible').within(() => {
      cy.get('[data-test=album-card]').should('have.length', 7)
      cy.get('[data-test=view-mode-list]').click()
      cy.get('[data-test=album-card].compact').should('have.length', 7)
      cy.get('[data-test=view-mode-thumbnail]').click()
      cy.get('[data-test=album-card].full').should('have.length', 7)
    })
  })

  it('plays all songs in an album', () => {
    cy.$mockPlayback()

    cy.get('#albumsWrapper').within(() => {
      cy.get('[data-test=album-card]:first-child .control-play')
        .invoke('show')
        .click()
    })

    cy.url().should('contain', '/#!/queue')
    cy.$assertPlaying()
  })

  it('invokes album screen', () => {
    cy.intercept('/api/album/8/info', {
      fixture: 'album-info.get.200.json'
    })

    cy.get('#albumsWrapper').within(() => {
      cy.get('[data-test=album-card]:first-child .name').click()
    })

    cy.get('#albumWrapper').within(() => {
      cy.get('tr.song-item').should('have.length.at.least', 1)

      cy.get('.screen-header').within(() => {
        cy.findByText('Download All').should('be.visible')
        cy.findByText('Info').click()
      })

      cy.get('[data-test=album-info]').should('be.visible').within(() => {
        cy.findByText('Album full wiki').should('be.visible')
        cy.get('.cover').should('be.visible')

        cy.get('[data-test=album-info-tracks]').should('be.visible').within(() => {
          // out of 4 tracks, 3 are already available in Koel. The last one has a link to iTunes.
          cy.get('li').should('have.length', 4)
          cy.get('li.available').should('have.length', 3)
          cy.get('li:last-child a.view-on-itunes').should('be.visible')
        })
      })

      cy.get('[data-test=close-modal-btn]').click()
      cy.get('[data-test=album-info]').should('not.exist')
    })
  })

  it('invokes artist screen', () => {
    cy.get('#albumsWrapper').within(() => {
      cy.get('[data-test=album-card]:first-child .artist').click()
      cy.url().should('contain', '/#!/artist/3')
      // rest of the assertions belong to the Artist spec
    })
  })
})
