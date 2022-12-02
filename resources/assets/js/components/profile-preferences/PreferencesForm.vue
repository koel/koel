<template>
  <div>
    <div v-if="!isPhone" class="form-row">
      <label>
        <CheckBox v-model="preferences.notify" name="notify" />
        Show “Now Playing” song notification
      </label>
    </div>
    <div v-if="!isPhone" class="form-row">
      <label>
        <CheckBox v-model="preferences.confirmClosing" name="confirm_closing" />
        Confirm before closing Koel
      </label>
    </div>
    <div v-if="isPhone" class="form-row">
      <label>
        <CheckBox v-model="preferences.transcodeOnMobile" name="transcode_on_mobile" />
        Convert and play media at 128kbps on mobile
      </label>
    </div>
    <div class="form-row">
      <label>
        <CheckBox v-model="preferences.showAlbumArtOverlay" name="show_album_art_overlay" />
        Show a translucent, blurred overlay of the current album’s art
      </label>
    </div>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { preferenceStore as preferences } from '@/stores'
import CheckBox from '@/components/ui/CheckBox.vue'

const isPhone = isMobile.phone
</script>

<style lang="scss" scoped>
label {
  font-size: 1rem;
}
</style>
