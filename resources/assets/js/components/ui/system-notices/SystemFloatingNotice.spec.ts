import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SystemFloatingNotice.vue'

describe('systemFloatingNotice', () => {
  const h = createHarness()

  it('has no dismiss button unless dismissible', () => {
    h.render(Component)

    expect(screen.queryByRole('button', { name: 'Dismiss' })).toBeNull()
  })

  it('emits dismiss when its dismiss button is clicked', async () => {
    const { emitted } = h.render(Component, { props: { dismissible: true } })

    await h.user.click(screen.getByRole('button', { name: 'Dismiss' }))

    expect(emitted().dismiss).toHaveLength(1)
  })
})
