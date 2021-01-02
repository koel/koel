import '@testing-library/cypress/add-commands'
import AUTWindow = Cypress.AUTWindow
import Chainable = Cypress.Chainable
import scrollBehaviorOptions = Cypress.scrollBehaviorOptions

function _login (dataFixture, redirectTo = '/'): Chainable<AUTWindow> {
  window.localStorage.setItem('api-token', 'mock-token')

  cy.intercept('api/data', {
    fixture: dataFixture
  })

  return cy.visit(redirectTo)
}

Cypress.Commands.add('$login', (redirectTo = '/') => _login('data.get.200.json', redirectTo))

Cypress.Commands.add('$loginAsNonAdmin', (redirectTo = '/') => _login('data-non-admin.get.200.json', redirectTo))

Cypress.Commands.add('$each', (dataset: Array<Array<any>>, callback: Function) => {
  dataset.forEach(args => callback(...args))
})

Cypress.Commands.add('$confirm', () => {
  cy.get('.alertify .ok')
    .click()
})

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
