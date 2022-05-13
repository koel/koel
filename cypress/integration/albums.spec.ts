context('Albums', { scrollBehavior: false }, () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Albums')
  })

  it('loads the list of albums', () => {
    cy.get('#albumsWrapper').within(() => {
      cy.get('.screen-header').should('be.visible').and('contain.text', 'Albums')
      cy.findByTestId('view-mode-thumbnail').should('be.visible').and('have.class', 'active')
      cy.findByTestId('view-mode-list').should('be.visible').and('not.have.class', 'active')
      cy.findAllByTestId('album-card').should('have.length', 7)
    })
  })

  it('changes display mode', () => {
    cy.get('#albumsWrapper').should('be.visible').within(() => {
      cy.findAllByTestId('album-card').should('have.length', 7)
      cy.findByTestId('view-mode-list').click()
      cy.get('[data-testid=album-card].compact').should('have.length', 7)
      cy.findByTestId('view-mode-thumbnail').click()
      cy.get('[data-testid=album-card].full').should('have.length', 7)
    })
  })

  it('plays all songs in an album', () => {
    cy.$mockPlayback()

    cy.get('#albumsWrapper').within(() => {
      cy.get('[data-testid=album-card]:first-child .control-play')
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
      cy.get('[data-testid=album-card]:first-child .name').click()
    })

    cy.get('#albumWrapper').within(() => {
      cy.$getSongRows().should('have.length.at.least', 1)

      cy.get('.screen-header').within(() => {
        cy.findByText('Download All').should('be.visible')
        cy.findByText('Info').click()
      })

      cy.findByTestId('album-info').should('be.visible').within(() => {
        cy.findByText('Album full wiki').should('be.visible')
        cy.get('.cover').should('be.visible')

        cy.findByTestId('album-info-tracks').should('be.visible').within(() => {
          // out of 4 tracks, 3 are already available in Koel. The last one has a link to Apple Music.
          cy.get('li').should('have.length', 4)
          cy.get('li.available').should('have.length', 3)
          cy.get('li:last-child a[title="Preview and buy this song on Apple Music"]').should('be.visible')
        })
      })

      cy.findByTestId('close-modal-btn').click()
      cy.findByTestId('album-info').should('not.exist')
    })
  })

  it('invokes artist screen', () => {
    cy.get('#albumsWrapper').within(() => {
      cy.get('[data-testid=album-card]:first-child .artist').click()
      cy.url().should('contain', '/#!/artist/3')
      // rest of the assertions belong to the Artist spec
    })
  })
})
