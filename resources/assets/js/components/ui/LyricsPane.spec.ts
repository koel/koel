import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './LyricsPane.vue'
import Magnifier from '@/components/ui/Magnifier.vue'

describe('lyricsPane.vue', () => {
  const h = createHarness()

  const renderComponent = (song?: Song) => {
    song = song || h.factory('song', {
      lyrics: 'Foo bar baz qux',
    })

    const rendered = h.render(Component, {
      props: {
        song,
      },
      global: {
        stubs: {
          Magnifier,
        },
      },
    })

    return {
      ...rendered,
      song,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('provides a button to add lyrics if current user is admin', async () => {
    const song = h.factory('song', { lyrics: null })

    const mock = h.mock(eventBus, 'emit')
    h.actingAsAdmin()
    renderComponent(song)

    await h.user.click(screen.getByRole('button', { name: 'Click here' }))

    expect(mock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', song, 'lyrics')
  })

  it('does not have a button to add lyrics if current user is not an admin', async () => {
    h.actingAsUser()
    renderComponent(h.factory('song', { lyrics: null }))
    expect(screen.queryByRole('button', { name: 'Click here' })).toBeNull()
  })
})
