import { beforeEach, expect, it } from 'vitest'
import { cleanup } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { nextTick } from 'vue'
import { albumStore, preferenceStore } from '@/stores'
import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import Visualizer from '@/components/ui/Visualizer.vue'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
})

it('has a translucent overlay per album', async () => {
  mockHelper.mock(albumStore, 'getThumbnail', 'https://foo/bar.jpg')

  const { getByTestId } = render(MainContent, {
    global: {
      stubs: {
        AlbumArtOverlay
      }
    }
  })

  eventBus.emit('SONG_STARTED', factory<Song>('song'))
  await nextTick() // re-render
  await nextTick() // fetch album thumbnail

  getByTestId('album-art-overlay')
})

it('does not have a transluscent over if configured not so', async () => {
  preferenceStore.state.showAlbumArtOverlay = false

  const { queryByTestId } = render(MainContent, {
    global: {
      stubs: {
        AlbumArtOverlay
      }
    }
  })

  eventBus.emit('SONG_STARTED', factory<Song>('song'))
  await nextTick()
  await nextTick()

  expect(await queryByTestId('album-art-overlay')).toBe(null)
})

it('toggles visualizer', async () => {
  const { getByTestId, queryByTestId } = render(MainContent, {
    global: {
      stubs: {
        Visualizer
      }
    }
  })

  eventBus.emit('TOGGLE_VISUALIZER')
  await nextTick()
  getByTestId('visualizer')

  eventBus.emit('TOGGLE_VISUALIZER')
  await nextTick()
  expect(await queryByTestId('visualizer')).toBe(null)
})
