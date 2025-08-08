<template>
  <div
    :class="{ playing: station?.playback_state === 'Playing' }"
    class="station-info px-6 py-0 flex items-center content-start w-[84px] md:w-[420px] gap-5"
  >
    <span class="logo block h-[55%] md:h-3/4 aspect-square rounded-full bg-cover" />
    <div v-if="station" class="meta overflow-hidden hidden md:block">
      <h3 class="title text-ellipsis overflow-hidden whitespace-nowrap">{{ station.name }}</h3>
      <p class="text-k-text-secondary text-ellipsis overflow-hidden whitespace-nowrap">{{ station.description }}</p>
    </div>
  </div>
</template>

<script lang="ts" setup>
import type { Ref } from 'vue'
import { computed, ref } from 'vue'
import defaultCover from '@/../img/covers/default.svg'
import { requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/symbols'

const station = requireInjection<Ref<RadioStation | undefined>>(CurrentStreamableKey, ref())

const cover = computed(() => station.value ? station.value.logo : defaultCover)
const coverBackgroundImage = computed(() => `url(${cover.value ?? defaultCover})`)
</script>

<style lang="postcss" scoped>
.station-info {
  :fullscreen & {
    @apply pl-0;
  }

  .logo {
    background-image: v-bind(coverBackgroundImage);

    :fullscreen & {
      @apply h-20;
    }
  }

  .meta {
    :fullscreen & {
      @apply -mt-72 origin-bottom-left absolute overflow-hidden;

      .title {
        @apply text-5xl mb-[0.4rem] font-bold;
      }
    }
  }

  &.playing .logo {
    @apply motion-reduce:animate-none;
    animation: spin 30s linear infinite;
  }
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}
</style>
