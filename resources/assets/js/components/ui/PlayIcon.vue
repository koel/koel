<template>
  <span
    class="play-icon absolute flex opacity-0 no-hover:opacity-100 items-center justify-center w-[32px] aspect-square rounded-full top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    aria-hidden="true"
  >
    <Icon v-if="playing" :icon="faPause" data-testid="icon-pause" class="text-k-highlight-fg" size="lg" />
    <Icon v-else :icon="faPlay" data-testid="icon-play" class="ml-0.5 text-k-highlight-fg" size="lg" />
  </span>
</template>

<script lang="ts" setup>
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'

withDefaults(defineProps<{ playing?: boolean }>(), { playing: false })
</script>
