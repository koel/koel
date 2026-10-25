<template>
  <Transition name="lyrics-overlay">
    <section
      v-if="isLyricsOverlayOpen && songWithLyrics"
      ref="overlay"
      aria-label="Lyrics"
      class="lyrics-overlay fixed inset-0 z-10 flex flex-col overflow-hidden bg-k-bg text-k-fg"
      tabindex="-1"
    >
      <img :src="cover" alt="" class="absolute inset-0 w-full h-full object-cover scale-125 blur-3xl opacity-70" />
      <div class="absolute inset-0 bg-k-bg-60" />

      <div class="lyrics-area relative flex-1 min-h-0 w-full mb-(--overlay-footer-space) text-2xl text-center">
        <LyricsPane :song="songWithLyrics" collapsible />
      </div>
    </section>
  </Transition>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, watch } from 'vue'
import { onKeyStroke } from '@vueuse/core'
import { useBranding } from '@/composables/useBranding'
import { useLyricsOverlay } from '@/composables/useLyricsOverlay'
import { isSong } from '@/utils/typeGuards'
import { CurrentStreamableKey } from '@/config/symbols'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'

const LyricsPane = defineAsyncComponent(() => import('@/components/ui/lyrics/LyricsPane.vue'))

const { isLyricsOverlayOpen, closeLyricsOverlay } = useLyricsOverlay()
const { cover: defaultCover } = useBranding()

const streamable = requireInjection(CurrentStreamableKey, ref(undefined))
const overlay = ref<HTMLElement>()

const songWithLyrics = computed(() => {
  const current = streamable.value

  return current && isSong(current) && current.lyrics?.trim() ? current : null
})

const cover = computed(() => songWithLyrics.value?.album_cover || defaultCover)

watch(songWithLyrics, value => value || closeLyricsOverlay())

onKeyStroke('Escape', event => (event.target as HTMLElement).closest('dialog') || closeLyricsOverlay())

watch(isLyricsOverlayOpen, async open => {
  if (open) {
    await nextTick()
    overlay.value?.focus()
  }
})
</script>

<style lang="postcss" scoped>
.lyrics-overlay {
  --overlay-footer-space: calc(var(--footer-height, 84px) + 2rem);

  outline: none;
}

.lyrics-overlay-enter-active,
.lyrics-overlay-leave-active {
  transition: opacity 0.25s ease;
}

.lyrics-overlay-enter-from,
.lyrics-overlay-leave-to {
  opacity: 0;
}
</style>
