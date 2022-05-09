import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { playbackService } from '@/services'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import FooterPlayerControls from './FooterPlayerControls.vue'

new class extends ComponentTestCase {
  protected test () {
    it.each<[string, string, MethodOf<typeof playbackService>]>([
      ['plays next song', 'Play next song', 'playNext'],
      ['plays previous song', 'Play previous song', 'playPrev'],
      ['plays/resumes current song', 'Play or resume', 'toggle']
    ])('%s', async (_: string, title: string, playbackMethod: MethodOf<typeof playbackService>) => {
      const mock = this.mock(playbackService, playbackMethod)

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
