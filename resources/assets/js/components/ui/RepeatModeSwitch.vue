<template>
  <button
    class="control"
    :class="mode"
    @click.prevent="changeRepeatMode"
    :title="`Change repeat mode (current mode: ${readableRepeatMode})`"
    data-testid="repeat-mode-switch"
  >
    <i class="fa fa-repeat"></i>
  </button>
</template>

<script lang="ts" setup>
import { computed, reactive, toRef } from 'vue'
import { playback } from '@/services'
import { preferenceStore } from '@/stores'

const mode = toRef(preferenceStore.state, 'repeatMode')

const readableRepeatMode = computed(() => mode.value
  .split('_')
  .map(part => part[0].toUpperCase() + part.substring(1).toLowerCase())
  .join(' ')
)

const changeRepeatMode = () => playback.changeRepeatMode()
</script>

<style lang="scss" scoped>
button {
  position: relative;

  &.REPEAT_ALL, &.REPEAT_ONE {
    color: var(--color-highlight);
  }

  &.REPEAT_ONE::after {
    content: "1";
    position: absolute;
    display: flex;
    place-content: center;
    place-items: center;
    top: 0;
    left: 0;
    font-weight: 700;
    font-size: .5rem;
    width: 100%;
    height: 100%;
  }
}
</style>
