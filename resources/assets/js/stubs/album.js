import config from '@/config'
import artist from './artist'

export default {
  artist,
  id: 0,
  artist_id: 0,
  name: '',
  cover: config.unknownCover,
  playCount: 0,
  length: 0,
  fmtLength: '00:00',
  songs: []
}
