<template>
  <section id="settingsWrapper">
    <h1 class="heading">
      <span>Settings</span>
    </h1>

    <form @submit.prevent="confirmThenSave" class="main-scroll-wrap">
      <div class="form-row">
        <label for="inputSettingsPath">Media Path</label>
        <p class="help">
          The <em>absolute</em> path to the server directory containing your media.
          Koel will scan this directory for songs and extract any available information.<br>
          Scanning may take a while, especially if you have a lot of songs, so be patient.
        </p>

        <input type="text" v-model="state.settings.media_path" id="inputSettingsPath">
      </div>

      <div class="form-row">
        <button type="submit">Scan</button>
      </div>
    </form>
  </section>
</template>

<script>
import swal from 'sweetalert';

import { settingStore, sharedStore } from '../../../stores';
import { parseValidationError, forceReloadWindow, event, showOverlay, hideOverlay } from '../../../utils';
import router from '../../../router';

export default {
  data() {
    return {
      state: settingStore.state,
      sharedState: sharedStore.state,
    };
  },

  computed: {
    /**
     * Determine if we should warn the user upon saving.
     * @return {boolean}
     */
    shouldWarn() {
      // Warn the user if the media path is not empty and about to change.
      return this.sharedState.originalMediaPath &&
        this.sharedState.originalMediaPath !== this.state.settings.media_path.trim();
    },
  },

  methods: {
    confirmThenSave() {
      if (this.shouldWarn) {
        swal({
          title: 'Be careful!',
          text: 'Changing the media path will essentially remove all existing data – songs, artists, \
          albums, favorites, everything – and empty your playlists!',
          type: 'warning',
          showCancelButton: true,
          confirmButtonText: 'I know. Go ahead.',
          confirmButtonColor: '#c34848',
        }, this.save);
      } else {
        this.save();
      }
    },

    /**
     * Save the settings.
     */
    save() {
      showOverlay();

      settingStore.update().then(() => {
        // Make sure we're back to home first.
        router.go('home');
        forceReloadWindow();
      }).catch(r => {
        let msg = 'Unknown error.';

        if (r.status === 422) {
          msg = parseValidationError(r.responseJSON)[0];
        }

        hideOverlay();

        swal({
          title: 'Something went wrong',
          text: msg,
          type: 'error',
          allowOutsideClick: true,
        });
      });
    },
  },
};
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#settingsWrapper {
  input[type="text"] {
    width: 384px;
    margin-top: 12px;
  }

  @media only screen and (max-width : 667px) {
    input[type="text"] {
      width: 100%;
    }
  }
}
</style>
