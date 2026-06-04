import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ViewModeSwitch.vue'

describe('viewModeSwitch.vue', () => {
  const h = createHarness()

  it.each<[ViewMode, string, string]>([
    ['grid', 'view-mode-grid', 'view-mode-list'],
    ['list', 'view-mode-list', 'view-mode-grid'],
  ])('marks the %s label active', (mode, activeTestId, inactiveTestId) => {
    h.render(Component, { props: { modelValue: mode } })

    expect(screen.getByTestId(activeTestId).classList.contains('active')).toBe(true)
    expect(screen.getByTestId(inactiveTestId).classList.contains('active')).toBe(false)
  })

  it('emits the correct event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        modelValue: 'grid',
      },
    })

    await h.user.click(screen.getByTestId('view-mode-list'))
    expect(emitted()['update:modelValue'][0]).toEqual(['list'])

    await h.user.click(screen.getByTestId('view-mode-grid'))
    expect(emitted()['update:modelValue'][1]).toEqual(['grid'])
  })
})
