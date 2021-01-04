context('Song Context Menu', { scrollBehavior: false }, () => {
  it('plays a song via double-clicking', () => {
    cy.$mockPlayback()
    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('tr.song-item:first-child').dblclick()
      cy.get('tr.song-item:first-child').should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('plays and pauses a song via context menu', () => {
    cy.$mockPlayback()
    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('tr.song-item:first-child')
        .as('item')
        .rightclick()
    })

    cy.findByTestId('song-context-menu').within(() => cy.findByText('Play').click())
    cy.get('@item').should('have.class', 'playing')
    cy.$assertPlaying()

    cy.get('@item').rightclick()
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Pause').click())
    cy.$assertNotPlaying()
  })

  it('goes to album', () => {
    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Go to Album').click())

    cy.get('#albumWrapper')
      .should('be.visible')
      .within(() => {
        cy.get('.screen-header').should('be.visible')
        cy.get('tr.song-item').should('have.length.at.least', 1)
      })
  })

  it('goes to artist', () => {
    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Go to Artist').click())

    cy.get('#artistWrapper')
      .should('be.visible')
      .within(() => {
        cy.get('.screen-header').should('be.visible')
        cy.get('tr.song-item').should('have.length.at.least', 1)
      })
  })

  ;([
    { menuItem: 'After Current Song', queuedPosition: 2 },
    { menuItem: 'Bottom of Queue', queuedPosition: 4 },
    { menuItem: 'Top of Queue', queuedPosition: 1 }
  ]).forEach(config => {
    it(`queues a song to ${config.menuItem}`, () => {
      cy.$login()
      cy.$shuffleSeveralSongs()
      cy.$clickSidebarItem('All Songs')

      let songTitle
      cy.get('#songsWrapper').within(() => {
        cy.get('tr.song-item:nth-child(4) .title')
          .invoke('text')
          .then(text => {
            songTitle = text
          })

        cy.get('tr.song-item:nth-child(4)').rightclick()
      })

      cy.findByTestId('song-context-menu').within(() => {
          cy.findByText('Add To').click()
          cy.findByText(config.menuItem).click()
        })

      cy.$clickSidebarItem('Current Queue')
      cy.get('#queueWrapper').within(() => {
        cy.get('tr.song-item').should('have.length', 4)
        cy.get(`tr.song-item:nth-child(${config.queuedPosition}) .title`).should('have.text', songTitle)
      })
    })
  })

  ;[
    { name: 'one song', songCount: 1 },
    { name: 'several songs', songCount: 2 }
  ].forEach((config) => {
    it(`adds ${config.name} into a simple playlist`, () => {
      cy.intercept('GET', '/api/playlist/1/songs', {
        fixture: 'playlist-songs.get.200.json'
      })

      cy.intercept('PUT', '/api/playlist/1/sync', {})

      cy.$login()
      cy.$clickSidebarItem('All Songs')

      cy.$assertPlaylistSongCount('Simple Playlist', 3)
      cy.get('#songsWrapper').within(() => {
        if (config.songCount > 1) {
          cy.$selectSongRange(1, config.songCount).rightclick()
        } else {
          cy.get('tr.song-item:first-child').rightclick()
        }
      })

      cy.findByTestId('song-context-menu')
        .within(() => {
          cy.findByText('Add To').click()
          cy.findByText('Simple Playlist').click()
        })

      cy.$assertPlaylistSongCount('Simple Playlist', 3 + config.songCount)
    })
  })

  it('does not have smart playlists as target for adding songs', () => {
    cy.$login()
    cy.$clickSidebarItem('All Songs')
    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())

    cy.findByTestId('song-context-menu')
      .within(() => {
        cy.findByText('Add To').click()
        cy.findByText('Smart Playlist').should('not.exist')
      })
  })

  it('adds a favorite song from context menu', () => {
    cy.intercept('POST', '/api/interaction/like', {
      fixture: 'like.post.200.json'
    })

    cy.$login()
    cy.$clickSidebarItem('All Songs')
    cy.$assertFavoriteSongCount(3)

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => {
      cy.findByText('Add To').click()
      cy.findByText('Favorites').click()
    })

    cy.$assertFavoriteSongCount(4)
  })

  it('initiates editing a song', () => {
    cy.intercept('GET', '/api/**/info', {
      fixture: 'info.get.200.json'
    })

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').click())
    cy.findByTestId('edit-song-form').should('be.visible')
  })

  it('downloads a song', () => {
    cy.intercept('/download/songs').as('download')

    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Download').click())

    cy.wait('@download')
  })

  it('does not have a Download item if download is not allowed', () => {
    cy.$login({ allowDownload: false })
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Download').should('not.exist'))
  })

  it.only('does not have an Edit item if user is not an admin', () => {
    cy.$loginAsNonAdmin()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Edit').should('not.exist'))
  })

  it("copies a song's URL", () => {
    cy.$login()
    cy.$clickSidebarItem('All Songs')

    cy.window().then(window => cy.spy(window.document, 'execCommand').as('copy'));
    cy.get('#songsWrapper').within(() => cy.get('tr.song-item:first-child').rightclick())
    cy.findByTestId('song-context-menu').within(() => cy.findByText('Copy Shareable URL').click())
    cy.get('@copy').should('be.calledWithExactly', 'copy');
  })
})
