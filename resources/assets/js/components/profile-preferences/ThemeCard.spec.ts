import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ThemeCard.vue'

describe('themeCard.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    const theme: Theme = {
      id: 'sample',
      name: 'Sample',
      thumbnailColor: '#f00',
    }

    const rendered = h.render(Component, {
      props: {
        theme,
      },
    })

    return {
      ...rendered,
      theme,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('emits an event when selected', async () => {
    const { emitted, theme } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Sample' }))

    expect(emitted().selected[0]).toEqual([theme])
  })
})
