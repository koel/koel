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
      cy.findByTestId('shuffle-library').click()
      cy.$getSongRows().should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('@rows').first().should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('clears the queue', () => {
    cy.$clickSidebarItem('Current Queue')

    cy.get('#queueWrapper').within(() => {
      cy.findByText('Current Queue').should('be.visible')
      cy.findByTestId('shuffle-library').click()
      cy.$getSongRows().should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('.screen-header [data-testid=song-list-controls]').findByText('Clear').click()
      cy.$getSongRows().should('have.length', 0)
    })
  })

  it('shuffles all from a song list screen', () => {
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('.screen-header [data-testid=btn-shuffle-all]').click()
      cy.url().should('contains', '/#!/queue')
    })

    cy.get('#queueWrapper').within(() => {
      cy.$getSongRows().should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
        .first().should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('creates a queue from selected songs', () => {
    cy.$shuffleSeveralSongs(3)

    cy.get('#queueWrapper').within(() => {
      cy.$getSongRows().should('have.length', 3)
        .first().should('have.class', 'playing')
    })
  })

  it('deletes a song from queue', () => {
    cy.$shuffleSeveralSongs(3)

    cy.get('#queueWrapper').within(() => {
      cy.$getSongRows().should('have.length', 3)
      cy.get('@rows').first().type('{backspace}')
      cy.$getSongRows().should('have.length', 2)
    })
  })

  it('queues a song when plays it', () => {
    cy.$shuffleSeveralSongs()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(function () {
      cy.$getSongRowAt(4).find('.title').invoke('text').as('title')
      cy.$getSongRowAt(4).dblclick()
    })

    cy.$clickSidebarItem('Current Queue')
    cy.get('#queueWrapper').within(function () {
      cy.$getSongRows().should('have.length', 4)
      cy.$getSongRowAt(1).find('.title').should('have.text', this.title)
      cy.$getSongRowAt(1).should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('navigates through the queue', () => {
    cy.$shuffleSeveralSongs()
    cy.get('#queueWrapper .song-item:nth-child(1)').should('have.class', 'playing')

    cy.findByTitle('Play next song').click({ force: true })
    cy.get('#queueWrapper .song-item:nth-child(2)').should('have.class', 'playing')
    cy.$assertPlaying()

    cy.findByTitle('Play previous song').click({ force: true })
    cy.get('#queueWrapper .song-item:nth-child(1)').should('have.class', 'playing')
    cy.$assertPlaying()
  })

  it('stops playing if reaches end of queue in no-repeat mode', () => {
    cy.$shuffleSeveralSongs()
    cy.findByTestId('play-next-btn').click({ force: true })
    cy.findByTestId('play-next-btn').click({ force: true })
    cy.findByTestId('play-next-btn').click({ force: true })
    cy.$assertNotPlaying()
  })

  it('rotates if reaches end of queue in repeat-all mode', () => {
    cy.findByTestId('repeat-mode-switch').click()

    cy.$shuffleSeveralSongs()
    cy.findByTestId('play-next-btn').click({ force: true })
    cy.findByTestId('play-next-btn').click({ force: true })
    cy.findByTestId('play-next-btn').click({ force: true })

    cy.get('#queueWrapper .song-item:nth-child(1)').should('have.class', 'playing')
    cy.$assertPlaying()
  })

  it('still moves to next song in repeat-one mode', () => {
    cy.findByTestId('repeat-mode-switch').click()
    cy.findByTestId('repeat-mode-switch').click()

    cy.$shuffleSeveralSongs()
    cy.findByTestId('play-next-btn').click({ force: true })

    cy.get('#queueWrapper .song-item:nth-child(2)').should('have.class', 'playing')
    cy.$assertPlaying()
  })
})
