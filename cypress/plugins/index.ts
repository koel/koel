/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

export default (on: () => void, config: Record<string, unknown>): Record<string, unknown> => {
  return Object.assign({}, config, {
    supportFile: 'cypress/support/main.ts'
  })
}
