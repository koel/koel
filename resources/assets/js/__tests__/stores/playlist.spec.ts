import factory from '@/__tests__/factory'
import { playlistStore } from '@/stores'

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

describe('stores/playlist', () => {
  it('serializes playlist for storage', () => {
    expect(playlistStore.serializeSmartPlaylistRulesForStorage(ruleGroups)).toEqual(serializedRuleGroups)
  })

  it('set up a smart playlist with properly unserialized rules', () => {
    // @ts-ignore because we're only using this factory here for convenience.
    // By right, the database "serializedRuleGroups" can't be used for Playlist type.
    const playlist = factory<Playlist>('playlist', {
      is_smart: true,
      rules: serializedRuleGroups
    })

    playlistStore.setupSmartPlaylist(playlist)
    expect(playlist.rules).toEqual(ruleGroups)
  })
})
