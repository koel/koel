import artist from './artist'
import album from './album'
import song from './song'
import video from './video'

const models = { artist, album, song, video }

const factory = (model, count = 1, overrides = {}) => {
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
