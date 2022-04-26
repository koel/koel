context('Song Editing', { scrollBehavior: false }, () => {
  beforeEach(() => {
    cy.intercept('/api/**/info', {
      fixture: 'song-info.get.200.json'
    })

    cy.$login()
    cy.$clickSidebarItem('All Songs')
  })

  it('edits a song', () => {
    cy.intercept('PUT', '/api/songs', {
      fixture: 'songs.put.200.json'
    })

    cy.get('#songsWrapper .song-item:first-child').rightclick()
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())

    cy.findByTestId('edit-song-form').within(() => {
      ['artist', 'album', 'is_compilation', 'track'].forEach(selector => {
        cy.get(`[name=${selector}]`).should('be.visible')
      })

      cy.get('[name=title]').clear().type('New Title')
      cy.findByTestId('edit-song-lyrics-tab').click()
      cy.get('textarea[name=lyrics]')
        .should('be.visible')
        .and('contain.value', 'Sample song lyrics')
        .clear()
        .type('New lyrics{enter}Supports multiline.')

      cy.get('button[type=submit]').click()
    })

    cy.findByText('Updated 1 song.').should('be.visible')
    cy.findByTestId('edit-song-form').should('not.exist')
    cy.get('#songsWrapper .song-item:first-child .title').should('have.text', 'New Title')
  })

  it('cancels editing', () => {
    cy.get('#songsWrapper .song-item:first-child').rightclick()
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())

    cy.$findInTestId('edit-song-form .btn-cancel').click()
    cy.findByTestId('edit-song-form').should('not.exist')
  })

  it('edits multiple songs', () => {
    cy.intercept('PUT', '/api/songs', {
      fixture: 'songs-multiple.put.200.json'
    })

    cy.get('#songsWrapper').within(() => {
      cy.$selectSongRange(1, 3).rightclick()
    })

    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())

    cy.findByTestId('edit-song-form').within(() => {
      ['title', 'track'].forEach(selector => {
        cy.get(`[name=${selector}]`).should('not.exist')
      })

      cy.get('textarea[name=lyrics]').should('not.exist')
      ;['3 songs selected', 'Mixed Albums'].forEach(text => cy.findByText(text).should('be.visible'))

      cy.get('[name=album]').invoke('attr', 'placeholder').should('contain', 'No change')

      // Test the typeahead/auto-complete feature
      cy.get('[name=album]').type('A')
      cy.findByText('Abstract').click()
      cy.get('[name=album]').should('contain.value', 'Abstract')
      cy.get('[name=album]').type('{downArrow}{downArrow}{downArrow}{downArrow}{enter}')
      cy.get('[name=album]').should('contain.value', 'The Wall')

      cy.get('button[type=submit]').click()
    })

    cy.findByText('Updated 3 songs.').should('be.visible')
    cy.findByTestId('edit-song-form').should('not.exist')

    cy.get('#songsWrapper .song-item:nth-child(1) .album').should('have.text', 'The Wall')
    cy.get('#songsWrapper .song-item:nth-child(2) .album').should('have.text', 'The Wall')
    cy.get('#songsWrapper .song-item:nth-child(3) .album').should('have.text', 'The Wall')
  })
})
