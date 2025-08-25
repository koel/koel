import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ViewModeSwitch.vue'

describe('viewModeSwitch.vue', () => {
  const h = createHarness()

  it.each<[ViewMode]>([['thumbnails'], ['list']])('renders %s mode', mode => {
    const { html } = h.render(Component, {
      props: {
        modelValue: mode,
      },
    })

    expect(html()).toMatchSnapshot()
  })

  it('emits the correct event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        modelValue: 'thumbnails',
      },
    })

    await h.user.click(screen.getByTestId('view-mode-list'))
    expect(emitted()['update:modelValue'][0]).toEqual(['list'])

    await h.user.click(screen.getByTestId('view-mode-thumbnails'))
    expect(emitted()['update:modelValue'][1]).toEqual(['thumbnails'])
  })
})
