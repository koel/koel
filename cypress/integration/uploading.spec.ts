context('Uploading', () => {
  beforeEach(() => {
    cy.$login()
    cy.$clickSidebarItem('Upload')
  })

  function assertResultsAddedToHomeScreen () {
    cy.$clickSidebarItem('Home')
    cy.get('.recently-added-album-list li:first-child .name').should('contain.text', 'Spectacular')
    cy.get('.recently-added-album-list li:first-child .artist').should('contain.text', 'Hilary Hahn')
    cy.get('.recently-added-song-list li:first-child')
      .should('contain.text', 'Mendelssohn Violin Concerto in E minor, Op. 64')
  }

  function executeFailedUpload () {
    // cy.intercept() doesn't allow overriding previous interceptors yet,
    // so in the mean time, we need to resort to the deprecated cy.route().
    // See https://github.com/cypress-io/cypress/issues/9302.
    cy.server()
    cy.route({
      method: 'POST',
      url: '/api/upload',
      status: 413
    }).as('failedUpload')

    cy.get('[type=file]').attachFile('sample.mp3')
    cy.get('[data-test=upload-item]')
      .should('have.length', 1)
      .and('be.visible')

    cy.wait('@failedUpload')

    cy.get('[data-test=upload-item]').should('have.length', 1)
    cy.get('[data-test=upload-item]:first-child').should('have.class', 'Errored')
  }

  it('uploads songs', () => {
    cy.intercept('POST', '/api/upload', {
      fixture: 'upload.post.200.json'
    }).as('upload')

    cy.get('#uploadWrapper').within(() => {
      cy.get('[type=file]').attachFile('sample.mp3')
      cy.get('[data-test=upload-item]')
        .should('have.length', 1)
        .and('be.visible')

      cy.wait('@upload')
      cy.get('[data-test=upload-item]').should('have.length', 0)
    })

    assertResultsAddedToHomeScreen()
  })

  it('allows retrying individual failed uploads', () => {
    cy.get('#uploadWrapper').within(() => {
      executeFailedUpload()

      cy.intercept('POST', '/api/upload', {
        fixture: 'upload.post.200.json'
      }).as('successfulUpload')

      cy.get('[data-test=upload-item]:first-child [data-test=retry-upload-btn]').click()
      cy.wait('@successfulUpload')
      cy.get('[data-test=upload-item]').should('have.length', 0)
    })

    assertResultsAddedToHomeScreen()
  })

  it('allows retrying all failed uploads at once', () => {
    cy.get('#uploadWrapper').within(() => {
      executeFailedUpload()

      cy.intercept('POST', '/api/upload', {
        fixture: 'upload.post.200.json'
      }).as('successfulUpload')

      cy.findByTestId('upload-retry-all-btn').click()
      cy.wait('@successfulUpload')
      cy.get('[data-test=upload-item]').should('have.length', 0)
    })

    assertResultsAddedToHomeScreen()
  })

  it('allows removing individual failed uploads', () => {
    cy.get('#uploadWrapper').within(() => {
      executeFailedUpload()
      cy.get('[data-test=upload-item]:first-child [data-test=remove-upload-btn]').click()
      cy.get('[data-test=upload-item]').should('have.length', 0)
    })
  })

  it('allows removing all failed uploads at once', () => {
    cy.get('#uploadWrapper').within(() => {
      executeFailedUpload()
      cy.findByTestId('upload-remove-all-btn').click()
      cy.get('[data-test=upload-item]').should('have.length', 0)
    })
  })
})
