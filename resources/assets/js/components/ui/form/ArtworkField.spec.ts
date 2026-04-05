import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor, fireEvent } from '@testing-library/vue'
import Component from './ArtworkField.vue'

describe('artworkField.vue', () => {
  const h = createHarness()

  const renderComponent = (defaultValue: null | string = null) =>
    h.render(Component, {
      props: {
        modelValue: defaultValue,
      },
    })

  it('shows a preview upon selecting an image and allows removing it', async () => {
    const { emitted } = renderComponent()

    await h.user.upload(
      screen.getByLabelText('Select or paste a file…'),
      new File(['bytes'], 'artwork.png', { type: 'image/png' }),
    )

    await waitFor(() => {
      expect(screen.getByRole('img').getAttribute('src')).toBe('data:image/png;base64,Ynl0ZXM=')
      expect(emitted()['update:modelValue'][0]).toStrictEqual(['data:image/png;base64,Ynl0ZXM='])
    })

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(screen.queryByRole('img')).toBeNull()
    expect(emitted()['update:modelValue'][1]).toStrictEqual([''])
  })

  it('shows the default value as an image and allows reverting to it', async () => {
    const { emitted } = renderComponent('https://example.com/image.png')
    expect(screen.getByRole('img').getAttribute('src')).toBe('https://example.com/image.png')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(screen.queryByRole('img')).toBeNull()
    expect(emitted()['update:modelValue'][0]).toStrictEqual([''])

    await h.user.upload(
      screen.getByLabelText('Select or paste a file…'),
      new File(['bytes'], 'artwork.png', { type: 'image/png' }),
    )

    await waitFor(() => {
      expect(screen.getByRole('img').getAttribute('src')).toBe('data:image/png;base64,Ynl0ZXM=')
      expect(emitted()['update:modelValue'][1]).toStrictEqual(['data:image/png;base64,Ynl0ZXM='])
    })

    await h.user.click(screen.getByRole('button', { name: 'Revert' }))
    expect(screen.getByRole('img').getAttribute('src')).toBe('https://example.com/image.png')
    expect(emitted()['update:modelValue'][2]).toStrictEqual(['https://example.com/image.png'])
  })

  it('accepts pasted image data', async () => {
    const { emitted, container } = renderComponent()

    const file = new File(['pasted-bytes'], 'image.png', { type: 'image/png' })

    await fireEvent.paste(container.querySelector<HTMLElement>('article')!, {
      clipboardData: { files: [file] } as unknown as DataTransfer,
    })

    await waitFor(() => {
      expect(screen.getByRole('img').getAttribute('src')).toBe('data:image/png;base64,cGFzdGVkLWJ5dGVz')
      expect(emitted()['update:modelValue'][0]).toStrictEqual(['data:image/png;base64,cGFzdGVkLWJ5dGVz'])
    })
  })

  it('ignores pasted non-image data', async () => {
    const { emitted, container } = renderComponent()

    const file = new File(['text'], 'doc.txt', { type: 'text/plain' })

    await fireEvent.paste(container.querySelector<HTMLElement>('article')!, {
      clipboardData: { files: [file] } as unknown as DataTransfer,
    })

    await h.tick()

    expect(screen.queryByRole('img')).toBeNull()
    expect(emitted()['update:modelValue']).toBeUndefined()
  })
})
