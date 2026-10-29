import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import type { Factoria } from 'factoria'
import { embedService } from '@/stores/embedService'

describe('embedService', async () => {
  const h = createHarness()

  it.each<[keyof Factoria.ModelRegistry, string]>([
    ['song', 'playable'],
    ['episode', 'playable'],
    ['album', 'album'],
    ['artist', 'artist'],
    ['playlist', 'playlist'],
  ])('resolves for %s', async (model, embeddableType) => {
    const playable = h.factory(model).make() as Exclude<Embeddable, UserFavorites>
    const embed = h.factory('embed').make()
    const postMock = h.mock(http, 'post').mockResolvedValue(embed)

    const resolved = await embedService.resolveForEmbeddable(playable)

    expect(postMock).toHaveBeenCalledWith('embeds/resolve', {
      embeddable_id: playable.id,
      embeddable_type: embeddableType,
    })

    expect(resolved).toEqual(embed)
  })

  it('resolves for favorites', async () => {
    const favorites: UserFavorites = { type: 'favorites', owner_name: 'Lisa' }
    const postMock = h.mock(http, 'post').mockResolvedValue(h.factory('embed').make())

    await embedService.resolveForEmbeddable(favorites)

    expect(postMock).toHaveBeenCalledWith('embeds/resolve', { embeddable_type: 'favorites' })
  })

  it('encrypts options', async () => {
    const options: EmbedOptions = {
      theme: 'cat',
      layout: 'grid',
      preview: true,
    }

    const postMock = h.mock(http, 'post').mockResolvedValue({ encrypted: 'secret' })

    expect(await embedService.encryptOptions(options)).toEqual('secret')
    expect(postMock).toHaveBeenCalledWith('embed-options', options)
  })

  it('get widget payload', async () => {
    const embed = h.factory('embed').make()
    const options: EmbedOptions = {
      theme: 'cat',
      layout: 'grid',
      preview: true,
    }

    const getMock = h.mock(http, 'get').mockResolvedValue({ embed, options })
    const { embed: returnedEmbed, options: returnedOptions } = await embedService.getWidgetPayload('foo', 'secret')

    expect(getMock).toHaveBeenCalledWith(`embeds/foo/secret`)
    expect(returnedEmbed).toEqual(embed)
    expect(returnedOptions).toEqual(options)
  })

  it('get widget payload with invalid theme', async () => {
    const embed = h.factory('embed').make()
    const options: EmbedOptions = {
      theme: 'invalid-theme',
      layout: 'full',
      preview: false,
    }

    const getMock = h.mock(http, 'get').mockResolvedValue({ embed, options })
    const { embed: returnedEmbed, options: returnedOptions } = await embedService.getWidgetPayload('foo', 'secret')

    expect(getMock).toHaveBeenCalledWith(`embeds/foo/secret`)
    expect(returnedEmbed).toEqual(embed)
    expect(returnedOptions.theme).toEqual('classic')
  })
})
