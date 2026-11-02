<template>
  <div class="space-y-4">
    <FormRow v-for="row in rows" :key="row.id">
      <component :is="row.component" v-bind="row.props" />
    </FormRow>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import type { Component } from 'vue'
import { computed, toRef } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { usePolicies } from '@/composables/usePolicies'
import { canUploadFromThisDevice } from '@/utils/uploadAccess'

import FormRow from '@/components/ui/form/FormRow.vue'
import PreferenceToggleRow from '@/components/profile-preferences/PreferenceToggleRow.vue'
import TranscodingPreferenceRow from '@/components/profile-preferences/TranscodingPreferenceRow.vue'
import CrossfadePreferenceRow from '@/components/profile-preferences/CrossfadePreferenceRow.vue'

export interface PreferenceRow {
  id: string
  component: Component
  props?: Record<string, unknown>
}

const onMobile = isMobile.any
const { isPlus } = useKoelPlus()
const { currentUser } = useAuthorization()
const { currentUserCan } = usePolicies()
const canUpload = canUploadFromThisDevice()

const isGuest = computed(() => currentUser.value.role === 'guest')
const showTranscodingOption = toRef(commonStore.state, 'supports_transcoding')

const includePublicMediaLabel = computed(() => {
  const types = ['songs', 'albums', 'artists']

  if (commonStore.state.uses_radio) {
    types.push('radio stations')
  }

  types[types.length - 1] = `and ${types[types.length - 1]}`

  return `Show other users' public ${types.join(', ')} in your library (reload required)`
})

const continuousPlaybackLabel = computed(() => {
  const types = ['playlist', 'album', 'artist', 'genre']

  if (commonStore.state.uses_podcasts) {
    types.push('podcast')
  }

  if (commonStore.state.uses_media_browser) {
    types.push('folder')
  }

  types[types.length - 1] = `or ${types[types.length - 1]}`

  const playable = commonStore.state.uses_podcasts ? 'a song or episode' : 'a song'

  return `Playing ${playable} triggers continuous playback of the entire ${types.join(', ')}`
})

const toggleRow = (preference: string, label: string, name?: string): PreferenceRow => ({
  id: preference,
  component: PreferenceToggleRow,
  props: { preference, label, name },
})

const rows = computed(() =>
  applyFilters<PreferenceRow[]>(Filter.PREFERENCE_ROWS, [
    ...(isPlus.value && currentUserCan.uploadSongs()
      ? [toggleRow('make_uploads_public', 'Make uploaded songs public by default')]
      : []),
    ...(canUpload ? [toggleRow('detect_duplicate_uploads', 'Detect and flag duplicate file uploads')] : []),
    ...(isPlus.value && !isGuest.value ? [toggleRow('include_public_media', includePublicMediaLabel.value)] : []),
    toggleRow('continuous_playback', continuousPlaybackLabel.value),
    ...(onMobile ? [toggleRow('show_now_playing_notification', 'Show "Now Playing" notification', 'notify')] : []),
    ...(!onMobile ? [toggleRow('confirm_before_closing', 'Confirm before closing Koel', 'confirm_closing')] : []),
    ...(showTranscodingOption.value ? [{ id: 'transcoding', component: TranscodingPreferenceRow }] : []),
    toggleRow('stream_lossless_when_supported', 'Stream lossless files as-is if supported'),
    toggleRow('show_album_art_overlay', "Show a translucent, blurred overlay of the current album's art"),
    ...(isPlus.value
      ? [
          toggleRow('normalize_volume', 'Play songs at about the same volume'),
          toggleRow('show_waveform', 'Show waveform during playback'),
        ]
      : []),
    { id: 'crossfade', component: CrossfadePreferenceRow },
  ]),
)
</script>
