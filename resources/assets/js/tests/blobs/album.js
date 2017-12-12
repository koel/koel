export default {
  id: 1,
  name: 'Koel Vol. 1',
  cover: 'http://foo/cover.jpg',
  info: {
    image: 'http://foo/bar.jpg',
    wiki: {
      summary: 'This is the summarized wiki of the album',
      full: 'This is the full wiki of the album'
    },
    tracks: [
      { title: 'First song', fmtLength: '3:42' },
      { title: 'Second song', fmtLength: '2:37' },
    ],
    url: 'http://foo/bar'
  },
  artist: {
    id: 1,
    name: 'Koel Artist'
  },
  songs: [
    {
      id: 'd501d98756733e2f6d875c5de8be40eb',
      title: 'Song #1',
      length: 10,
    },
    {
      id: '6d644013c2414ab07d21776ed5876ba4',
      title: 'Song #2',
      length: 20
    }
  ]
}
