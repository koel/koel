import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { ModalContextKey } from '@/symbols'
import { embedService } from '@/stores/embedService'
import { screen, waitFor } from '@testing-library/vue'
import Component from './CreateEmbedForm.vue'

describe('createEmbedForm.vue', () => {
  const h = createHarness()

  const renderComponent = async (embeddable?: Embeddable, embed?: Embed) => {
    embeddable = embeddable ?? h.factory('playlist')

    // @ts-ignore
    embed = embed ?? h.factory('embed', {
      embeddable_id: embeddable.id,
      embeddable_type: embeddable.type.slice(0, -1),
    })

    const resolveEmbedMock = h.mock(embedService, 'resolveForEmbeddable').mockResolvedValue(embed)
    const encryptOptionsMock = h.mock(embedService, 'encryptOptions').mockResolvedValueOnce('encrypted-1')

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ embeddable }),
        },
      },
    })

    await waitFor(() => {
      expect(resolveEmbedMock).toHaveBeenCalledWith(embeddable)

      expect(encryptOptionsMock).toHaveBeenCalledWith({
        layout: ['episodes', 'songs'].includes(embeddable.type) ? 'compact' : 'full',
        theme: 'classic',
        preview: false,
      })
    })

    await h.tick()

    return {
      ...rendered,
      embed,
      embeddable,
      resolveEmbedMock,
      encryptOptionsMock,
    }
  }

  it('renders form with widget and options', async () => {
    await renderComponent()

    screen.getByRole('combobox', { name: 'Layout' })
    expect(screen.queryByRole('combobox', { name: 'Theme' })).toBeNull()
  })

  it('updates the preview and the code based on the configuration', async () => {
    const { embed, encryptOptionsMock } = await renderComponent()

    await h.user.click(screen.getByRole('checkbox', { name: 'Show code' }))
    const embedCode: HTMLDivElement = screen.getByTestId('embed-code')

    const iframe: HTMLIFrameElement = screen.getByTestId('embed-preview-iframe')
    expect(iframe.getAttribute('src')).toBe(`http://test/#/embed/${embed.id}/encrypted-1`)
    expect(embedCode.textContent).toContain(`<iframe src="http://test/#/embed/${embed.id}/encrypted-1`)

    encryptOptionsMock.mockResolvedValueOnce('encrypted-2')
    await h.user.selectOptions(screen.getByRole('combobox', { name: 'Layout' }), 'compact')

    expect(iframe.getAttribute('src')).toBe(`http://test/#/embed/${embed.id}/encrypted-2`)
    expect(iframe.contentWindow!.location.reload).toHaveBeenCalled()
    expect(embedCode.textContent).toContain(`<iframe src="http://test/#/embed/${embed.id}/encrypted-2"`)
  })

  it('has a Theme dropdown for Plus user', async () => {
    await h.withPlusEdition(async () => {
      const { embed, encryptOptionsMock } = await renderComponent()

      await h.user.click(screen.getByRole('checkbox', { name: 'Show code' }))
      const embedCode: HTMLDivElement = screen.getByTestId('embed-code')

      screen.getByRole('combobox', { name: 'Layout' })

      encryptOptionsMock.mockResolvedValueOnce('encrypted-2')
      await h.user.selectOptions(screen.getByRole('combobox', { name: 'Theme' }), 'laura')

      const iframe: HTMLIFrameElement = screen.getByTestId('embed-preview-iframe')
      expect(iframe.getAttribute('src')).toBe(`http://test/#/embed/${embed.id}/encrypted-2`)
      expect(iframe.contentWindow!.location.reload).toHaveBeenCalled()
      expect(embedCode.textContent).toContain(`<iframe src="http://test/#/embed/${embed.id}/encrypted-2"`)
    })
  })

  it('copies the code to the clipboard when button is clicked', async () => {
    const { embed } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Copy Code' }))
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
      expect.stringContaining(`<iframe src="http://test/#/embed/${embed.id}/encrypted-1"`),
    )
  })

  it('copies the code to the clipboard when code is clicked', async () => {
    const { embed } = await renderComponent()
    await h.user.click(screen.getByRole('checkbox', { name: 'Show code' }))

    await h.user.click(screen.getByTestId('embed-code'))
    expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
      expect.stringContaining(`<iframe src="http://test/#/embed/${embed.id}/encrypted-1"`),
    )
  })
})
