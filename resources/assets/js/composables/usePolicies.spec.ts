import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { usePolicies } from './usePolicies'

describe('usePolicies', () => {
  const h = createHarness()

  it('allows admin to edit any song', () => {
    h.actingAsUser({
      ...h.factory('user').make(),
      abilities: ['manage songs'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    const song = h.factory('song').make({ owner_id: '999' })

    expect(currentUserCan.editSong(song)).toBe(true)
  })

  it('allows Plus user to edit their own songs', async () => {
    const user = h.factory('user').make({ abilities: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      const ownSong = h.factory('song').make({ owner_id: user.id })

      expect(currentUserCan.editSong(ownSong)).toBe(true)
    })
  })

  it('denies Plus user editing others songs', async () => {
    const user = h.factory('user').make({ abilities: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      const otherSong = h.factory('song').make({ owner_id: '999' })

      expect(currentUserCan.editSong(otherSong)).toBe(false)
    })
  })

  it('denies non-Plus non-admin editing songs', () => {
    h.actingAsUser({
      ...h.factory('user').make(),
      abilities: [],
    } as CurrentUser)

    commonStore.state.koel_plus.active = false
    const { currentUserCan } = usePolicies()

    expect(currentUserCan.editSong(h.factory('song').make())).toBe(false)
  })

  it('reads the edit permission embedded in the playlist', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('playlist').make({ permissions: { edit: true, delete: false } })
    const readonly = h.factory('playlist').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.editPlaylist(editable)).toBe(true)
    expect(currentUserCan.editPlaylist(readonly)).toBe(false)
  })

  it('reads the delete permission embedded in the playlist', () => {
    const { currentUserCan } = usePolicies()
    const deletable = h.factory('playlist').make({ permissions: { edit: false, delete: true } })
    const readonly = h.factory('playlist').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.deletePlaylist(deletable)).toBe(true)
    expect(currentUserCan.deletePlaylist(readonly)).toBe(false)
  })

  it('reads the edit permission embedded in the album', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('album').make({ permissions: { edit: true } })
    const readonly = h.factory('album').make({ permissions: { edit: false } })

    expect(currentUserCan.editAlbum(editable)).toBe(true)
    expect(currentUserCan.editAlbum(readonly)).toBe(false)
  })

  it('reads the edit permission embedded in the artist', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('artist').make({ permissions: { edit: true } })
    const readonly = h.factory('artist').make({ permissions: { edit: false } })

    expect(currentUserCan.editArtist(editable)).toBe(true)
    expect(currentUserCan.editArtist(readonly)).toBe(false)
  })

  it('checks manageSettings permission', () => {
    h.actingAsUser({
      ...h.factory('user').make(),
      abilities: ['manage settings'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.manageSettings()).toBe(true)
  })

  it('checks manageUsers permission', () => {
    h.actingAsUser({
      ...h.factory('user').make(),
      abilities: [],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.manageUsers()).toBe(false)
  })

  it('allows upload for Plus users', async () => {
    const user = h.factory('user').make({ abilities: [] }) as CurrentUser
    h.actingAsUser(user)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      expect(currentUserCan.uploadSongs()).toBe(true)
    })
  })

  it('allows upload for users with manage songs permission', () => {
    h.actingAsUser({
      ...h.factory('user').make(),
      abilities: ['manage songs'],
    } as CurrentUser)

    const { currentUserCan } = usePolicies()
    expect(currentUserCan.uploadSongs()).toBe(true)
  })

  it('forbids upload for Plus guests', async () => {
    const guest = h.factory('user').make({ abilities: [], role: 'guest' }) as CurrentUser
    h.actingAsUser(guest)

    await h.withPlusEdition(async () => {
      const { currentUserCan } = usePolicies()
      expect(currentUserCan.uploadSongs()).toBe(false)
    })
  })

  it('reads the edit permission embedded in the user', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('user').make({ permissions: { edit: true, delete: false } })
    const readonly = h.factory('user').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.editUser(editable)).toBe(true)
    expect(currentUserCan.editUser(readonly)).toBe(false)
  })

  it('reads the delete permission embedded in the user', () => {
    const { currentUserCan } = usePolicies()
    const deletable = h.factory('user').make({ permissions: { edit: false, delete: true } })
    const readonly = h.factory('user').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.deleteUser(deletable)).toBe(true)
    expect(currentUserCan.deleteUser(readonly)).toBe(false)
  })

  it('reads the edit permission embedded in the radio station', () => {
    const { currentUserCan } = usePolicies()
    const editable = h.factory('radio-station').make({ permissions: { edit: true, delete: false } })
    const readonly = h.factory('radio-station').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.editRadioStation(editable)).toBe(true)
    expect(currentUserCan.editRadioStation(readonly)).toBe(false)
  })

  it('reads the delete permission embedded in the radio station', () => {
    const { currentUserCan } = usePolicies()
    const deletable = h.factory('radio-station').make({ permissions: { edit: false, delete: true } })
    const readonly = h.factory('radio-station').make({ permissions: { edit: false, delete: false } })

    expect(currentUserCan.deleteRadioStation(deletable)).toBe(true)
    expect(currentUserCan.deleteRadioStation(readonly)).toBe(false)
  })
})
