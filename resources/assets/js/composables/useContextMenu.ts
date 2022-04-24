import { defineAsyncComponent, reactive, ref } from 'vue'

export type ContextMenuContext = Record<string, any>

export const useContextMenu = () => {
  const ContextMenuBase = defineAsyncComponent(() => import('@/components/ui/ContextMenuBase.vue'))
  const base = ref<InstanceType<typeof ContextMenuBase>>()

  const context = reactive<ContextMenuContext>({})

  const open = (top: number, left: number, ctx: ContextMenuContext = {}) => {
    base.value?.open(top, left, ctx)
    Object.assign(context, ctx)
  }

  const close = () => base.value?.close()

  return {
    base,
    ContextMenuBase,
    open,
    close,
    context
  }
}
