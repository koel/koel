import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import models from '@/config/smart-playlist/models'
import Component from './SmartPlaylistRuleGroup.vue'

describe('smartPlaylistRuleGroup', () => {
  const h = createHarness()

  const createRule = (overrides: Partial<SmartPlaylistRule> = {}): SmartPlaylistRule => ({
    id: crypto.randomUUID(),
    model: models[0], // Title (text)
    operator: 'is',
    value: ['test'],
    ...overrides,
  })

  const renderComponent = (group?: SmartPlaylistRuleGroup, isFirstGroup = true) => {
    group = group ?? {
      id: crypto.randomUUID(),
      rules: [createRule()],
    }

    return h.render(Component, {
      props: {
        group,
        isFirstGroup,
      },
    })
  }

  it('shows first-group heading', () => {
    renderComponent(undefined, true)
    screen.getByText(/Include songs that match/)
    screen.getByText('all')
  })

  it('shows subsequent-group heading', () => {
    renderComponent(undefined, false)
    screen.getByText(/or/)
    screen.getByText('all')
  })

  it('renders a Rule component for each rule', async () => {
    const group = {
      id: crypto.randomUUID(),
      rules: [createRule(), createRule()],
    }

    renderComponent(group)

    // Rules are async components, wait for them to render.
    // Note: the "add rule" button also has title="Remove this rule" (component bug),
    // so 2 rules + 1 add button = 3 elements with that title.
    await waitFor(() => {
      expect(screen.getAllByTitle('Remove this rule')).toHaveLength(3)
    })
  })

  it('adds a new rule when add button is clicked', async () => {
    renderComponent()

    // Wait for async components to render (1 rule + 1 add button = 2 buttons with this title)
    await waitFor(() => {
      expect(screen.getAllByTitle('Remove this rule')).toHaveLength(2)
    })

    // Click the last button (the add rule button)
    const buttons = screen.getAllByRole('button')
    await h.user.click(buttons[buttons.length - 1])

    // After adding, there should be 2 rules + 1 add button = 3 elements
    await waitFor(() => {
      expect(screen.getAllByTitle('Remove this rule')).toHaveLength(3)
    })
  })

  it('emits input with rule removed when remove button is clicked', async () => {
    const group = {
      id: crypto.randomUUID(),
      rules: [createRule(), createRule()],
    }

    const { emitted } = renderComponent(group)

    await waitFor(() => screen.getAllByTitle('Remove this rule'))

    await h.user.click(screen.getAllByTitle('Remove this rule')[0])

    const inputEvents = emitted().input as SmartPlaylistRuleGroup[][]
    expect(inputEvents).toBeTruthy()
    const lastEmittedGroup = inputEvents[inputEvents.length - 1][0]
    expect(lastEmittedGroup.rules).toHaveLength(1)
  })
})
