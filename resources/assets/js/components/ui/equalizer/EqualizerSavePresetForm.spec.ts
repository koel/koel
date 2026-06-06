import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EqualizerSavePresetForm.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('equalizerSavePresetForm.vue', () => {
  const h = createHarness()

  beforeEach(() => mockShowConfirmDialog.mockReset())

  it('emits submit with the trimmed name on form submit', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, '  My Bass Boost  ')
    await fireEvent.submit(input.form!)

    expect(emitted().submit).toEqual([['My Bass Boost']])
  })

  it('does not emit submit when the name is whitespace-only', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, '   ')
    await fireEvent.submit(input.form!)

    expect(emitted().submit).toBeUndefined()
  })

  it('emits cancel immediately when Cancel is clicked and the form is pristine', async () => {
    const { emitted } = h.render(Component)

    await fireEvent.click(screen.getByText('Cancel'))

    expect(emitted().cancel).toHaveLength(1)
    expect(mockShowConfirmDialog).not.toHaveBeenCalled()
  })

  it('asks to discard before emitting cancel when the form is dirty', async () => {
    mockShowConfirmDialog.mockResolvedValueOnce(true)
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, 'Custom')
    await fireEvent.click(screen.getByText('Cancel'))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Discard preset name?')
    expect(emitted().cancel).toHaveLength(1)
  })

  it('does not emit cancel when the user declines the discard dialog', async () => {
    mockShowConfirmDialog.mockResolvedValueOnce(false)
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, 'Custom')
    await fireEvent.click(screen.getByText('Cancel'))

    expect(mockShowConfirmDialog).toHaveBeenCalled()
    expect(emitted().cancel).toBeUndefined()
  })

  it('emits cancel via Escape when pristine', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name')
    await fireEvent.keyDown(input, { key: 'Escape' })

    expect(emitted().cancel).toHaveLength(1)
  })
})
