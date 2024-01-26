context('Song Editing', { scrollBehavior: false }, () => {
  beforeEach(() => {
    cy.intercept('/api/song/**/info', {
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
      cy.get('textarea[name=lyrics]').should('be.visible').and('contain.value', 'Sample song lyrics')
        .clear().type('New lyrics{enter}Fake multiline.')

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

    cy.get('#songsWrapper').within(() => cy.$selectSongRange(0, 2).rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())

    cy.findByTestId('edit-song-form').within(() => {
      ['title', 'track'].forEach(selector => {
        cy.get(`[name=${selector}]`).should('not.exist')
      })

      cy.get('textarea[name=lyrics]').should('not.exist')
      ;['3 songs selected', 'Mixed Albums'].forEach(text => cy.findByText(text).should('be.visible'))

      cy.get('[name=album]').invoke('attr', 'placeholder').should('contain', 'Leave unchanged')
      cy.get('[name=album]').type('The Wall')

      cy.get('button[type=submit]').click()
    })

    cy.findByText('Updated 3 songs.').should('be.visible')
    cy.findByTestId('edit-song-form').should('not.exist')

    ;[1, 2, 3].forEach(i => cy.get(`#songsWrapper .song-item:nth-child(${i}) .album`).should('have.text', 'The Wall'))
  })
})
