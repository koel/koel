import type { Ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { OverlayKey } from '@/symbols'
import type Overlay from '@/components/ui/Overlay.vue'

export const useOverlay = (overlay?: Ref<InstanceType<typeof Overlay>> | null) => {
  overlay = overlay || requireInjection(OverlayKey)

  return {
    showOverlay: overlay.value.show.bind(overlay.value),
    hideOverlay: overlay.value.hide.bind(overlay.value),
  }
}
