import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import KoelPlusModal from '@/components/koel-plus/KoelPlusModal.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './BtnUpgradeToPlus.vue'

describe('BtnUpgradeToPlus', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  it('renders the upgrade button', () => {
    h.render(Component)
    expect(screen.getByText('Upgrade to Plus')).toBeTruthy()
  })

  it('opens KoelPlusModal on click', async () => {
    h.render(Component)

    await h.user.click(screen.getByText('Upgrade to Plus'))
    await assertOpenModal(openModalMock, KoelPlusModal)
  })
})
