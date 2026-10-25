import { ref } from 'vue'

const isLyricsOverlayOpen = ref(false)

export const useLyricsOverlay = () => ({
  isLyricsOverlayOpen,
  openLyricsOverlay: () => (isLyricsOverlayOpen.value = true),
  closeLyricsOverlay: () => (isLyricsOverlayOpen.value = false),
})
