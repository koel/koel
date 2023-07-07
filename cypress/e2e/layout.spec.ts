context('Application Layout', () => {
  it('renders a proper layout', () => {
    cy.$login()
    ;[
      '#mainHeader',
      '#searchForm',
      '#userBadge',
      '#mainHeader .about',
      '#mainWrapper',
      '#sidebar',
      '#mainContent',
      '#extra',
      '#mainFooter',
      '#mainFooter .player-controls',
      '#mainFooter .middle-pane',
      '#mainFooter .other-controls'
    ].forEach(selector => cy.get(selector).should('be.visible'))
  })
})
