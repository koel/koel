import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import Router from '@/router'
import { playbackService } from '@/services/QueuePlaybackService'
import { usePlayCollection } from './usePlayCollection'

describe('usePlayCollection', () => {
  const h = createHarness()

  const setUpPlayCollection = () => {
    let playCollection: ReturnType<typeof usePlayCollection>['playCollection'] | undefined

    h.render(
      defineComponent({
        setup: () => {
          playCollection = usePlayCollection().playCollection
        },
        template: '<div />',
      }),
    )

    return playCollection!
  }

  it('plays the fetched playables and goes to the queue', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song').make(3)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    await setUpPlayCollection()(async () => songs, { shuffle: true, emptyMessage: 'Empty.' })

    expect(playMock).toHaveBeenCalledWith(songs, true)
    expect(goMock).toHaveBeenCalledWith(Router.url('queue'))
  })

  it('warns instead of playing when nothing is fetched', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const warnMock = h.mock(MessageToasterStub.value, 'warning')

    await setUpPlayCollection()(async () => [], { emptyMessage: 'Empty.' })

    expect(warnMock).toHaveBeenCalledWith('Empty.')
    expect(playMock).not.toHaveBeenCalled()
    expect(goMock).not.toHaveBeenCalled()
  })
})
