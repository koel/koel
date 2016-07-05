'use strict'
const fs = require('fs')
const path = require('path')
require('chai').should()

let server = require('./server')

const Nightmare = require('nightmare')
const nightmare = Nightmare({ show: true })

server.start()

console.warn('Remember to run gulp before e2e')

describe('test elements rendered', function () {
  afterEach(() => {
    nightmare.end()
  })

  after(function() {
    server.stop()
    server = null
  })

  it ('should display the login form', done => {
    nightmare
      .goto('http://localhost:8080')
      .wait('#main')
      .evaluate(() => {
        return document.querySelector('.login-wrapper')
      })
      .then(result => {
        result.should.not.be.null
        done()
      })
  })
})

