import { http } from '@/services/http'
import { themeStore } from '@/stores/themeStore'

export const embedService = {
  async resolveForEmbeddable (embeddable: Embeddable) {
    let embeddableType = embeddable.type.slice(0, -1) // remove the last 's' to get the singular form

    if (embeddableType === 'song' || embeddableType === 'episode') {
      embeddableType = 'playable'
    }

    return await http.post<Embed>('embeds/resolve', {
      embeddable_id: embeddable.id,
      embeddable_type: embeddableType,
    })
  },

  async encryptOptions (options: EmbedOptions) {
    const { encrypted } = await http.post<{ encrypted: string }>('embed-options', {
      ...options,
    })

    return encrypted
  },

  async getWidgetPayload (id: string, encryptedOptions: string) {
    const payload = await http.get<{ embed: WidgetReadyEmbed, options: EmbedOptions }>(
      `embeds/${id}/${encryptedOptions}`,
    )

    // Since the theme value can be any literal string, do a sanity check.
    payload.options.theme = themeStore.isValidTheme(payload.options.theme)
      ? payload.options.theme
      : themeStore.getDefaultTheme().id

    return payload
  },
}
