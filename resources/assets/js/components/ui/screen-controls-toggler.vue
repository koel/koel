<template>
  <span class="controls-toggler text-orange" v-if="isPhone" @click="toggleControls">
    <i class="fa fa-angle-up toggler" v-if="showingControls"/>
    <i class="fa fa-angle-down toggler" v-else/>
  </span>
</template>

<script lang="ts">
import Vue from 'vue'
import isMobile from 'ismobilejs'

export default Vue.extend({
  props: {
    showingControls: {
      type: Boolean,
      default: true
    }
  },

  data: () => ({
    isPhone: isMobile.phone
  }),

  methods: {
    toggleControls (): void {
      this.$emit('toggleControls')
    }
  }
})
</script>

<style lang="scss" scoped>
.toggler {
  font-size: 1rem;
  margin-left: 4px;
}
</style>
