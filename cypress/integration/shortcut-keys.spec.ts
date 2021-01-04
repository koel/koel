context('Shortcut Keys', () => {
  beforeEach(() => cy.$login())

  it('focus into Search input when F is pressed', () => {
    cy.get('body').type('f')
    cy.get('#searchForm [name=q]').should('be.focused')
  })

  it('shuffles all songs by default when Space is pressed', () => {
    cy.fixture('data.get.200.json').then(data => {
      cy.$mockPlayback()
      cy.get('body').type(' ')
      cy.$assertSidebarItemActive('Current Queue')
      cy.$assertPlaying()
      cy.get('#queueWrapper .screen-header').should('contain.text', `${data.songs.length} songs`)
    })
  })

  it('toggles playback when Space is pressed', () => {
    cy.$mockPlayback()
    cy.$shuffleSeveralSongs()
    cy.$assertPlaying()
    cy.get('body').type(' ')
    cy.$assertNotPlaying()
    cy.get('body').type(' ')
    cy.$assertPlaying()
  })

  it('moves back and forward when K and J are pressed', () => {
    cy.$mockPlayback()
    cy.$shuffleSeveralSongs()
    cy.get('body').type('j')
    cy.get('#queueWrapper tr.song-item:nth-child(2)').should('have.class', 'playing')
    cy.get('body').type('k')
    cy.get('#queueWrapper tr.song-item:nth-child(1)').should('have.class', 'playing')
    cy.$assertPlaying()
  })

  it('toggles favorite when L is pressed', () => {
    cy.intercept('POST', '/api/interaction/like', {})
    cy.$mockPlayback()
    cy.$shuffleSeveralSongs()
    cy.get('body').type('l')
    cy.get('#queueWrapper tr.song-item:first-child [data-test=btn-like-liked]').should('be.visible')
    cy.get('body').type('l')
    cy.get('#queueWrapper tr.song-item:first-child [data-test=btn-like-unliked]').should('be.visible')
  })
})
