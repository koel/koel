<template>
  <span class="controls-toggler text-orange" v-if="isMobile.phone" @click="$emit('toggleControls')">
    <i class="fa fa-angle-up toggler" v-if="showingControls"/>
    <i class="fa fa-angle-down toggler" v-else/>
  </span>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ showingControls: boolean }>(), { showingControls: true })
const { showingControls } = toRefs(props)
</script>

<style lang="scss" scoped>
.toggler {
  font-size: 1rem;
  margin-left: 4px;
}
</style>
