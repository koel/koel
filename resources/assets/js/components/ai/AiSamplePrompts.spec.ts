import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AiSamplePrompts.vue'

describe('aiSamplePrompts.vue', () => {
  const h = createHarness()

  it('renders sample prompts', () => {
    h.render(Component)

    expect(screen.getAllByRole('button').length).toBe(3)
  })

  it('emits a select event when a prompt is clicked', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getAllByRole('button')[0])

    const selectEvents = emitted().select as unknown[][]
    expect(selectEvents).toBeTruthy()
    expect(selectEvents[0][0]).toBeTypeOf('string')
  })
})
