<template>
  <div class="space-y-3">
    <FormRow v-if="isPlus">
      <div>
        <CheckBox v-model="preferences.make_uploads_public" name="make_uploads_public" />
        Make uploaded songs public by default
      </div>
    </FormRow>
    <FormRow v-if="isPlus">
      <div>
        <CheckBox v-model="preferences.include_public_media" name="include_public_media" />
        Show other users’ public songs, albums, artists, and radio stations in your library (reload required)
      </div>
    </FormRow>
    <FormRow>
      <div>
        <CheckBox v-model="preferences.continuous_playback" name="continuous_playback" />
        {{ continuousPlaybackLabel }}
      </div>
    </FormRow>
    <FormRow v-if="onMobile">
      <div>
        <CheckBox v-model="preferences.show_now_playing_notification" name="notify" />
        Show “Now Playing” notification
      </div>
    </FormRow>
    <FormRow v-if="!onMobile">
      <div>
        <CheckBox v-model="preferences.confirm_before_closing" name="confirm_closing" />
        Confirm before closing Koel
      </div>
    </FormRow>
    <FormRow v-if="showTranscodingOption">
      <div>
        <CheckBox
          v-model="preferences.transcode_on_mobile"
          data-testid="transcode_on_mobile"
          name="transcode_on_mobile"
        />
        Convert and play media at
        <select
          v-model="preferences.transcode_quality"
          :disabled="!preferences.transcode_on_mobile"
          class="appearance-auto rounded"
        >
          <option v-for="quality in [64, 96, 128, 192, 256, 320]" :key="quality" :value="quality">{{ quality }}</option>
        </select>
        kbps on mobile
      </div>
    </FormRow>
    <FormRow>
      <div>
        <CheckBox v-model="preferences.show_album_art_overlay" name="show_album_art_overlay" />
        Show a translucent, blurred overlay of the current album’s art
      </div>
    </FormRow>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, toRef } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useKoelPlus } from '@/composables/useKoelPlus'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const onMobile = isMobile.any
const { isPlus } = useKoelPlus()

const showTranscodingOption = toRef(commonStore.state, 'supports_transcoding')

const continuousPlaybackLabel = computed(() => {
  const types = [
    'playlist',
    'album',
    'artist',
    'genre',
    'podcast',
  ]

  if (commonStore.state.uses_media_browser) {
    types.push('folder')
  }

  types[types.length - 1] = `or ${types[types.length - 1]}`

  return `Playing a song or episode triggers continuous playback of the entire ${types.join(', ')}`
})
</script>

<style lang="postcss" scoped>
label {
  @apply text-base;
}
</style>
