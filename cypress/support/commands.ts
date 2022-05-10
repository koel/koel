import '@testing-library/cypress/add-commands'
import scrollBehaviorOptions = Cypress.scrollBehaviorOptions

Cypress.Commands.add('$login', (options: Partial<LoginOptions> = {}) => {
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

    cy.intercept('/api/data', {
      statusCode: 200,
      body: Object.assign(data, mergedOptions)
    })
  }).as('fetchData')

  const win = cy.visit('/')
  cy.wait('@fetchData')

  return win
})

Cypress.Commands.add('$loginAsNonAdmin', (options: Partial<LoginOptions> = {}) => {
  options.asAdmin = false
  return cy.$login(options)
})

Cypress.Commands.add('$each', (dataset: Array<Array<any>>, callback: (...args) => void) => {
  dataset.forEach(args => callback(...args))
})

Cypress.Commands.add('$confirm', () => cy.get('.alertify .ok').click())

Cypress.Commands.add('$findInTestId', (selector: string) => {
  const [testId, ...rest] = selector.split(' ')

  return cy.findByTestId(testId.trim()).find(rest.join(' '))
})

Cypress.Commands.add('$clickSidebarItem', (text: string) => cy.get('#sidebar').findByText(text).click())

Cypress.Commands.add('$mockPlayback', () => {
  cy.intercept('/play/**?api_token=mock-token', {
    fixture: 'sample.mp3,null'
  })

  cy.intercept('/api/album/**/thumbnail', {
    fixture: 'album-thumbnail.get.200.json'
  })

  cy.intercept('/api/song/**/info', {
    fixture: 'song-info.get.200.json'
  })
})

Cypress.Commands.add('$shuffleSeveralSongs', (count = 3) => {
  cy.$mockPlayback()
  cy.$clickSidebarItem('All Songs')

  cy.get('#songsWrapper').within(() => {
    cy.$getSongRowAt(0).click()
    cy.$getSongRowAt(count - 1).click({ shiftKey: true })

    cy.get('.screen-header [data-testid=btn-shuffle-selected]').click()
  })
})

Cypress.Commands.add('$assertPlaylistSongCount', (name: string, count: number) => {
  cy.$clickSidebarItem(name)
  cy.get('#playlistWrapper .song-item').should('have.length', count)
  cy.go('back')
})

Cypress.Commands.add('$assertFavoriteSongCount', (count: number) => {
  cy.$clickSidebarItem('Favorites')
  cy.get('#favoritesWrapper').within(() => cy.get('.song-item').should('have.length', count))
  cy.go('back')
})

Cypress.Commands.add(
  '$selectSongRange',
  (start: number, end: number, scrollBehavior: scrollBehaviorOptions = false) => {
    cy.$getSongRowAt(start).click()
    return cy.$getSongRowAt(end).click({
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
  cy.get('#sidebar').findByText(text).should('have.class', 'active')
})

Cypress.Commands.add('$getSongRows', () => cy.get('.song-item').as('rows'))
Cypress.Commands.add('$getSongRowAt', (position: number) => cy.$getSongRows().eq(position))
