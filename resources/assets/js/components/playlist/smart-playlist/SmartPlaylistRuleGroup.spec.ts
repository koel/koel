import { describe, expect, it } from 'vite-plus/test'
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

    await waitFor(() => {
      expect(screen.getAllByTestId('remove-rule-btn')).toHaveLength(2)
    })
  })

  it('adds a new rule when add button is clicked', async () => {
    renderComponent()

    await waitFor(() => {
      expect(screen.getAllByTestId('remove-rule-btn')).toHaveLength(1)
    })

    await h.user.click(screen.getByTestId('add-rule-btn'))

    await waitFor(() => {
      expect(screen.getAllByTestId('remove-rule-btn')).toHaveLength(2)
    })
  })

  it('emits input with rule removed when remove button is clicked', async () => {
    const group = {
      id: crypto.randomUUID(),
      rules: [createRule(), createRule()],
    }

    const { emitted } = renderComponent(group)

    await waitFor(() => screen.getAllByTestId('remove-rule-btn'))

    await h.user.click(screen.getAllByTestId('remove-rule-btn')[0])

    const inputEvents = emitted().input as SmartPlaylistRuleGroup[][]
    expect(inputEvents).toBeTruthy()
    const lastEmittedGroup = inputEvents[inputEvents.length - 1][0]
    expect(lastEmittedGroup.rules).toHaveLength(1)
  })
})
