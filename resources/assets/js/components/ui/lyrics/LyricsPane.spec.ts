import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import EditSongForm from '@/components/playable/EditSongForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './LyricsPane.vue'

describe('lyricsPane.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  const renderComponent = (song?: Song) => {
    song =
      song ||
      h.factory('song', {
        lyrics: 'Foo bar baz qux',
      })

    const rendered = h.render(Component, {
      props: {
        song,
      },
      global: {
        stubs: {
          Magnifier: h.stub('magnifier'),
          LrcLyricsPane: h.stub('lrc-lyrics-pane'),
        },
      },
    })

    return {
      ...rendered,
      song,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders plain text lyrics when lyrics are not synced', () => {
    renderComponent(
      h.factory('song', {
        lyrics: 'Plain lyrics\nLine 2\nLine 3',
      }),
    )

    screen.getByTestId('plain-text-lyrics')
    expect(screen.queryByTestId('lrc-lyrics-pane')).toBeNull()
  })

  it('renders LRC pane when lyrics are in LRC format', () => {
    const song = h.factory('song', {
      lyrics: '[00:12.00]First line\n[00:17.20]Second line\n[00:21.00]Third line',
    })

    renderComponent(song)

    expect(screen.queryByTestId('plain-text-lyrics')).toBeNull()
    screen.getByTestId('lrc-lyrics-pane')
  })

  it('provides a button to add lyrics if current user is admin', async () => {
    const song = h.factory('song', { lyrics: null })

    h.actingAsAdmin()
    renderComponent(song)

    await h.user.click(screen.getByRole('button', { name: 'Click here' }))

    await assertOpenModal(openModalMock, EditSongForm, { songs: [song], initialTab: 'lyrics' })
  })

  it('does not have a button to add lyrics if current user is not an admin', async () => {
    h.actingAsUser()
    renderComponent(h.factory('song', { lyrics: null }))
    expect(screen.queryByRole('button', { name: 'Click here' })).toBeNull()
  })
})
