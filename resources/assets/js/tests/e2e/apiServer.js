var faker = require('faker')
var md5 = require('blueimp-md5')
var _ = require('lodash')
var jsonServer = require('json-server')

module.exports = {
  data: {},
  server: null,

  getData: function () {
    if (!this.data) {
      this.data = {
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

      var allSongs = []

      // create 100 songs in 10 albums by 5 artists
      for (i = 1; i < 6; ++i) {
        var artist = {
          id: i,
          name: faker.name.findName(),
          image: faker.image.imageUrl(),
          albums: []
        }

        for (j = 1; j < 3; ++j) {
          var album = {
            id: i * 10 + j,
            artist_id: artist.id,
            cover: faker.image.imageUrl(),
            is_compilation: false, // let's keep it simple for now
            name: faker.lorem.sentence(3),
            songs: []
          }

          for (k = 1; k < 11; ++k) {
            var song = {
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
        this.data.artists.push(artist)
      }

      // create 50 interactions
      _.sampleSize(allSongs, 50).forEach(function (song) {
        this.data.interactions.push({
          song_id: song.id,
          like: faker.random.boolean(),
          play_count: faker.random.number(100)
        })
      })

      // create 3 playlists, each contains a random of 0-20 songs
      for (i = 1; i < 4; ++i) {
        this.data.playlists.push({
          id: i,
          name: faker.lorem.sentence(3),
          songs: _.map(_.sampleSize(allSongs, faker.random.number(20)), 'id')
        })
      }

      // create 3 users and make the first one the current and admin
      for (i = 1; i < 4; ++i) {
        this.data.users.push({
          id: i,
          name: faker.name.findName(),
          email: faker.internet.email(),
          is_admin: i === 1
        })
      }
      this.data.currentUser = _.clone(data.users[0])
    }

    return { data: this.data }
  },

  start: function (port) {
    if (typeof port === 'undefined') {
      port = 3000
    }
    this.server = jsonServer.create()
    this.server.use(jsonServer.router(this.getData()))
    this.server.use(jsonServer.defaults())
    this.server.listen(port, function () {
      console.log('JSON Server is running...')
    })
  }
}
