import album from './album'
import artist from './artist'

const song: Song = {
  id: '00000000000000000000000000000000',
  album,
  artist,
  artist_id: artist.id,
  track: 1,
  disc: 0,
  album_id: album.id,
  title: '',
  length: 0,
  fmtLength: '00:00',
  lyrics: '',
  liked: false,
  playCount: 0,
  playbackState: 'Stopped',
  playCountRegistered: false,
  preloaded: false,
  infoRetrieved: true,
  playStartTime: 0
}

export default song
