<template>
  <article id="lyrics">
    <div class="content">
      <div v-if="song.lyrics" v-html="song.lyrics"/>
      <p class="none" v-if="song.id && !song.lyrics">No lyrics found. Are you not listening to Bach?</p>
    </div>
  </article>
</template>

<script>
export default {
  props: ['song']
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";
</style>
