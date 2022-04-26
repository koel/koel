context('Extra Information Panel', () => {
  beforeEach(() => cy.$login())

  it('displays the lyrics as the default panel', () => {
    cy.$shuffleSeveralSongs()
    cy.get('#extraPanelLyrics').should('be.visible').and('contain.text', 'Sample song lyrics')
  })

  it('displays an option to add lyrics if blank', () => {
    cy.fixture('song-info.get.200.json').then(data => {
      data.lyrics = null

      cy.intercept('/api/**/info', {
        statusCode: 200,
        body: data
      })
    })

    cy.$clickSidebarItem('All Songs')
    cy.get('#songsWrapper .song-item:first-child').dblclick()

    cy.get('#extraPanelLyrics').should('be.visible').and('contain.text', 'No lyrics found.')
    cy.get('#extraPanelLyrics [data-test=add-lyrics-btn]').click()
    cy.findByTestId('edit-song-form').should('be.visible').within(() => {
      cy.get('[name=lyrics]').should('have.focus')
    })
  })

  it('displays the artist information', () => {
    cy.$shuffleSeveralSongs()
    cy.get('#extraTabArtist').click()
    cy.get('#extraPanelArtist').should('be.visible').within(() => {
      cy.get('[data-test=artist-info]').should('be.visible')
      cy.findByText('Artist summary').should('be.visible')
      cy.get('[data-test=more-btn]').click()
      cy.findByText('Artist summary').should('not.exist')
      cy.findByText('Artist full bio').should('be.visible')
    })
  })

  it('displays the album information', () => {
    cy.$shuffleSeveralSongs()
    cy.get('#extraTabAlbum').click()
    cy.get('#extraPanelAlbum').should('be.visible').within(() => {
      cy.get('[data-test=album-info]').should('be.visible')
      cy.findByText('Album summary').should('be.visible')
      cy.get('[data-test=more-btn]').click()
      cy.findByText('Album summary').should('not.exist')
      cy.findByText('Album full wiki').should('be.visible')
    })
  })

  // YouTube spec has been handled by youtube.spec.ts
})
