import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { fireEvent, screen } from '@testing-library/vue'
import { settingStore } from '@/stores/settingStore'
import Component from './BrandingSettingGroup.vue'

describe('brandingSettingGroup.vue', () => {
  const h = createHarness()

  const renderComponent = (currentBranding?: Branding) => {
    currentBranding = currentBranding ?? window.BRANDING

    const rendered = h.render(Component, {
      props: {
        currentBranding,
      },
      global: {
        stubs: {
          BrandingImageField: h.stub('branding-image-field', true),
        },
      },
    })

    return {
      ...rendered,
      currentBranding,
    }
  }

  it('works', async () => {
    renderComponent()
    const updateMock = h.mock(settingStore, 'updateBranding')

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'New App Name')
    await fireEvent.update(screen.queryAllByTestId('branding-image-field')[0], 'data:new-logo')
    await fireEvent.update(screen.queryAllByTestId('branding-image-field')[1], 'data:new-cover')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith({
      cover: 'data:new-cover',
      logo: 'data:new-logo',
      name: 'New App Name',
    })
  })
})
