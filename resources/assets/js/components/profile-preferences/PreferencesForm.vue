<template>
  <div>
    <div class="form-row" v-if="!isPhone">
      <label>
        <CheckBox name="notify" v-model="preferences.notify"/>
        Show “Now Playing” song notification
      </label>
    </div>
    <div class="form-row" v-if="!isPhone">
      <label>
        <CheckBox name="confirm_closing" v-model="preferences.confirmClosing"/>
        Confirm before closing Koel
      </label>
    </div>
    <div class="form-row" v-if="isPhone">
      <label>
        <CheckBox name="transcode_on_mobile" v-model="preferences.transcodeOnMobile"/>
        <input type="checkbox" name="transcode_on_mobile" v-model="preferences.transcodeOnMobile">
        Convert and play media at 128kbps on mobile
      </label>
    </div>
    <div class="form-row">
      <label>
        <CheckBox name="show_album_art_overlay" v-model="preferences.showAlbumArtOverlay"/>
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
