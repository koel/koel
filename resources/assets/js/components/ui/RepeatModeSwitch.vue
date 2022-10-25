<template>
  <button
    v-koel-tooltip.top
    :class="{ active: mode !== 'NO_REPEAT' }"
    :title="`Change repeat mode (current: ${readableMode})`"
    data-testid="repeat-mode-switch"
    type="button"
    @click.prevent="changeMode"
  >
    <FontAwesomeLayers>
      <icon :icon="faRepeat"/>
      <FontAwesomeLayersText v-if="mode === 'REPEAT_ONE'" counter value="1"/>
    </FontAwesomeLayers>
  </button>
</template>

<script lang="ts" setup>
import { FontAwesomeLayers, FontAwesomeLayersText } from '@fortawesome/vue-fontawesome'
import { faRepeat } from '@fortawesome/free-solid-svg-icons'
import { computed, toRef } from 'vue'
import { playbackService } from '@/services'
import { preferenceStore } from '@/stores'

const mode = toRef(preferenceStore.state, 'repeatMode')

const readableMode = computed(() => mode.value
  .split('_')
  .map(part => part[0].toUpperCase() + part.substring(1).toLowerCase())
  .join(' ')
)

const changeMode = () => playbackService.changeRepeatMode()
</script>

<style lang="scss" scoped>
.fa-layers-counter {
  transform: none;
  font-size: .45rem;
  font-weight: bold;
  right: 2px;
  top: 2px;
  color: currentColor;
  background: transparent;
}

button {
  opacity: .3;
}

.active {
  opacity: 1;
}
</style>
