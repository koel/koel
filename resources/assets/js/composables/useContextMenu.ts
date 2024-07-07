import { ref } from 'vue'
import ContextMenu from '@/components/ui/ContextMenu.vue'

export const useContextMenu = () => {
  const base = ref<InstanceType<typeof ContextMenu>>()

  const open = async (top: number, left: number) => await base.value?.open(top, left)
  const close = () => base.value?.close()

  const trigger = (func: Closure) => {
    close()
    func()
  }

  return {
    ContextMenu,
    base,
    open,
    close,
    trigger
  }
}
