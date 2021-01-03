import '@testing-library/cypress/add-commands'
import 'cypress-file-upload'
import Chainable = Cypress.Chainable
import scrollBehaviorOptions = Cypress.scrollBehaviorOptions

Cypress.Commands.add('$login', (options: Partial<LoginOptions> = {}): Chainable<Cypress.AUTWindow> => {
  window.localStorage.setItem('api-token', 'mock-token')

  const mergedOptions = Object.assign({
    asAdmin: true,
    useiTunes: true,
    useYouTube: true,
    useLastfm: true,
    allowDownload: true,
    supportsTranscoding: true
  }, options) as LoginOptions

  cy.fixture(mergedOptions.asAdmin ? 'data.get.200.json' : 'data-non-admin.get.200.json').then(data => {
    delete mergedOptions.asAdmin

    cy.intercept('GET', 'api/data', {
      statusCode: 200,
      body: Object.assign(data, mergedOptions)
    })
  })

  return cy.visit('/')
})

Cypress.Commands.add('$loginAsNonAdmin', (options: Partial<LoginOptions> = {}): Chainable<Cypress.AUTWindow> => {
  options.asAdmin = false
  return cy.$login(options)
})

Cypress.Commands.add('$each', (dataset: Array<Array<any>>, callback: Function) => {
  dataset.forEach(args => callback(...args))
})

Cypress.Commands.add('$confirm', () => cy.get('.alertify .ok').click())

Cypress.Commands.add('$findInTestId', (selector: string) => {
  const [testId, ...rest] = selector.split(' ')

  return cy.findByTestId(testId.trim()).find(rest.join(' '))
})

Cypress.Commands.add('$clickSidebarItem', (sidebarItemText: string): Chainable<JQuery> => {
  return cy.get('#sidebar')
    .findByText(sidebarItemText)
    .click()
})

Cypress.Commands.add('$mockPlayback', () => {
  cy.intercept('GET', '/play/**?api_token=mock-token', {
    fixture: 'sample.mp3'
  })

  cy.intercept('GET', '/api/album/**/thumbnail', {
    fixture: 'album-thumbnail.get.200.json'
  })

  cy.intercept('GET', '/api/**/info', {
    fixture: 'info.get.200.json'
  })
})

Cypress.Commands.add('$queueSeveralSongs', (count = 3) => {
  cy.$mockPlayback()
  cy.$clickSidebarItem('All Songs')

  cy.get('#songsWrapper').within(() => {
    cy.get('tr.song-item:nth-child(1)').click()
    cy.get(`tr.song-item:nth-child(${count})`).click({
      shiftKey: true
    })

    cy.get('.screen-header [data-test=btn-shuffle-selected]').click()
  })
})

Cypress.Commands.add('$assertPlaylistSongCount', (name: string, count: number) => {
  cy.$clickSidebarItem(name)
  cy.get('#playlistWrapper tr.song-item').should('have.length', count)
  cy.go('back')
})

Cypress.Commands.add('$assertFavoriteSongCount', (count: number) => {
  cy.$clickSidebarItem('Favorites')
  cy.get('#favoritesWrapper').within(() => cy.get('tr.song-item').should('have.length', count))
  cy.go('back')
})

Cypress.Commands.add(
  '$selectSongRange',
  (start: number, end: number, scrollBehavior: scrollBehaviorOptions = false): Chainable<JQuery> => {
    cy.get(`tr.song-item:nth-child(${start})`).click()
    return cy.get(`tr.song-item:nth-child(${end})`).click({
      scrollBehavior,
      shiftKey: true
    })
  })

Cypress.Commands.add('$assertPlaying', () => {
  cy.findByTestId('pause-btn').should('exist')
  cy.findByTestId('play-btn').should('not.exist')
  cy.findByTestId('sound-bar-play').should('be.visible')
})

Cypress.Commands.add('$assertNotPlaying', () => {
  cy.findByTestId('pause-btn').should('not.exist')
  cy.findByTestId('play-btn').should('exist')
  cy.findByTestId('sound-bar-play').should('not.exist')
})

Cypress.Commands.add('$assertSidebarItemActive', (text: string) => {
  cy.get('#sidebar')
    .findByText(text)
    .should('have.class', 'active')
})
