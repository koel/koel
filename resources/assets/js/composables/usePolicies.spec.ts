import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { acl } from '@/services/acl'
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

  it('allows editing own playlist', () => {
    const user = h.factory('user') as CurrentUser
    h.actingAsUser(user)

    const { currentUserCan } = usePolicies()
    const playlist = h.factory('playlist', { owner_id: user.id })

    expect(currentUserCan.editPlaylist(playlist)).toBe(true)
  })

  it('denies editing others playlist', () => {
    h.actingAsUser(h.factory('user') as CurrentUser)

    const { currentUserCan } = usePolicies()
    const playlist = h.factory('playlist', { owner_id: '999' })

    expect(currentUserCan.editPlaylist(playlist)).toBe(false)
  })

  it('delegates album editing to ACL', async () => {
    const checkMock = h.mock(acl, 'checkResourcePermission').mockResolvedValue(true)
    const { currentUserCan } = usePolicies()
    const album = h.factory('album')

    await expect(currentUserCan.editAlbum(album)).resolves.toBe(true)
    expect(checkMock).toHaveBeenCalledWith('album', album.id, 'edit')
  })

  it('delegates artist editing to ACL', async () => {
    const checkMock = h.mock(acl, 'checkResourcePermission').mockResolvedValue(false)
    const { currentUserCan } = usePolicies()
    const artist = h.factory('artist')

    await expect(currentUserCan.editArtist(artist)).resolves.toBe(false)
    expect(checkMock).toHaveBeenCalledWith('artist', artist.id, 'edit')
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
