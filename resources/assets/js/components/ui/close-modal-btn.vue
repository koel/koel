<template>
  <button @click.prevent="$emit('click')" data-test="close-modal-btn">
    <i class="fa fa-times"></i>
  </button>
</template>

<script lang="ts">
import Vue from 'vue'

export default Vue.extend({})
</script>

<style lang="scss" scoped>
button {
  display: flex;
  place-content: center;
  place-items: center;
  position: absolute;
  right: 0;
  top: 0;
  background: var(--color-red);
  width: 24px;
  height: 24px;
  border-radius: 0;
  padding: 0;
  color: var(--color-text-primary);
  transition: opacity .3s;

  @media (hover: none) {
    opacity: 1;
  }
}
</style>
