import { reactive, ref } from 'vue'
import ContextMenuBase from '@/components/ui/ContextMenuBase.vue'

export type ContextMenuContext = Record<string, any>

export const useContextMenu = () => {
  const base = ref<InstanceType<typeof ContextMenuBase>>()

  const context = reactive<ContextMenuContext>({})

  const open = (top: number, left: number, ctx: ContextMenuContext = {}) => {
    base.value?.open(top, left, ctx)
    Object.assign(context, ctx)
  }

  const close = () => base.value?.close()

  const trigger = (func: Closure) => {
    close()
    func()
  }

  return {
    ContextMenuBase,
    base,
    context,
    open,
    close,
    trigger
  }
}
