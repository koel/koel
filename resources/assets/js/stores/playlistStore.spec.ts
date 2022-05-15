import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistStore } from '@/stores'
import { expect, it } from 'vitest'

const ruleGroups: SmartPlaylistRuleGroup[] = [
  {
    id: 1,
    rules: [
      {
        id: 1,
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist'
        },
        operator: 'is',
        value: ['Elvis Presley']
      }
    ]
  },
  {
    id: 2,
    rules: [
      {
        id: 1,
        model: {
          name: 'artist.name',
          type: 'text',
          label: 'Artist'
        },
        operator: 'is',
        value: ['Queen']
      }
    ]
  }
]

const serializedRuleGroups = [
  {
    id: 1,
    rules: [
      {
        id: 1,
        model: 'artist.name',
        operator: 'is',
        value: ['Elvis Presley']
      }
    ]
  },
  {
    id: 2,
    rules: [
      {
        id: 1,
        model: 'artist.name',
        operator: 'is',
        value: ['Queen']
      }
    ]
  }
]

new class extends UnitTestCase {
  protected test () {
    it('serializes playlist for storage', () => {
      expect(playlistStore.serializeSmartPlaylistRulesForStorage(ruleGroups)).toEqual(serializedRuleGroups)
    })

    it('sets up a smart playlist with properly unserialized rules', () => {
      const playlist = factory<Playlist>('playlist', {
        is_smart: true,
        rules: serializedRuleGroups as unknown as SmartPlaylistRuleGroup[]
      })

      playlistStore.setupSmartPlaylist(playlist)

      expect(playlist.rules).toEqual(ruleGroups)
    })
  }
}
