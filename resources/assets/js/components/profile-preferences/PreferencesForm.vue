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
        Playing a song triggers continuous playback of the entire playlist, album, artist, or genre
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
    <FormRow v-if="isPhone">
      <div>
        <CheckBox v-model="preferences.transcode_on_mobile" name="transcode_on_mobile" />
        Convert and play media at 128kbps on mobile
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
import { preferenceStore as preferences } from '@/stores'
import { useKoelPlus } from '@/composables'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const isPhone = isMobile.phone
const { isPlus } = useKoelPlus()
</script>

<style lang="postcss" scoped>
label {
  @apply text-base;
}
</style>
