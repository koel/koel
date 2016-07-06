'use strict'
const fs = require('fs')
const path = require('path')
const faker = require('faker')
const md5 = require('blueimp-md5')
const _ = require('lodash')
const jsonServer = require('json-server')
const express = require('express')
const bodyParser = require('body-parser')

const app = express()
const rootDir = path.resolve(__dirname, '../../../../../')
let data = null

const getData = () => {
  if (!data) {
    data = {
      allowDownload: faker.random.boolean(),
      artists: [],
      albums: [],
      cdnUrl: 'http://localhost:8080/',
      currentUser: {},
      interactions: [],
      latestVersion: 'v2.2.1',
      playlists: [],
      settings: {
        media_path: '/tmp/fake/path'
      },
      useLastfm: faker.random.boolean(),
      users: []
    }

    const allSongs = []

    // create 100 songs in 10 albums by 5 artists
    for (let i = 1; i < 6; ++i) {
      const artist = {
        id: i,
        name: faker.name.findName(),
        image: faker.image.imageUrl(),
        albums: []
      }

      for (let j = 1; j < 3; ++j) {
        const album = {
          id: i * 10 + j,
          artist_id: artist.id,
          cover: faker.image.imageUrl(),
          is_compilation: false, // let's keep it simple for now
          name: faker.lorem.sentence(3),
          songs: []
        }

        for (let k = 1; k < 11; ++k) {
          const song = {
            id: md5(faker.random.uuid()),
            album_id: album.id,
            contributing_artist_id: null,
            title: faker.lorem.sentence(5),
            length: (Math.random()*1000).toFixed(2),
            track: faker.random.number(15),
            lyrics: faker.lorem.paragraphs(3),
            path: '/tmp/fake/file.mp3'
          }
          album.songs.push(song)
          allSongs.push(song)
        }
        artist.albums.push(album)
      }
      data.artists.push(artist)
    }

    // create 50 interactions
    _.sampleSize(allSongs, 50).forEach(song => {
      data.interactions.push({
        song_id: song.id,
        like: faker.random.boolean(),
        play_count: faker.random.number(100)
      })
    })

    // create 3 playlists, each contains a random of 0-20 songs
    for (let i = 1; i < 4; ++i) {
      data.playlists.push({
        id: i,
        name: faker.lorem.sentence(3),
        songs: _.map(_.sampleSize(allSongs, faker.random.number(20)), 'id')
      })
    }

    // create 3 users and make the first one the current and admin
    for (let i = 1; i < 4; ++i) {
      data.users.push({
        id: i,
        name: faker.name.findName(),
        email: faker.internet.email(),
        is_admin: i === 1
      })
    }
    data.currentUser = _.clone(data.users[0])
  }

  return data
}

fs.createReadStream(__dirname + '/app.html')
  .pipe(fs.createWriteStream(rootDir + '/index.html'))

app.use(express.static(rootDir))
app.use(bodyParser.json())

// Mock all basic routes
app.get('/api/data', (req, res) => {
  res.json(getData())
}).post('/api/me', (req, res, next) => {
  if (req.body.email === 'admin@koel.dev' && req.body.password === 'koel') {
    res.json({ token: 'koelToken' })
  } else {
    res.status(401).json({ error: 'invalid_credentials' })
  }
}).delete('/api/me', (req, res, next) => {
  next()
}).put('/api/me', (req, res, next) => {
  next()
})

module.exports = {
  server: null,

  start() {
    this.server = app.listen(3000, function () {
      console.log('API server started at port 3000')
    })
  },

  stop() {
    this.server.close()
  },

  restart() {
    this.stop()
    this.start()
  },

  data() {
    return getData()
  }
}
