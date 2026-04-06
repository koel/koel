<template>
  <div class="space-y-4">
    <FormRow v-if="isPlus">
      <label class="pref-row">
        <span>Make uploaded songs public by default</span>
        <CheckBox v-model="preferences.make_uploads_public" name="make_uploads_public" />
      </label>
    </FormRow>
    <FormRow v-if="canUpload">
      <label class="pref-row">
        <span>Detect and flag duplicate file uploads</span>
        <CheckBox v-model="preferences.detect_duplicate_uploads" name="detect_duplicate_uploads" />
      </label>
    </FormRow>
    <FormRow v-if="isPlus">
      <label class="pref-row">
        <span
          >Show other users' public songs, albums, artists, and radio stations in your library (reload required)</span
        >
        <CheckBox v-model="preferences.include_public_media" name="include_public_media" />
      </label>
    </FormRow>
    <FormRow>
      <label class="pref-row">
        <span>{{ continuousPlaybackLabel }}</span>
        <CheckBox v-model="preferences.continuous_playback" name="continuous_playback" />
      </label>
    </FormRow>
    <FormRow v-if="onMobile">
      <label class="pref-row">
        <span>Show "Now Playing" notification</span>
        <CheckBox v-model="preferences.show_now_playing_notification" name="notify" />
      </label>
    </FormRow>
    <FormRow v-if="!onMobile">
      <label class="pref-row">
        <span>Confirm before closing Koel</span>
        <CheckBox v-model="preferences.confirm_before_closing" name="confirm_closing" />
      </label>
    </FormRow>
    <FormRow v-if="showTranscodingOption">
      <div class="pref-row">
        <span>
          Convert and play media at
          <select
            v-model="preferences.transcode_quality"
            :disabled="!preferences.transcode_on_mobile"
            class="appearance-auto rounded"
          >
            <option v-for="quality in [64, 96, 128, 192, 256, 320]" :key="quality" :value="quality">
              {{ quality }}
            </option>
          </select>
          kbps on mobile
        </span>
        <CheckBox
          v-model="preferences.transcode_on_mobile"
          data-testid="transcode_on_mobile"
          name="transcode_on_mobile"
        />
      </div>
    </FormRow>
    <FormRow>
      <label class="pref-row">
        <span>Show a translucent, blurred overlay of the current album's art</span>
        <CheckBox v-model="preferences.show_album_art_overlay" name="show_album_art_overlay" />
      </label>
    </FormRow>
    <FormRow>
      <div class="pref-row">
        <span class="flex-1">
          <span class="flex items-center gap-3">
            <label id="crossfade-label" for="crossfade-slider" class="shrink-0">Crossfade songs</label>
            <input
              id="crossfade-slider"
              v-model.number="preferences.crossfade_duration"
              type="range"
              min="0"
              max="15"
              step="1"
              data-testid="crossfade-slider"
              class="crossfade-slider flex-1 min-w-32 max-w-96"
            />
            <span class="text-k-fg-50 shrink-0">
              {{ crossfadeEnabled ? `${preferences.crossfade_duration}s` : 'Off' }}
            </span>
          </span>
        </span>
        <CheckBox
          :model-value="crossfadeEnabled"
          name="crossfade"
          data-testid="crossfade-toggle"
          @update:model-value="toggleCrossfade"
        />
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
import { usePolicies } from '@/composables/usePolicies'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const onMobile = isMobile.any
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()
const canUpload = currentUserCan.uploadSongs()

const showTranscodingOption = toRef(commonStore.state, 'supports_transcoding')

const crossfadeEnabled = computed(() => preferences.crossfade_duration > 0)

const toggleCrossfade = (enabled: boolean) => {
  preferences.crossfade_duration = enabled ? 7 : 0
}

const continuousPlaybackLabel = computed(() => {
  const types = ['playlist', 'album', 'artist', 'genre', 'podcast']

  if (commonStore.state.uses_media_browser) {
    types.push('folder')
  }

  types[types.length - 1] = `or ${types[types.length - 1]}`

  return `Playing a song or episode triggers continuous playback of the entire ${types.join(', ')}`
})
</script>

<style lang="postcss" scoped>
.pref-row {
  @apply flex items-center gap-4 cursor-pointer;

  > :first-child {
    @apply flex-1;
  }
}

.crossfade-slider {
  appearance: none;
  height: 4px;
  border-radius: 2px;
  outline: none;
  @apply bg-k-fg-10;
  cursor: pointer;
}

.crossfade-slider::-webkit-slider-thumb {
  appearance: none;
  height: 14px;
  width: 14px;
  border-radius: 50%;
  border: 0;
  cursor: pointer;
  @apply bg-k-fg;
}

.crossfade-slider::-moz-range-thumb {
  height: 14px;
  width: 14px;
  border-radius: 50%;
  border: 0;
  cursor: pointer;
  @apply bg-k-fg;
}
</style>
