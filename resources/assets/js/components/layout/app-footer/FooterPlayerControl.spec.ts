import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { playbackService } from '@/services'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import FooterPlayerControls from './FooterPlayerControls.vue'

declare type PlaybackMethod = {
  [K in keyof typeof playbackService]:
  typeof playbackService[K] extends Closure ? K : never;
}[keyof typeof playbackService]

new class extends ComponentTestCase {
  protected test () {
    it.each<[string, string, PlaybackMethod]>([
      ['plays next song', 'Play next song', 'playNext'],
      ['plays previous song', 'Play previous song', 'playPrev'],
      ['plays/resumes current song', 'Play or resume', 'toggle']
    ])('%s', async (_: string, title: string, method: PlaybackMethod) => {
      const mock = this.mock(playbackService, method)

      const { getByTitle } = this.render(FooterPlayerControls, {
        props: {
          song: factory<Song>('song')
        }
      })

      await fireEvent.click(getByTitle(title))
      expect(mock).toHaveBeenCalled()
    })

    it('pauses the current song', async () => {
      const mock = this.mock(playbackService, 'toggle')

      const { getByTitle } = this.render(FooterPlayerControls, {
        props: {
          song: factory<Song>('song', {
            playbackState: 'Playing'
          })
        }
      })

      await fireEvent.click(getByTitle('Pause'))
      expect(mock).toHaveBeenCalled()
    })
  }
}
