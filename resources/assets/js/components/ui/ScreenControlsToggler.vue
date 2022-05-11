<template>
  <span v-if="isMobile.phone" class="text-orange" data-testid="controls-toggler" @click="$emit('toggleControls')">
    <i v-if="showingControls" class="fa fa-angle-up toggler"/>
    <i v-else class="fa fa-angle-down toggler"/>
  </span>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ showingControls?: boolean }>(), { showingControls: true })
const { showingControls } = toRefs(props)
</script>

<style lang="scss" scoped>
.toggler {
  font-size: 1rem;
  margin-left: 4px;
}
</style>
