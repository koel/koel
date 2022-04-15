import artist from './artist'

const albumInfo: AlbumInfo = {
  image: '',
  tracks: []
}

const album: Album = {
  artist,
  id: 0,
  artist_id: 0,
  name: '',
  cover: '',
  playCount: 0,
  length: 0,
  fmtLength: '00:00',
  songs: [],
  is_compilation: false,
  info: albumInfo
}

export default album
