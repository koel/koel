import { beforeEach, expect, it } from 'vitest'
import { render } from '@/__tests__/__helpers__'
import FooterMiddlePane from './FooterMiddlePane.vue'
import factory from '@/__tests__/factory'
import { cleanup } from '@testing-library/vue'

beforeEach(() => cleanup())

it('renders without a song', () => {
  expect(render(FooterMiddlePane).html()).toMatchSnapshot()
})

it('renders with a song', () => {
  const album = factory<Album>('album', {
    id: 42,
    name: 'IV',
    artist: factory<Artist>('artist', {
      id: 104,
      name: 'Led Zeppelin'
    })
  })

  const song = factory<Song>('song', {
    album,
    title: 'Fahrstühl to Heaven',
    artist: album.artist
  })

  expect(render(FooterMiddlePane, {
    props: {
      song
    }
  }).html()).toMatchSnapshot()
})
