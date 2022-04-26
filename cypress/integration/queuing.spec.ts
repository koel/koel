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
      cy.get('.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('.song-item:first-child').should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('clears the queue', () => {
    cy.$clickSidebarItem('Current Queue')

    cy.get('#queueWrapper').within(() => {
      cy.findByText('Current Queue').should('be.visible')
      cy.findByTestId('shuffle-library').click()
      cy.get('').click()
      cy.get('.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('.screen-header [data-test=song-list-controls]')
        .findByText('Clear')
        .click()
      cy.get('.song-item').should('have.length', 0)
    })
  })

  it('shuffles all from a song list screen', () => {
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(() => {
      cy.get('.screen-header [data-test=btn-shuffle-all]').click()
      cy.url().should('contains', '/#!/queue')
    })

    cy.get('#queueWrapper').within(() => {
      cy.get('.song-item').should('have.length.at.least', MIN_SONG_ITEMS_SHOWN)
      cy.get('.song-item:first-child').should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('creates a queue from selected songs', () => {
    cy.$shuffleSeveralSongs()

    cy.get('#queueWrapper').within(() => {
      cy.get('.song-item').should('have.length', 3)
      cy.get('.song-item:first-child').should('have.class', 'playing')
    })
  })

  it('deletes a song from queue', () => {
    cy.$shuffleSeveralSongs()

    cy.get('#queueWrapper').within(() => {
      cy.get('.song-item').should('have.length', 3)
      cy.get('.song-item:first-child').type('{backspace}')
      cy.get('.song-item').should('have.length', 2)
    })
  })

  it('queues a song when plays it', () => {
    cy.$shuffleSeveralSongs()
    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper').within(function () {
      cy.get('.song-item:nth-child(4) .title').invoke('text').as('title')
      cy.get('.song-item:nth-child(4)').dblclick()
    })

    cy.$clickSidebarItem('Current Queue')
    cy.get('#queueWrapper').within(function () {
      cy.get('.song-item').should('have.length', 4)
      cy.get(`.song-item:nth-child(2) .title`).should('have.text', this.title)
      cy.get('.song-item:nth-child(2)').should('have.class', 'playing')
    })

    cy.$assertPlaying()
  })

  it('navigates through the queue', () => {
    cy.$shuffleSeveralSongs()
    cy.get('#queueWrapper .song-item:nth-child(1)').should('have.class', 'playing')

    cy.findByTestId('play-next-btn').click({ force: true })
    cy.get('#queueWrapper .song-item:nth-child(2)').should('have.class', 'playing')
    cy.$assertPlaying()

    cy.findByTestId('play-prev-btn').click({ force: true })
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
