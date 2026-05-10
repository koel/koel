import { describe, expect, it } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EqualizerHeader.vue'

const builtInPresets: EqualizerPreset[] = [
  { name: 'Default', preamp: 0, gains: [0, 0, 0] },
  { name: 'Rock', preamp: 5, gains: [5, 3, 1] },
]

const customPresets: EqualizerPreset[] = [{ id: '01HFCUSTOM01', name: 'My Bass', preamp: 3, gains: [4, 4, 0] }]

describe('equalizerHeader.vue', () => {
  const h = createHarness()

  const renderHeader = (
    overrides: Partial<{
      selectedKey: string | null
      customPresets: EqualizerPreset[]
      isModified: boolean
      customSelected: boolean
    }> = {},
  ) =>
    h.render(Component, {
      props: {
        builtInPresets,
        customPresets: overrides.customPresets ?? customPresets,
        isModified: overrides.isModified ?? false,
        customSelected: overrides.customSelected ?? false,
        selectedKey: overrides.selectedKey ?? 'builtin:Default',
      },
    })

  it('renders built-in and custom presets in grouped optgroups', () => {
    renderHeader()

    screen.getByText('Default')
    screen.getByText('Rock')
    screen.getByText('My Bass')
  })

  it('omits the Custom optgroup when there are no custom presets', () => {
    renderHeader({ customPresets: [] })

    expect(screen.queryByText('My Bass')).toBeNull()
  })

  it('hides Save as… when not modified', () => {
    renderHeader({ isModified: false })
    expect(screen.queryByText('Save as…')).toBeNull()
  })

  it('shows Save as… when modified', () => {
    renderHeader({ isModified: true, selectedKey: null })
    screen.getByText('Save as…')
  })

  it('hides Delete when no custom preset is selected', () => {
    renderHeader({ customSelected: false })
    expect(screen.queryByText('Delete')).toBeNull()
  })

  it('shows Delete when a custom preset is selected', () => {
    renderHeader({ customSelected: true, selectedKey: 'custom:01HFCUSTOM01' })
    screen.getByText('Delete')
  })

  it('emits delete when Delete is clicked', async () => {
    const { emitted } = renderHeader({ customSelected: true, selectedKey: 'custom:01HFCUSTOM01' })

    await fireEvent.click(screen.getByText('Delete'))

    expect(emitted().delete).toHaveLength(1)
  })

  it('opens the save form when Save as… is clicked, then closes it on cancel', async () => {
    renderHeader({ isModified: true, selectedKey: null })

    await fireEvent.click(screen.getByText('Save as…'))
    screen.getByPlaceholderText('Preset name')

    await fireEvent.click(screen.getByText('Cancel'))
    expect(screen.queryByPlaceholderText('Preset name')).toBeNull()
  })

  it('emits save with the entered name when the form is submitted, and closes the dialog', async () => {
    const { emitted } = renderHeader({ isModified: true, selectedKey: null })

    await fireEvent.click(screen.getByText('Save as…'))

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, 'Bass Boost')
    await fireEvent.click(screen.getByText('Save'))

    expect(emitted().save).toEqual([['Bass Boost']])
    expect(screen.queryByPlaceholderText('Preset name')).toBeNull()
  })

  it('emits select with the new key when a preset is chosen', async () => {
    const { emitted } = renderHeader()

    await h.user.selectOptions(screen.getByRole('combobox'), 'builtin:Rock')

    expect(emitted().select).toEqual([['builtin:Rock']])
  })
})
