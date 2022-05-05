import { beforeEach, expect, it } from 'vitest'
import { cleanup, fireEvent } from '@testing-library/vue'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { playbackService } from '@/services'
import factory from '@/__tests__/factory'
import FooterPlayerControls from './FooterPlayerControls.vue'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
})

declare type PlaybackMethod = {
  [K in keyof typeof playbackService]:
  typeof playbackService[K] extends Closure ? K : never;
}[keyof typeof playbackService]

it.each<[string, string, PlaybackMethod]>([
  ['plays next song', 'Play next song', 'playNext'],
  ['plays previous song', 'Play previous song', 'playPrev'],
  ['plays/resumes current song', 'Play or resume', 'toggle']
])('%s', async (_: string, title: string, method: PlaybackMethod) => {
  const mock = mockHelper.mock(playbackService, method)

  const { getByTitle } = render(FooterPlayerControls, {
    props: {
      song: factory<Song>('song')
    }
  })

  await fireEvent.click(getByTitle(title))
  expect(mock).toHaveBeenCalled()
})

it('pauses the current song', async () => {
  const mock = mockHelper.mock(playbackService, 'toggle')

  const { getByTitle } = render(FooterPlayerControls, {
    props: {
      song: factory<Song>('song', {
        playbackState: 'Playing'
      })
    }
  })

  await fireEvent.click(getByTitle('Pause'))
  expect(mock).toHaveBeenCalled()
})
