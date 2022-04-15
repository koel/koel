<template>
  <input :type="type" v-model="mutableValue" name="value[]" required>
</template>

<script lang="ts">
import Vue from 'vue'
import types from '@/config/smart-playlist/types'

export default Vue.extend({
  props: {
    type: {
      type: String,
      validator: value => Object.keys(types).includes(value)
    },

    value: {
      type: String,
      default: ''
    }
  },

  data: () => ({
    mutableValue: ''
  }),

  watch: {
    mutableValue (): void {
      this.$emit('input', this.mutableValue)
    }
  },

  created (): void {
    this.mutableValue = this.value
  }
})
</script>

<style lang="scss" scoped>
input {
  display: inline-block;
  width: 140px !important;
}
</style>
