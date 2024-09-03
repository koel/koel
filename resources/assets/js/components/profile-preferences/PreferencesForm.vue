<template>
  <div class="space-y-3">
    <FormRow v-if="isPlus">
      <div>
        <CheckBox v-model="preferences.make_uploads_public" name="make_upload_public" />
        Make uploaded songs public by default
      </div>
    </FormRow>
    <FormRow>
      <div>
        <CheckBox v-model="preferences.continuous_playback" name="continuous_playback" />
        Playing a song or episode triggers continuous playback of the entire playlist, album, artist, genre, or podcast
      </div>
    </FormRow>
    <FormRow v-if="isPhone">
      <div>
        <CheckBox v-model="preferences.show_now_playing_notification" name="notify" />
        Show “Now Playing” notification
      </div>
    </FormRow>
    <FormRow v-if="!isPhone">
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
          <option v-for="quality in [64, 96, 128, 192, 256, 320]" :value="quality" :key="quality">{{ quality }}</option>
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
import { computed } from 'vue'
import { commonStore, preferenceStore as preferences } from '@/stores'
import { useKoelPlus } from '@/composables'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const isPhone = isMobile.phone
const { isPlus } = useKoelPlus()

const showTranscodingOption = computed(() => isPhone && commonStore.state.supports_transcoding)
</script>

<style lang="postcss" scoped>
label {
  @apply text-base;
}
</style>
