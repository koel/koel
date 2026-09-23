import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './EmptyLibraryHint.vue'

describe('emptyLibraryHint.vue', () => {
  const h = createHarness()

  it('asks an admin to set up the library when no media path is set', () => {
    commonStore.state.storage_driver = 'local'
    commonStore.state.media_path_set = false

    h.actingAsAdmin().render(Component)

    screen.getByText('Have you set up your library yet?')
  })

  it('invites an upload when the library is set up but empty', () => {
    commonStore.state.storage_driver = 'local'
    commonStore.state.media_path_set = true

    h.actingAsAdmin().render(Component)

    expect(screen.getByText('Upload some music').getAttribute('href')).toContain('/upload')
  })

  it('invites an upload on cloud storage, where there is no media path to set', () => {
    commonStore.state.storage_driver = 's3'
    commonStore.state.media_path_set = false

    h.actingAsAdmin().render(Component)

    screen.getByText('Upload some music')
    expect(screen.queryByText('Have you set up your library yet?')).toBeNull()
  })

  it('says nothing to a user who can neither configure nor upload', () => {
    commonStore.state.storage_driver = 'local'
    commonStore.state.media_path_set = false

    h.actingAsUser().render(Component)

    expect(screen.queryByText('Have you set up your library yet?')).toBeNull()
    expect(screen.queryByText('Upload some music')).toBeNull()
  })
})
