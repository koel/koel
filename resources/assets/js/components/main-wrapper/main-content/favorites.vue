<template>
  <section id="favoritesWrapper">
    <h1 class="heading">
      <span>你喜爱的音乐
        <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

        <span class="meta" v-show="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && state.songs.length">
            •
            <a href @click.prevent="download" title="Download all songs in playlist">
              下载全部
            </a>
          </template>
        </span>
      </span>

      <song-list-controls
        v-show="state.songs.length && (!isPhone || showingControls)"
        @shuffleAll="shuffleAll"
        @shuffleSelected="shuffleSelected"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list v-show="state.songs.length" :items="state.songs" type="favorites"/>

    <div v-show="!state.songs.length" class="none">
      要记录你喜欢的音乐!
      单击正在播放歌曲的<i style="margin: 0 5px" class="fa fa-heart"></i>图标来添加到我的喜爱列表.
    </div>
  </section>
</template>

<script>
import { pluralize } from '../../../utils'
import { favoriteStore, sharedStore } from '../../../stores'
import { download } from '../../../services'
import hasSongList from '../../../mixins/has-song-list'

export default {
  name: 'main-wrapper--main-content--favorites',
  mixins: [hasSongList],
  filters: { pluralize },

  data () {
    return {
      state: favoriteStore.state,
      sharedState: sharedStore.state
    }
  },

  methods: {
    /**
     * Download all favorite songs.
     */
    download () {
      download.fromFavorites()
    }
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#favoritesWrapper {
  .none {
    color: $color2ndText;
    padding: 16px 24px;

    a {
      color: $colorHighlight;
    }
  }
}
</style>
