import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './BtnUpgradeToPlus.vue'

describe('BtnUpgradeToPlus', () => {
  const h = createHarness()

  it('renders the upgrade button', () => {
    h.render(Component)
    expect(screen.getByText('Upgrade to Plus')).toBeTruthy()
  })

  it('emits MODAL_SHOW_KOEL_PLUS on click', async () => {
    const emitSpy = vi.spyOn(eventBus, 'emit')
    h.render(Component)

    await h.user.click(screen.getByText('Upgrade to Plus'))
    expect(emitSpy).toHaveBeenCalledWith('MODAL_SHOW_KOEL_PLUS')
  })
})
