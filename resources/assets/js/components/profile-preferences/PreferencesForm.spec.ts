import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { Filter } from '@/config/hooks'
import { addFilter, type HookHandle, removeFilter } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component, { type PreferenceRow } from './PreferencesForm.vue'

describe('preferencesForm', () => {
  const h = createHarness()

  let handle: HookHandle | null = null

  afterEach(() => {
    if (handle) {
      removeFilter(handle)
      handle = null
    }
  })

  it('renders rows added through the preference rows filter', () => {
    const ExtensionRow = defineComponent({ template: '<p data-testid="extension-row" />' })
    handle = addFilter<PreferenceRow[]>(Filter.PREFERENCE_ROWS, rows => [
      ...rows,
      { id: 'extension', component: ExtensionRow },
    ])

    h.render(Component)

    screen.getByTestId('extension-row')
  })

  it('has "Transcode on mobile" option if supported', () => {
    commonStore.state.supports_transcoding = true
    h.render(Component)
    screen.getByTestId('transcode_on_mobile')
  })

  it('does not have "Transcode on mobile" option if not supported', () => {
    commonStore.state.supports_transcoding = false
    h.render(Component)
    expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
  })

  it('shows crossfade controls', () => {
    h.render(Component)
    screen.getByTestId('crossfade-slider')
    screen.getByTestId('crossfade-toggle')
  })

  it('toggles crossfade on via checkbox', async () => {
    preferences.crossfade_duration = 0
    h.render(Component)

    await h.user.click(screen.getByTestId('crossfade-toggle'))
    expect(preferences.crossfade_duration).toBe(7)
  })

  it('toggles crossfade off via checkbox', async () => {
    preferences.crossfade_duration = 5
    h.render(Component)

    await h.user.click(screen.getByTestId('crossfade-toggle'))
    expect(preferences.crossfade_duration).toBe(0)
  })

  it('hides the upload visibility and public media options from guests', async () => {
    h.actingAsUser(h.factory('user').make({ abilities: [], role: 'guest' }) as CurrentUser)

    await h.withPlusEdition(() => {
      const { container } = h.render(Component)

      expect(container.querySelector('[name="make_uploads_public"]')).toBeNull()
      expect(container.querySelector('[name="include_public_media"]')).toBeNull()
    })
  })

  it('shows the upload visibility and public media options to Plus users', async () => {
    h.actingAsUser(h.factory('user').make({ abilities: [] }) as CurrentUser)

    await h.withPlusEdition(() => {
      const { container } = h.render(Component)

      expect(container.querySelector('[name="make_uploads_public"]')).not.toBeNull()
      expect(container.querySelector('[name="include_public_media"]')).not.toBeNull()
    })
  })
})
