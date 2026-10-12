import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RichTextEditor.vue'

describe('richTextEditor.vue', () => {
  const h = createHarness()

  it('shows the given HTML', async () => {
    h.render(Component, { props: { label: 'Description', modelValue: '<p>Formed in <strong>1981</strong>.</p>' } })
    await h.tick()

    expect(screen.getByText('1981').tagName).toBe('STRONG')
    screen.getByRole('textbox', { name: 'Description' })
  })

  it('keeps the link button off until some text is selected', async () => {
    h.render(Component, { props: { label: 'Description', modelValue: '<p>Hello</p>' } })
    await h.tick()

    expect(screen.getByRole('button', { name: 'Link' }).hasAttribute('disabled')).toBe(true)
  })
})
