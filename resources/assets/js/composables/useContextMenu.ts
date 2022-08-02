import { reactive, ref } from 'vue'
import ContextMenuBase from '@/components/ui/ContextMenuBase.vue'

export type ContextMenuContext = Record<string, any>

export const useContextMenu = () => {
  const base = ref<InstanceType<typeof ContextMenuBase>>()

  const context = reactive<ContextMenuContext>({})

  const open = async (top: number, left: number, ctx: ContextMenuContext = {}) => {
    Object.assign(context, ctx)
    await base.value?.open(top, left, ctx)
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
