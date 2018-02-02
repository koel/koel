<template>
  <article id="similar">
    <div class="content">
      <section class="track-listing" v-if="song.similar.tracks.length">
        <h1>Similar Tracks</h1>
        <ul class="tracks">
          <li v-for="track in song.similar.tracks">
            {{ track.title }} - {{ track.artist }}
          </li>
         </ul>
        </section>
      <p class="none" v-if="song.id && !song.lyrics">No similar tracks found.</p>
    </div>
  </article>
</template>

<script>
export default {
  props: {
    song: {
      type: Object,
      required: true
    }
  }
}
</script>

<style lang="scss">
@import "~#/partials/_vars.scss";
@import "~#/partials/_mixins.scss";
#similar {
  @include artist-album-info();
}
</style>
