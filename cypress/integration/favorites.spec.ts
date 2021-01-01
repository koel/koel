context('Favorites', () => {
  beforeEach(() => {
    cy.$login()
  })

  function assertFavoriteCount (count: number) {
    cy.$clickSidebarItem('Favorites')
    cy.get('#favoritesWrapper').within(() => cy.get('tr.song-item').should('have.length', count))
  }

  it('loads the list of favorites', () => {
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.findByText('Songs You Love').should('be.visible')
        cy.findByText('Download All').should('be.visible')
        cy.get('tr.song-item').should('have.length', 3)
          .each(row => {
            cy.wrap(row)
              .get('[data-test=btn-like-liked]')
              .should('be.visible')
          })
      })
  })

  it('adds a favorite song from Like button', () => {
    cy.intercept('POST', '/api/interaction/like', {
      fixture: 'like.post.200.json'
    })

    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper')
      .within(() => {
        cy.get('tr.song-item:first-child [data-test=like-btn]')
          .within(() => cy.get('[data-test=btn-like-unliked]').should('be.visible'))
          .click()
          .within(() => cy.get('[data-test=btn-like-liked]').should('be.visible'))
      })

    assertFavoriteCount(4)
  })

  it('adds a favorite song from Add To dropdown', () => {
    cy.intercept('POST', '/api/interaction/like', {
      fixture: 'like.post.200.json'
    })

    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper')
      .within(() => {
        cy.get('tr.song-item:first-child').click()
        cy.get('[data-test=add-to-btn]').click()
        cy.get('[data-test=add-to-menu]')
          .should('be.visible')
          .within(() => cy.findByText('Favorites').click())
          .should('not.be.visible')
      })

    assertFavoriteCount(4)
  })

  it('deletes a favorite with Unlike button', () => {
    cy.intercept('POST', '/api/interaction/like', {})
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.get('tr.song-item:first-child')
          .should('contain.text', 'November')
          .within(() => cy.get('[data-test=like-btn]').click())

        cy.get('tr.song-item').should('have.length', 2)
        cy.get('tr.song-item:first-child').should('not.contain.text', 'November')
      })
  })

  it('deletes a favorite with Backspace key', () => {
    cy.intercept('POST', '/api/interaction/like', {})
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.get('tr.song-item:first-child')
          .should('contain.text', 'November')
          .click()
          .type('{backspace}')

        cy.get('tr.song-item').should('have.length', 2)
        cy.get('tr.song-item:first-child').should('not.contain.text', 'November')
      })
  })
})
