context('Song Editing', () => {
  beforeEach(() => {
    cy.intercept('GET', '/api/**/info', {
      fixture: 'info.get.200.json'
    })

    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())
  })

  it('edits a song', () => {
    cy.intercept('PUT', '/api/songs', {
      fixture: 'songs.put.200.json'
    })

    cy.findByTestId('edit-song-form').within(() => {
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
    cy.get('#songsWrapper tr.song-item:first-child .title').should('have.text', 'New Title')
  })

  it('edits a song', () => {
    cy.$findInTestId('edit-song-form .btn-cancel').click()
    cy.findByTestId('edit-song-form').should('not.exist')
  })
})
