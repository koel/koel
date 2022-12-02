import { reactive, ref } from 'vue'
import ContextMenuBase from '@/components/ui/ContextMenuBase.vue'

export const useContextMenu = () => {
  const base = ref<InstanceType<typeof ContextMenuBase>>()

  const open = async (top: number, left: number) => await base.value?.open(top, left)
  const close = () => base.value?.close()

  const trigger = (func: Closure) => {
    close()
    func()
  }

  return {
    ContextMenuBase,
    base,
    open,
    close,
    trigger
  }
}
