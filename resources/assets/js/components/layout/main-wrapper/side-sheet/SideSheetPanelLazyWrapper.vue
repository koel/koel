<template>
  <div v-if="shouldMount" v-show="active" role="tabpanel" tabindex="0">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ active?: boolean, shouldMount: boolean }>(), {
  active: false,
  shouldMount: false,
})

const { active, shouldMount } = toRefs(props)
</script>

<style scoped lang="postcss">

</style>
