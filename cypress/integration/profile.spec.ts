context('Profiles & Preferences', () => {
  it('shows the current user\'s profile', () => {
    cy.$login()
    cy.findByTestId('view-profile-link').click()
    cy.url().should('contain', '/#!/profile')

    cy.get('#profileWrapper').within(() => {
      cy.get('.screen-header').should('contain.text', 'Profile & Preferences')
      cy.findByTestId('update-profile-form').should('be.visible')

      ;[
        'current_password',
        'name',
        'email',
        'new_password',
        'notify',
        'show_album_art_overlay',
        'confirm_closing'
      ].forEach(inputName => cy.get(`[name=${inputName}]`).should('exist'))

      cy.findByTestId('lastfm-integrated').scrollIntoView().should('be.visible')
      cy.findByTestId('lastfm-not-integrated').should('not.exist')
    })
  })

  it('shows instruction for Last.fm integration to admins', () => {
    cy.$login({ useLastfm: false })
    cy.findByTestId('view-profile-link').click()
    cy.findByTestId('lastfm-integrated').should('not.exist')
    cy.findByTestId('lastfm-not-integrated').scrollIntoView().should('be.visible')
    cy.findByTestId('lastfm-admin-instruction').should('be.visible')
  })

  it('shows instruction for Last.fm integration to normal users', () => {
    cy.$loginAsNonAdmin({ useLastfm: false })
    cy.findByTestId('view-profile-link').click()
    cy.findByTestId('lastfm-integrated').should('not.exist')
    cy.findByTestId('lastfm-not-integrated').scrollIntoView().should('be.visible')
    cy.findByTestId('lastfm-user-instruction').should('be.visible')
  })

  it('updates the user profile', () => {
    cy.intercept('PUT', '/api/me', {})
    cy.$login()
    cy.findByTestId('view-profile-link').click()

    cy.get('#profileWrapper').within(() => {
      cy.get('[name=current_password]').clear().type('current-secrEt')
      cy.get('[name=name]').clear().type('Admin No. 2')
      cy.get('[name=email]').clear().type('admin.2@koel.test')
      cy.get('[type=submit]').click()
    })

    cy.findByText('Profile updated.').should('be.visible')
    cy.findByTestId('view-profile-link').should('contain.text', 'Admin No. 2')
  })

  it('updates the user profile along with password', () => {
    cy.intercept('PUT', '/api/me', {})
    cy.$login()
    cy.findByTestId('view-profile-link').click()

    cy.get('#profileWrapper').within(() => {
      cy.get('[name=current_password]').clear().type('current-secrEt')
      cy.get('[name=name]').clear().type('Admin No. 2')
      cy.get('[name=email]').clear().type('admin.2@koel.test')
      cy.get('[name=new_password]').type('new-password')
      cy.get('[type=submit]').click()
    })

    cy.findByText('Profile updated.').should('be.visible')
    cy.findByTestId('view-profile-link').should('contain.text', 'Admin No. 2')
  })

  it('has an option to show/hide album art overlay', () => {
    cy.$login()
    cy.$mockPlayback()
    cy.$clickSidebarItem('Current Queue')
    cy.get('#queueWrapper').within(() => cy.findByText('shuffling all songs').click())
    cy.findByTestId('album-art-overlay').should('exist')

    cy.findByTestId('view-profile-link').click()
    cy.get('#profileWrapper [name=show_album_art_overlay]').scrollIntoView().uncheck()
    cy.findByTestId('album-art-overlay').should('not.exist')
    cy.get('#profileWrapper [name=show_album_art_overlay]').scrollIntoView().check()
    cy.findByTestId('album-art-overlay').should('exist')
  })

  it('sets a theme', () => {
    cy.$login()
    cy.findByTestId('view-profile-link').click()
    cy.findByTestId('theme-card-violet').click()
    cy.get('html').should('have.attr', 'data-theme', 'violet')
    cy.reload()
    cy.get('html').should('have.attr', 'data-theme', 'violet')
  })
})
