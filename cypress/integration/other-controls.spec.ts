context('Other Controls', () => {
  beforeEach(() => {
    cy.$login()
    cy.$mockPlayback()
    cy.$shuffleSeveralSongs()
  })

  it('likes/unlikes the current song', () => {
    cy.$findInTestId('other-controls [data-test=like-btn]').as('like').click()
    cy.get('#queueWrapper .song-item:first-child [data-test=btn-like-liked]').should('be.visible')
    cy.get('@like').click()
    cy.get('#queueWrapper .song-item:first-child [data-test=btn-like-unliked]').should('be.visible')
  })

  it('toggles the info panel', () => {
    cy.findByTestId('extra-panel').should('be.visible')
    cy.findByTestId('toggle-extra-panel-btn').as('btn').click()
    cy.findByTestId('extra-panel').should('not.be.visible')
    cy.findByTestId('toggle-extra-panel-btn').as('btn').click()
    cy.findByTestId('extra-panel').should('be.visible')
  })

  it('toggles the "sound bars" icon when a song is played/paused', () => {
    cy.$findInTestId('other-controls [data-test=soundbars]').should('be.visible')
    cy.get('body').type(' ')
    cy.$assertNotPlaying()
    cy.$findInTestId('other-controls [data-test=soundbars]').should('not.exist')
  })

  it('toggles the visualizer', () => {
    cy.findByTestId('toggle-visualizer-btn').click()
    cy.findByTestId('visualizer').should('be.visible')
    cy.findByTestId('toggle-visualizer-btn').click()
    cy.findByTestId('visualizer').should('not.exist')
  })

  it('toggles the equalizer', () => {
    cy.findByTestId('toggle-equalizer-btn').click()
    cy.findByTestId('equalizer').should('be.visible')
    cy.findByTestId('toggle-equalizer-btn').click()
    cy.findByTestId('equalizer').should('not.be.visible')
  })
})
