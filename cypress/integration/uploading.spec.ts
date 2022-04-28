context('Uploading', () => {
  let interceptCounter = 0

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

  function selectFixtureFile (fileName = 'sample.mp3') {
    // Cypress caches fixtures and apparently has a bug where consecutive fixture files yield an empty "type"
    // which will fail our "audio type filter" (i.e. the file will not be considered an audio file).
    // As a workaround, we pad the fixture file name with slashes to invalidate the cache.
    // https://github.com/cypress-io/cypress/issues/4716#issuecomment-558305553
    cy.fixture(fileName.padStart(fileName.length + interceptCounter, '/')).as('file')
    cy.get('[type=file]').selectFile('@file')

    interceptCounter++
  }

  function executeFailedUpload () {
    cy.intercept('POST', '/api/upload', {
      statusCode: 413
    }).as('failedUpload')

    selectFixtureFile()
    cy.get('[data-test=upload-item]').should('have.length', 1).and('be.visible')
    cy.wait('@failedUpload')

    cy.get('[data-test=upload-item]').should('have.length', 1)
    cy.get('[data-test=upload-item]:first-child').should('have.class', 'Errored')
  }

  it('uploads songs', () => {
    cy.intercept('POST', '/api/upload', {
      fixture: 'upload.post.200.json'
    }).as('upload')

    cy.get('#uploadWrapper').within(() => {
      selectFixtureFile()
      cy.get('[data-test=upload-item]').should('have.length', 1).and('be.visible')

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
