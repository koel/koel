<template>
  <span class="song-list-controls-toggler" v-if="isPhone" @click="toggleControls">
    <i class="fa fa-angle-down toggler" v-show="!showingControls"/>
    <i class="fa fa-angle-up toggler" v-show="showingControls"/>
  </span>
</template>

<script>
import isMobile from 'ismobilejs'

export default {
  name: 'shared--song-list-controls-toggler',
  props: ['showingControls'],

  data () {
    return {
      isPhone: isMobile.phone
    }
  },

  methods: {
    toggleControls () {
      this.$emit('toggleControls')
    }
  }
}
</script>

<style lang="sass" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.toggler {
  font-size: 1rem;
  margin-left: 4px;
  color: $colorHighlight;
}
</style>
