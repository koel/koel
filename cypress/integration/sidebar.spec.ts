context('Sidebar Functionalities', () => {
  const commonMenuItems = [
    ['Home', '/#!/home'],
    ['Current Queue', '/#!/queue'],
    ['All Songs', '/#!/songs'],
    ['Albums', '/#!/albums'],
    ['Artists', '/#!/artists'],
    ['YouTube Video', '/#!/youtube'],
    ['Favorites', '/#!/favorites'],
    ['Recently Played', '/#!/recently-played'],
    ['Simple Playlist', '/#!/playlist/1']
  ]

  const managementMenuItems = [
    ['Settings', '/#!/settings'],
    ['Upload', '/#!/upload'],
    ['Users', '/#!/users']
  ]

  function assertMenuItem (text: string, url: string) {
    cy.get('#sidebar')
      .findByText(text)
      .click()
      .url()
      .should('contain', url)
  }

  it('contains menu items', () => {
    cy.$login()
    cy.$each(commonMenuItems, assertMenuItem)
    cy.$each(managementMenuItems, assertMenuItem)
  })

  it('does not contain management items for non-admins', () => {
    cy.$loginAsNonAdmin()
    cy.$each(commonMenuItems, assertMenuItem)

    cy.$each(managementMenuItems, (text: string) => {
      cy.get('#sidebar')
        .findByText(text)
        .should('not.exist')
    })
  })

  it('creates a simple playlist from the sidebar', () => {
    cy.intercept('GET', '/api/playlist/2/songs', [])

    cy.intercept('POST', '/api/playlist', {
      fixture: 'playlist.post.200.json'
    })

    cy.$loginAsNonAdmin()
    cy.clock()
    cy.findByTestId('sidebar-create-playlist-btn')
      .click()

    cy.tick(1)

    cy.findByTestId('playlist-context-menu-create-simple')
      .click()

    cy.get('[name=create-simple-playlist-form] [name=name]')
      .as('nameInput')
      .should('be.visible')

    cy.get('@nameInput')
      .clear()
      .type('A New Playlist{enter}')

    cy.tick(50)

    cy.get('#sidebar')
      .findByText('A New Playlist')
      .should('exist')
      .and('have.class', 'active')

    cy.findByText('Created playlist "A New Playlist".')
      .should('be.visible')

    cy.get('#playlistWrapper')
      .should('be.visible')

    cy.get('#playlistWrapper .heading-wrapper')
      .should('be.visible')
      .and('contain', 'A New Playlist')

    cy.get('#playlistWrapper .none')
      .should('be.visible')
      .and('contain', 'The playlist is currently empty.')
  })

  it('updates a simple playlist from the sidebar', () => {
    cy.intercept('PUT', '/api/playlist/1', {})
    cy.intercept('GET', '/api/playlist/1/songs', {
      fixture: 'playlist-songs.get.200.json'
    })

    cy.clock()
    cy.$loginAsNonAdmin()
    cy.tick(1)

    cy.get('#sidebar')
      .findByText('Simple Playlist')
      .as('menuItem')
      .dblclick()

    cy.findByTestId('inline-playlist-name-input')
      .as('nameInput')
      .should('be.focused')

    cy.get('@nameInput')
      .clear()
      .type('A New Name{enter}')

    cy.tick(50)

    cy.get('@menuItem')
      .should('contain', 'A New Name')
      .and('have.class', 'active')

    cy.findByText('Updated playlist "A New Name".')
      .should('be.visible')

    cy.tick(50)

    cy.get('#playlistWrapper .heading-wrapper')
      .should('be.visible')
      .and('contain', 'A New Name')
  })
})
