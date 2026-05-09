import { describe, expect, it } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EqualizerSavePresetForm.vue'

describe('equalizerSavePresetForm.vue', () => {
  const h = createHarness()

  it('emits submit with the trimmed name when Save is clicked', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, '  My Bass Boost  ')
    await fireEvent.click(screen.getByText('Save'))

    expect(emitted().submit).toEqual([['My Bass Boost']])
  })

  it('disables Save when the name is blank or whitespace', async () => {
    h.render(Component)

    const saveBtn = screen.getByText('Save').closest('button')!
    expect(saveBtn.disabled).toBe(true)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, '   ')
    expect(saveBtn.disabled).toBe(true)

    await fireEvent.update(input, 'Real')
    expect(saveBtn.disabled).toBe(false)
  })

  it('emits cancel when Cancel is clicked', async () => {
    const { emitted } = h.render(Component)

    await fireEvent.click(screen.getByText('Cancel'))

    expect(emitted().cancel).toHaveLength(1)
  })

  it('emits cancel when Escape is pressed in the input', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name')
    await fireEvent.keyDown(input, { key: 'Escape' })

    expect(emitted().cancel).toHaveLength(1)
  })

  it('submits via Enter key', async () => {
    const { emitted } = h.render(Component)

    const input = screen.getByPlaceholderText('Preset name') as HTMLInputElement
    await fireEvent.update(input, 'Quick')
    await fireEvent.submit(input.form!)

    expect(emitted().submit).toEqual([['Quick']])
  })
})
