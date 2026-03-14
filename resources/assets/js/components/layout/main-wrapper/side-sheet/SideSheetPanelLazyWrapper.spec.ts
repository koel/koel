import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SideSheetPanelLazyWrapper.vue'

describe('sideSheetPanelLazyWrapper.vue', () => {
  const h = createHarness()

  it('does not render slot when shouldMount is false', () => {
    const { queryByText } = h.render(Component, {
      props: { active: true, shouldMount: false },
      slots: { default: 'Panel content' },
    })

    expect(queryByText('Panel content')).toBeNull()
  })

  it('renders slot when shouldMount is true', () => {
    const { getByText } = h.render(Component, {
      props: { active: true, shouldMount: true },
      slots: { default: 'Panel content' },
    })

    getByText('Panel content')
  })

  it('hides content when active is false but shouldMount is true', () => {
    const { getByRole } = h.render(Component, {
      props: { active: false, shouldMount: true },
      slots: { default: 'Panel content' },
    })

    expect(getByRole('tabpanel', { hidden: true }).style.display).toBe('none')
  })
})
