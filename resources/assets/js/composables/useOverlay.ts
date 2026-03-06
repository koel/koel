import type { Ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { OverlayKey } from '@/config/symbols'
import type Overlay from '@/components/ui/Overlay.vue'

export const useOverlay = (overlay?: Ref<InstanceType<typeof Overlay>> | null) => {
  const resolved = overlay || requireInjection(OverlayKey)

  return {
    showOverlay: resolved.value!.show.bind(resolved.value!),
    hideOverlay: resolved.value!.hide.bind(resolved.value!),
  }
}
