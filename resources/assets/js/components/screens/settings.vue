<template>
  <section id="settingsWrapper">
    <screen-header>Settings</screen-header>

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
          v-model="state.settings.media_path"
          name="media_path"
        >
      </div>

      <div class="form-row">
        <btn type="submit">Scan</btn>
      </div>
    </form>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { settingStore, sharedStore } from '@/stores'
import { parseValidationError, forceReloadWindow, showOverlay, hideOverlay, alerts } from '@/utils'
import router from '@/router'

export default Vue.extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
    state: settingStore.state,
    sharedState: sharedStore.state
  }),

  computed: {
    shouldWarn (): boolean {
      // Warn the user if the media path is not empty and about to change.
      if (!this.sharedState.originalMediaPath || !this.state.settings.media_path) {
        return false
      }

      return this.sharedState.originalMediaPath !== this.state.settings.media_path.trim()
    }
  },

  methods: {
    confirmThenSave (): void {
      if (this.shouldWarn) {
        alerts.confirm('Warning: Changing the media path will essentially remove all existing data – songs, artists, \
          albums, favorites, everything – and empty your playlists! Sure you want to proceed?', this.save)
      } else {
        this.save()
      }
    },

    save: async (): Promise<void> => {
      showOverlay()

      try {
        await settingStore.update()
        // Make sure we're back to home first.
        router.go('home')
        forceReloadWindow()
      } catch (err) {
        hideOverlay()

        const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
        alerts.error(msg)
      }
    }
  }
})
</script>

<style lang="scss">
#settingsWrapper {
  input[type="text"] {
    width: 50%;
    margin-top: 1rem;
  }

  @media only screen and (max-width : 667px) {
    input[type="text"] {
      width: 100%;
    }
  }
}
</style>
