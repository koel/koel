context('User Management', () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Users')
  })

  it('shows the list of users', () => {
    cy.get('#usersWrapper').within(() => {
      cy.get('[data-test=user-card]').should('have.length', 3).and('be.visible')

      cy.get('[data-test=user-card].me').within(() => {
        cy.get('[data-test=current-user-indicator]').should('be.visible')
        cy.get('[data-test=admin-indicator]').should('be.visible')
      })
    })
  })

  it('adds a user', () => {
    cy.intercept('POST', '/api/user', {
      fixture: 'user.post.200.json'
    })

    cy.findByTestId('add-user-btn').click()
    cy.findByTestId('add-user-form').within(() => {
      cy.get('[name=name]').should('be.focused')
        .type('Charles')
      cy.get('[name=email]').type('charles@koel.test')
      cy.get('[name=password]').type('a-secure-password')
      cy.get('[name=is_admin]').check()
      cy.get('[type=submit]').click()
    })

    cy.findByText('New user "Charles" created.').should('be.visible')
    cy.get('#usersWrapper [data-test=user-card]').should('have.length', 4)

    cy.get('#usersWrapper [data-test=user-card]:first-child').within(() => {
      cy.findByText('Charles').should('be.visible')
      cy.findByText('charles@koel.test').should('be.visible')
      cy.get('[data-test=admin-indicator]').should('be.visible')
    })
  })

  it('redirects to profile for current user', () => {
    cy.get('#usersWrapper [data-test=user-card].me [data-test=edit-user-btn]').click({ force: true })
    cy.url().should('contain', '/#!/profile')
  })

  it('edits a user', () => {
    cy.intercept('PUT', '/api/user/2', {
      fixture: 'user.put.200.json'
    })

    cy.get('#usersWrapper [data-test=user-card]:nth-child(2) [data-test=edit-user-btn]').click({ force: true })

    cy.findByTestId('edit-user-form').within(() => {
      cy.get('[name=name]').should('be.focused').and('have.value', 'Alice')
        .clear().type('Adriana')

      cy.get('[name=email]').should('have.value', 'alice@koel.test')
        .clear().type('adriana@koel.test')

      cy.get('[name=password]').should('have.value', '')
      cy.get('[type=submit]').click()
    })

    cy.findByText('User profile updated.').should('be.visible')

    cy.get('#usersWrapper [data-test=user-card]:nth-child(2)').within(() => {
      cy.findByText('Adriana').should('be.visible')
      cy.findByText('adriana@koel.test').should('be.visible')
    })
  })

  it('deletes a user', () => {
    cy.intercept('DELETE', '/api/user/2', {})

    cy.get('#usersWrapper [data-test=user-card]:nth-child(2) [data-test=delete-user-btn]').click({ force: true })
    cy.$confirm()
    cy.findByText('User "Alice" deleted.').should('be.visible')
    cy.get('#usersWrapper [data-test=user-card]').should('have.length', 2)
  })
})
