import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import models from '@/config/smart-playlist/models'
import Component from './SmartPlaylistRule.vue'

describe('smartPlaylistRule', () => {
  const h = createHarness()

  const titleModel = models.find(m => m.name === 'title')!
  const yearModel = models.find(m => m.name === 'year')!
  const lastPlayedModel = models.find(m => m.name === 'interactions.last_played_at')!
  const lengthModel = models.find(m => m.name === 'length')!

  const createRule = (overrides: Partial<SmartPlaylistRule> = {}): SmartPlaylistRule => ({
    id: crypto.randomUUID(),
    model: titleModel,
    operator: 'is',
    value: [''],
    ...overrides,
  })

  const renderComponent = (rule?: SmartPlaylistRule) => {
    return h.render(Component, {
      props: {
        rule: rule ?? createRule(),
      },
    })
  }

  it('renders model and operator dropdowns', () => {
    renderComponent()

    // Model select should contain all model labels
    screen.getByRole('option', { name: 'Title' })
    screen.getByRole('option', { name: 'Album' })
    screen.getByRole('option', { name: 'Artist' })

    // Operator select should show text operators for Title (text type)
    screen.getByRole('option', { name: 'is' })
    screen.getByRole('option', { name: 'contains' })
    screen.getByRole('option', { name: 'begins with' })
  })

  it('shows text operators for a text model', () => {
    renderComponent(createRule({ model: titleModel, operator: 'is' }))

    screen.getByRole('option', { name: 'is' })
    screen.getByRole('option', { name: 'is not' })
    screen.getByRole('option', { name: 'contains' })
    screen.getByRole('option', { name: 'does not contain' })
    screen.getByRole('option', { name: 'begins with' })
    screen.getByRole('option', { name: 'ends with' })
  })

  it('shows number operators for a number model', () => {
    renderComponent(createRule({ model: yearModel, operator: 'is' }))

    screen.getByRole('option', { name: 'is' })
    screen.getByRole('option', { name: 'is not' })
    screen.getByRole('option', { name: 'is greater than' })
    screen.getByRole('option', { name: 'is less than' })
    screen.getByRole('option', { name: 'is between' })
  })

  it('shows date operators for a date model', () => {
    renderComponent(createRule({ model: lastPlayedModel, operator: 'is' }))

    screen.getByRole('option', { name: 'is' })
    screen.getByRole('option', { name: 'is not' })
    screen.getByRole('option', { name: 'in the last' })
    screen.getByRole('option', { name: 'not in the last' })
    screen.getByRole('option', { name: 'is between' })
  })

  it('shows two inputs for the isBetween operator', async () => {
    renderComponent(createRule({ model: yearModel, operator: 'isBetween', value: ['2000', '2020'] }))

    await waitFor(() => {
      expect(screen.getAllByRole('spinbutton')).toHaveLength(2)
    })
  })

  it('shows "days" suffix for inLast operator', () => {
    renderComponent(createRule({ model: lastPlayedModel, operator: 'inLast', value: ['7'] }))
    screen.getByText('days')
  })

  it('shows "seconds" suffix for the length model', () => {
    renderComponent(createRule({ model: lengthModel, operator: 'is', value: ['300'] }))
    screen.getByText('seconds')
  })

  it('emits remove when remove button is clicked', async () => {
    const { emitted } = renderComponent()

    await h.user.click(screen.getByTitle('Remove this rule'))

    expect(emitted().remove).toBeTruthy()
  })

  it('emits input on value change', async () => {
    const { emitted } = renderComponent(createRule({ model: titleModel, operator: 'is', value: [''] }))

    await waitFor(() => screen.getByRole('textbox'))
    await h.type(screen.getByRole('textbox'), 'foo')

    expect(emitted().input).toBeTruthy()
  })
})
