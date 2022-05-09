context('Favorites', { scrollBehavior: false }, () => {
  beforeEach(() => cy.$login())

  it('loads the list of favorites', () => {
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.findByText('Songs You Love').should('be.visible')
        cy.findByText('Download All').should('be.visible')

        cy.$getSongRows().should('have.length', 3)
          .each(row => cy.wrap(row).findByTestId('btn-like-liked').should('be.visible'))
      })
  })

  it('adds a favorite song from Like button', () => {
    cy.intercept('POST', '/api/interaction/like', {
      fixture: 'like.post.200.json'
    })

    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper')
      .within(() => {
        cy.$getSongRows().first().within(() => {
          cy.findByTestId('like-btn')
            .within(() => cy.findByTestId('btn-like-unliked').should('be.visible')).click()
            .within(() => cy.findByTestId('btn-like-liked').should('be.visible'))
        })
      })

    cy.$assertFavoriteSongCount(4)
  })

  it('adds a favorite song from Add To dropdown', () => {
    cy.intercept('POST', '/api/interaction/batch/like', {
      fixture: 'batch-like.post.200.json'
    })

    cy.$clickSidebarItem('All Songs')

    cy.get('#songsWrapper')
      .within(() => {
        cy.$getSongRows().first().click()
        cy.findByTestId('add-to-btn').click()
        cy.findByTestId('add-to-menu').should('be.visible')
          .within(() => cy.findByText('Favorites').click()).should('not.be.visible')
      })

    cy.$assertFavoriteSongCount(4)
  })

  it('deletes a favorite with Unlike button', () => {
    cy.intercept('POST', '/api/interaction/like', {})
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.$getSongRows().should('have.length', 3)
          .first().should('contain.text', 'November')
          .within(() => cy.findByTestId('like-btn').click())

        cy.$getSongRows().should('have.length', 2)
          .first().should('not.contain.text', 'November')
      })
  })

  it('deletes a favorite with Backspace key', () => {
    cy.intercept('POST', '/api/interaction/like', {})
    cy.$clickSidebarItem('Favorites')

    cy.get('#favoritesWrapper')
      .within(() => {
        cy.$getSongRows().should('have.length', 3)
          .first().should('contain.text', 'November')
          .click().type('{backspace}')

        cy.$getSongRows().should('have.length', 2)
          .first().should('not.contain.text', 'November')
      })
  })
})
