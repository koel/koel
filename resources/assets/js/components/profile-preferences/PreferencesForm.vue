<template>
  <div class="space-y-3">
    <FormRow v-if="isPlus">
      <div>
        <CheckBox v-model="preferences.make_uploads_public" name="make_uploads_public" />
        {{ $t('preferences.makeUploadedPublic') }}
      </div>
    </FormRow>
    <FormRow v-if="isPlus">
      <div>
        <CheckBox v-model="preferences.include_public_media" name="include_public_media" />
        {{ $t('preferences.showOthersPublicMedia') }}
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
        {{ $t('preferences.showNowPlayingNotification') }}
      </div>
    </FormRow>
    <FormRow v-if="!onMobile">
      <div>
        <CheckBox v-model="preferences.confirm_before_closing" name="confirm_closing" />
        {{ $t('preferences.confirmClosing') }}
      </div>
    </FormRow>
    <FormRow v-if="showTranscodingOption">
      <div>
        <CheckBox
          v-model="preferences.transcode_on_mobile"
          data-testid="transcode_on_mobile"
          name="transcode_on_mobile"
        />
        {{ $t('preferences.convertPlayMedia') }}
        <select
          v-model="preferences.transcode_quality"
          :disabled="!preferences.transcode_on_mobile"
          class="appearance-auto rounded"
        >
          <option v-for="quality in [64, 96, 128, 192, 256, 320]" :key="quality" :value="quality">{{ quality }}</option>
        </select>
        {{ $t('preferences.kbpsOnMobile') }}
      </div>
    </FormRow>
    <FormRow>
      <div>
        <CheckBox v-model="preferences.show_album_art_overlay" name="show_album_art_overlay" />
        {{ $t('preferences.showAlbumArtOverlay') }}
      </div>
    </FormRow>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useKoelPlus } from '@/composables/useKoelPlus'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { t } = useI18n()
const onMobile = isMobile.any
const { isPlus } = useKoelPlus()

const showTranscodingOption = toRef(commonStore.state, 'supports_transcoding')

const continuousPlaybackLabel = computed(() => {
  const typeKeys = [
    'playlists',
    'albums',
    'artists',
    'genres',
    'podcasts',
  ]

  if (commonStore.state.uses_media_browser) {
    typeKeys.push('folder')
  }

  const types = typeKeys.map(key => {
    if (key === 'folder') {
      return t('playlists.folder.name') || 'folder'
    }
    return t(`sidebar.${key}`)
  })

  // For the last item, we need to add "or" before it
  if (types.length > 0) {
    const lastType = types[types.length - 1]
    types[types.length - 1] = `${t('preferences.or')} ${lastType}`
  }

  return t('preferences.continuousPlaybackDescription', { types: types.join(', ') })
})
</script>

<style lang="postcss" scoped>
label {
  @apply text-base;
}
</style>
