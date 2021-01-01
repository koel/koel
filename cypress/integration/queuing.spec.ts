// Setting scrollBehavior to false because we don't want Cypress to scroll the row into view,
// causing the first row lost due to virtual scrolling.
context('Queuing', { scrollBehavior: false }, () => {
  const MIN_SONG_ITEMS_SHOWN = 15

  beforeEach(() => {
    cy.$mockPlayback()
    cy.$login()
  })

  it('allows shuffling all songs', () => {
    cy.$clickSidebarItem('Current Queue')

    cy.get('#queueWrapper').within(() => {
      cy.findByText('Current Queue').should('be.visible')
      cy.findByText('shuffling all songs').click()
      cy.get('tr.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('tr.song-item:first-child').should('have.class', 'playing')
    })
  })

  it('clears the queue', () => {
    cy.$clickSidebarItem('Current Queue')

    cy.get('#queueWrapper').within(() => {
      cy.findByText('Current Queue').should('be.visible')
      cy.findByText('shuffling all songs').click()
      cy.get('tr.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('.screen-header [data-test=song-list-controls]')
        .findByText('Clear')
        .click()
      cy.get('tr.song-item').should('have.length', 0)
    })
  })

  it('shuffles all from a song list screen', () => {
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('.screen-header [data-test=btn-shuffle-all]').click()
      cy.url().should('contains', '/#!/queue')
    })

    cy.get('#queueWrapper').within(() => {
      cy.get('tr.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('tr.song-item:first-child').should('have.class', 'playing')
    })
  })

  function queueSomeFromSongList (count = 3) {
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('tr.song-item:nth-child(1)').click()
      cy.get(`tr.song-item:nth-child(${count})`).click({
        shiftKey: true
      })

      cy.get('.screen-header [data-test=btn-shuffle-selected]').click()
    })
  }

  it('creates a queue from selected songs', () => {
    queueSomeFromSongList()

    cy.get('#queueWrapper').within(() => {
      cy.get('tr.song-item').should('have.length', 3)
      cy.get('tr.song-item:first-child').should('have.class', 'playing')
    })
  })

  it('deletes a song from queue', () => {
    queueSomeFromSongList()

    cy.get('#queueWrapper').within(() => {
      cy.get('tr.song-item').should('have.length', 3)
      cy.get('tr.song-item:first-child').type('{backspace}')
      cy.get('tr.song-item').should('have.length', 2)
    })
  })

  ;([
    { menuItem: 'After Current Song', queuedPosition: 2 },
    { menuItem: 'Bottom of Queue', queuedPosition: 4 },
    { menuItem: 'Top of Queue', queuedPosition: 1 },
  ]).forEach(config => {
    it(`queues a song to ${config.menuItem}`, () => {
      queueSomeFromSongList()

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

      cy.findByTestId('song-context-menu').should('be.visible')
        .within(() => {
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
})
