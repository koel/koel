const models: SmartPlaylistModel[] = [
  {
    name: 'title',
    type: 'text',
    label: 'Title'
  }, {
    name: 'album.name',
    type: 'text',
    label: 'Album'
  }, {
    name: 'artist.name',
    type: 'text',
    label: 'Artist'
  }, {
    name: 'genre',
    type: 'text',
    label: 'Genre'
  }, {
    name: 'year',
    type: 'number',
    label: 'Year'
  }, {
    name: 'interactions.play_count',
    type: 'number',
    label: 'Plays'
  }, {
    name: 'interactions.updated_at',
    type: 'date',
    label: 'Last Played'
  }, {
    name: 'length',
    type: 'number',
    label: 'Length',
    unit: 'seconds'
  }, {
    name: 'created_at',
    type: 'date',
    label: 'Date Added'
  }, {
    name: 'updated_at',
    type: 'date',
    label: 'Date Modified'
  }
]

export default models
