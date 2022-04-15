<template>
  <section id="settingsWrapper">
    <ScreenHeader>Settings</ScreenHeader>

    <form @submit.prevent="confirmThenSave" class="main-scroll-wrap">
      <div class="form-row">
        <label for="inputSettingsPath">Media Path</label>

        <p class="help" id="mediaPathHelp">
          The <em>absolute</em> path to the server directory containing your media.
          Koel will scan this directory for songs and extract any available information.<br>
          Scanning may take a while, especially if you have a lot of songs, so be patient.
        </p>

        <input
          aria-describedby="mediaPathHelp"
          id="inputSettingsPath"
          type="text"
          v-model="state.media_path"
          name="media_path"
        >
      </div>

      <div class="form-row">
        <Btn type="submit">Scan</Btn>
      </div>
    </form>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive } from 'vue'
import { settingStore, sharedStore } from '@/stores'
import { alerts, forceReloadWindow, hideOverlay, parseValidationError, showOverlay } from '@/utils'
import router from '@/router'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))

const state = settingStore.state
const sharedState = reactive(sharedStore.state)

const shouldWarn = computed(() => {
  // Warn the user if the media path is not empty and about to change.
  if (!sharedState.originalMediaPath || !state.media_path) {
    return false
  }

  return sharedState.originalMediaPath !== state.media_path.trim()
})

const save = async () => {
  showOverlay()

  try {
    await settingStore.update()
    // Make sure we're back to home first.
    router.go('home')
    forceReloadWindow()
  } catch (err: any) {
    hideOverlay()

    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    alerts.error(msg)
  }
}

const confirmThenSave = () => {
  if (shouldWarn.value) {
    alerts.confirm('Warning: Changing the media path will essentially remove all existing data – songs, artists, \
          albums, favorites, everything – and empty your playlists! Sure you want to proceed?', save)
  } else {
    save()
  }
}
</script>

<style lang="scss">
#settingsWrapper {
  input[type="text"] {
    width: 50%;
    margin-top: 1rem;
  }

  @media only screen and (max-width: 667px) {
    input[type="text"] {
      width: 100%;
    }
  }
}
</style>
