context('Searching', () => {
  beforeEach(() => {
    cy.$login()
    cy.get('#searchForm [name=q]').as('searchInput')
  })

  it('shows the search screen when search box receives focus', () => {
    cy.get('@searchInput').focus()
    cy.get('#searchExcerptsWrapper').within(() => cy.get('[data-test=screen-empty-state]').should('be.visible'))
  })

  it('performs an excerpt search', () => {
    cy.intercept('/api/search?q=foo', {
      fixture: 'search-excerpts.get.200.json'
    })

    cy.get('@searchInput').type('foo')

    cy.get('#searchExcerptsWrapper').within(() => {
      cy.$findInTestId('song-excerpts [data-test=song-card]').should('have.length', 6)
      cy.$findInTestId('artist-excerpts [data-test=artist-card]').should('have.length', 1)
      cy.$findInTestId('album-excerpts [data-test=album-card]').should('have.length', 3)
    })
  })

  it('has a button to view all matching songs', () => {
    cy.intercept('/api/search?q=foo', {
      fixture: 'search-excerpts.get.200.json'
    })

    cy.intercept('/api/search/songs?q=foo', {
      fixture: 'search-songs.get.200.json'
    })

    cy.get('@searchInput').type('foo')
    cy.get('#searchExcerptsWrapper [data-test=view-all-songs-btn]').click()
    cy.url().should('contain', '/#!/search/songs/foo')

    cy.get('#songResultsWrapper').within(() => {
      cy.get('.screen-header').should('contain.text', 'Showing Songs for foo')
      cy.get('.song-item').should('have.length', 7)
    })
  })

  it('does not have a View All button if no songs are found', () => {
    cy.fixture('search-excerpts.get.200.json').then(data => {
      data.results.songs = []

      cy.intercept('/api/search?q=foo', {
        statusCode: 200,
        body: data
      }).as('search')
    })

    cy.get('@searchInput').type('foo')
    cy.wait('@search')
    cy.get('#searchExcerptsWrapper [data-test=view-all-songs-btn]').should('not.exist')
    cy.findByTestId('song-excerpts').findByText('None found.').should('be.visible')
  })
})
