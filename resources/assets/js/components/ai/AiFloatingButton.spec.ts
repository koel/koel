import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './AiFloatingButton.vue'

describe('aiFloatingButton.vue', () => {
  const h = createHarness()

  it('navigates to the AI screen when clicked', async () => {
    const mock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'AI Assistant' }))

    expect(mock).toHaveBeenCalledWith('/#/ai')
  })

  it('renders', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
