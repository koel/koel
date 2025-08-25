import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { plusService } from '@/services/plusService'
import Component from './ActivateLicenseForm.vue'

describe('activateLicenseForm.vue', () => {
  const h = createHarness()

  const renderComponent = () => h.render(Component)

  it('activates license', async () => {
    renderComponent()
    const activateMock = h.mock(plusService, 'activateLicense').mockResolvedValueOnce('')

    await h.type(screen.getByRole('textbox'), 'my-license-key')
    await h.user.click(screen.getByText('Activate'))
    expect(activateMock).toHaveBeenCalledWith('my-license-key')
  })
})
