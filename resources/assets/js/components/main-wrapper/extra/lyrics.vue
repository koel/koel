<template>
  <article id="lyrics">
    <div class="content">
      <div v-if="song.lyrics" v-html="song.lyrics"/>
      <p class="none" v-if="song.id && !song.lyrics">未找到歌词文件</p>
    </div>
  </article>
</template>

<script>
export default {
  props: ['song']
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";
</style>
