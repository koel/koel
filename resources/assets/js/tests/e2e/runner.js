'use strict'
const fs = require('fs')
const chalk = require('chalk')
const path = require('path')
require('chai').should()
const express = require('express')
const app = express()

console.log(chalk.white.bgMagenta('Remember to run gulp before e2e!'))

app.use('/public', express.static(path.resolve(__dirname, '../../../../public')))
app.get('/', (req, res) => {
  res.sendFile(path.resolve(__dirname, 'app.html'))
})
const appServer = app.listen(8080, function () {
  console.log('App server started at 8080')
})

let apiServer = require('./apiServer')

const Nightmare = require('nightmare')
const nightmare = Nightmare({ show: true })

apiServer.start()

describe('test elements rendered', function () {
  afterEach(() => {
    nightmare.end()
  })

  after(function() {
    apiServer.stop()
    apiServer = null
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

