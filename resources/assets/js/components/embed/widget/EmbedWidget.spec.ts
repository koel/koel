import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { embedService } from '@/stores/embedService'
import { themeStore } from '@/stores/themeStore'
import { screen, waitFor } from '@testing-library/vue'
import Component from './EmbedWidget.vue'

describe('embedWidget.vue', async () => {
  const h = createHarness()

  const renderComponent = async (embed?: WidgetReadyEmbed, options?: EmbedOptions, getWidgetPayloadMock?: any) => {
    embed = embed ?? {
      ...h.factory('embed'),
      embeddable: h.factory('playlist'),
      playables: h.factory('song', 5),
    }

    options = options ?? {
      theme: 'classic',
      layout: 'full',
      preview: false,
    }

    getWidgetPayloadMock = getWidgetPayloadMock ?? h.mock(embedService, 'getWidgetPayload').mockResolvedValueOnce({
      embed,
      options,
    })

    const initThemeMock = h.mock(themeStore, 'init')

    h.visit(`/embed/${embed.id}/encrypted-options`)

    const rendered = h.render(Component, {
      props: {
        embed,
        options,
      },
      global: {
        stubs: {
          Banner: h.stub('banner'),
          TrackList: h.stub('track-list'),
          ErrorMessage: h.stub('error-message'),
        },
      },
    })

    return {
      ...rendered,
      embed,
      options,
      getWidgetPayloadMock,
      initThemeMock,
    }
  }

  it('renders an embed', async () => {
    const { embed, getWidgetPayloadMock, initThemeMock } = await renderComponent()

    await waitFor(() => {
      expect(getWidgetPayloadMock).toHaveBeenCalledWith(embed.id, 'encrypted-options')
      expect(initThemeMock).toHaveBeenCalledWith('classic')

      screen.getByTestId('banner')
      screen.getByTestId('track-list')

      expect(screen.queryByTestId('error-message')).toBeNull()
    })
  })

  it('shows an error message if the widget payload cannot be resolved', async () => {
    const { embed, getWidgetPayloadMock, initThemeMock } = await renderComponent(
      undefined,
      undefined,
      h.mock(embedService, 'getWidgetPayload').mockRejectedValueOnce(new Error('Failed to load widget payload')),
    )

    await waitFor(() => {
      expect(getWidgetPayloadMock).toHaveBeenCalledWith(embed.id, 'encrypted-options')
      expect(initThemeMock).not.toHaveBeenCalled()

      expect(screen.queryByTestId('banner')).toBeNull()
      expect(screen.queryByTestId('track-list')).toBeNull()

      screen.getByTestId('error-message')
    })
  })
})
