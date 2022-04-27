context('Sidebar Functionalities', () => {
  const commonMenuItems = [
    ['Home', '/#!/home'],
    ['Current Queue', '/#!/queue'],
    ['All Songs', '/#!/songs'],
    ['Albums', '/#!/albums'],
    ['Artists', '/#!/artists'],
    ['YouTube Video', '/#!/youtube'],
    ['Favorites', '/#!/favorites'],
    ['Recently Played', '/#!/recently-played'],
    ['Simple Playlist', '/#!/playlist/1']
  ]

  const managementMenuItems = [
    ['Settings', '/#!/settings'],
    ['Upload', '/#!/upload'],
    ['Users', '/#!/users']
  ]

  function assertMenuItem (text: string, url: string) {
    cy.$clickSidebarItem(text)
    cy.url().should('contain', url)
  }

  it('contains menu items', () => {
    cy.on('uncaught:exception', err => !err.message.includes('Request failed'))

    cy.$login()
    cy.$each(commonMenuItems, assertMenuItem)
    cy.$each(managementMenuItems, assertMenuItem)
  })

  it('does not contain management items for non-admins', () => {
    cy.on('uncaught:exception', err => !err.message.includes('Request failed'))

    cy.$loginAsNonAdmin()
    cy.$each(commonMenuItems, assertMenuItem)

    cy.$each(managementMenuItems, (text: string) => cy.get('#sidebar').findByText(text).should('not.exist'))
  })

  it('does not have a YouTube item if YouTube is not used', () => {
    cy.$login({ useYouTube: false })
    cy.get('#sidebar').findByText('YouTube Video').should('not.exist')
  })
})
