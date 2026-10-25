<template>
  <Transition name="lyrics-overlay">
    <section
      v-if="isLyricsOverlayOpen && song"
      ref="overlay"
      aria-label="Lyrics"
      class="lyrics-overlay fixed inset-0 z-10 flex flex-col overflow-hidden bg-black"
      data-testid="lyrics-overlay"
      tabindex="-1"
      @keydown.esc="closeLyricsOverlay"
    >
      <img :src="cover" alt="" class="absolute inset-0 w-full h-full object-cover scale-125 blur-3xl opacity-70" />
      <div class="absolute inset-0 bg-black/55" />

      <div class="lyrics-area relative flex-1 min-h-0 w-full mb-(--overlay-footer-space) text-2xl text-center">
        <LyricsPane :song collapsible />
      </div>
    </section>
  </Transition>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, watch } from 'vue'
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

const song = computed(() => (streamable.value && isSong(streamable.value) ? streamable.value : null))
const cover = computed(() => song.value?.album_cover || defaultCover)

watch(song, value => value || closeLyricsOverlay())

watch(isLyricsOverlayOpen, async open => {
  if (open) {
    await nextTick()
    overlay.value?.focus()
  }
})
</script>

<style lang="postcss" scoped>
.lyrics-overlay {
  --color-fg: #ffffff;
  --overlay-footer-space: calc(var(--footer-height, 84px) + 2rem);

  color: #ffffff;
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
