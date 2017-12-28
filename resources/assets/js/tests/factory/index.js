import factory from 'factoria'
import artist from './artist'
import album from './album'
import song from './song'
import video from './video'
import playlist from './playlist'
import user from './user'

factory
  .define('artist', faker => artist(faker))
  .define('album', faker => album(faker))
  .define('song', faker => song(faker))
  .define('video', faker => video(faker))
  .define('playlist', faker => playlist(faker))
  .define('user', faker => user(faker))

export default factory
