import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { usePolicies } from './usePolicies'

describe('usePolicies', () => {
  const h = createHarness()

  it('allows admin to edit any song', () => {
    h.actingAsUser({
      ...h.factory('user'),
      permissions: ['manage songs'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    const song = h.factory('song', { owner_id: '999' })

    expect(currentUserCan.editSong(song)).toBe(true)
  })

  it('allows Plus user to edit their own songs', async () => {
    const user = h.factory('user', { permissions: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      const ownSong = h.factory('song', { owner_id: user.id })

      expect(currentUserCan.editSong(ownSong)).toBe(true)
    })
  })

  it('denies Plus user editing others songs', async () => {
    const user = h.factory('user', { permissions: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      const otherSong = h.factory('song', { owner_id: '999' })

      expect(currentUserCan.editSong(otherSong)).toBe(false)
    })
  })

  it('denies non-Plus non-admin editing songs', () => {
    h.actingAsUser({
      ...h.factory('user'),
      permissions: [],
    } as CurrentUser)

    commonStore.state.koel_plus.active = false
    const { currentUserCan } = usePolicies()

    expect(currentUserCan.editSong(h.factory('song'))).toBe(false)
  })

  it('reads the edit permission embedded in the playlist', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('playlist', { permissions: { edit: true } })
    const readonly = h.factory('playlist', { permissions: { edit: false } })

    expect(currentUserCan.editPlaylist(editable)).toBe(true)
    expect(currentUserCan.editPlaylist(readonly)).toBe(false)
  })

  it('reads the edit permission embedded in the album', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('album', { permissions: { edit: true } })
    const readonly = h.factory('album', { permissions: { edit: false } })

    expect(currentUserCan.editAlbum(editable)).toBe(true)
    expect(currentUserCan.editAlbum(readonly)).toBe(false)
  })

  it('reads the edit permission embedded in the artist', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('artist', { permissions: { edit: true } })
    const readonly = h.factory('artist', { permissions: { edit: false } })

    expect(currentUserCan.editArtist(editable)).toBe(true)
    expect(currentUserCan.editArtist(readonly)).toBe(false)
  })

  it('checks manageSettings permission', () => {
    h.actingAsUser({
      ...h.factory('user'),
      permissions: ['manage settings'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.manageSettings()).toBe(true)
  })

  it('checks manageUsers permission', () => {
    h.actingAsUser({
      ...h.factory('user'),
      permissions: [],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.manageUsers()).toBe(false)
  })

  it('allows upload for Plus users', async () => {
    const user = h.factory('user', { permissions: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      expect(currentUserCan.uploadSongs()).toBe(true)
    })
  })

  it('allows upload for users with manage songs permission', () => {
    h.actingAsUser({
      ...h.factory('user'),
      permissions: ['manage songs'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.uploadSongs()).toBe(true)
  })
})
