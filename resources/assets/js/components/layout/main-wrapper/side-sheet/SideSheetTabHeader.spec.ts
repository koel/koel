import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './SideSheetTabHeader.vue'

describe('sideSheetTabHeader.vue', () => {
  const h = createHarness()

  it('renders tab headers', () => {
    commonStore.state.uses_you_tube = false
    h.render(Component)

    ;['lyrics', 'artist', 'album'].forEach(name => screen.getByTestId(`side-sheet-${name}-tab-header`))
    expect(screen.queryByTestId('side-sheet-youtube-tab-header')).toBeNull()
  })

  it('has a YouTube tab header if using YouTube', () => {
    commonStore.state.uses_you_tube = true
    h.render(Component)

    screen.getByTestId('side-sheet-youtube-tab-header')
  })

  it('emits the selected tab value', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByTestId('side-sheet-lyrics-tab-header'))

    expect(emitted()['update:modelValue']).toEqual([['Lyrics']])
  })
})
