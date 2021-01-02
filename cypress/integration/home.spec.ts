context('Home Screen', () => {
  beforeEach(() => {
    cy.clock()
    cy.$login()
    cy.tick(0)
  })

  it('renders', () => {
    cy.get('.screen-header').should('be.visible')
    cy.$each([
      ['.top-song-list', 3],
      ['.recent-song-list', 7],
      ['.recently-added-album-list', 6],
      ['.recently-added-song-list', 10],
      ['.top-artist-list', 1],
      ['.top-album-list', 3]
    ], (selector: string, itemCount: number) => {
      cy.get(selector)
        .should('exist')
        .find('li')
        .should('have.length', itemCount)
    })
  })

  it('has a link to view all recently-played songs', () => {
    cy.findByTestId('home-view-all-recently-played-btn')
      .click()
      .url()
      .should('contain', '/#!/recently-played')
  })

  it('a song item can be played', () => {
    cy.$mockPlayback()

    cy.get('.top-song-list [data-test=song-card]:first-child').within(() => {
      cy.get('a.control').invoke('show').click()
    }).should('have.class', 'playing')
    cy.$assertPlaying()
  })

  it('a song item has a context menu', () => {
    cy.get('.top-song-list [data-test=song-card]:first-child').rightclick()
    cy.findByTestId('song-context-menu').should('be.visible')
  })
})
