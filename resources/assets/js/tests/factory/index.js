import artist from './artist'
import album from './album'
import song from './song'
import video from './video'
import playlist from './playlist'
import user from './user'

const models = { artist, album, song, video, playlist, user }

const factory = (model, count = 1, overrides = {}) => {
  if (!(model in models)) {
    throw new Error(`Model \`${model}\` not found`)
  }

  if (typeof count === 'object') {
    return factory(model, 1, count)
  }

  if (count === 1) {
    return _.assign(models[model](), overrides)
  } else {
    return [...(function* () {
      let i = 0
      while (i < count) {
        yield _.assign(models[model](), overrides)
        ++i
      }
    })()]
  }
}

export default factory
