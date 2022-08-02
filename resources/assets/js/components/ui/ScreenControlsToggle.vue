<template>
  <span v-if="isMobile.phone" class="text-highlight" data-testid="controls-toggle" @click="$emit('toggleControls')">
    <icon v-if="showingControls" :icon="showingControls ? faAngleUp : faAngleDown" class="toggle"/>
  </span>
</template>

<script lang="ts" setup>
import { faAngleDown, faAngleUp } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ showingControls?: boolean }>(), { showingControls: true })
const { showingControls } = toRefs(props)
</script>

<style lang="scss" scoped>
.toggle {
  font-size: 1rem;
  margin-left: 4px;
}
</style>
