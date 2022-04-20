import { defineAsyncComponent, reactive, ref } from 'vue'

export type ContextMenuContext = Record<string, any>

export const useContextMenu = () => {
  const BaseContextMenu = defineAsyncComponent(() => import('@/components/ui/context-menu.vue'))
  const base = ref<InstanceType<typeof BaseContextMenu>>()

  const context = reactive<ContextMenuContext>({})

  const open = (top: number, left: number, ctx: ContextMenuContext = {}) => {
    base.value?.open(top, left, ctx)
    Object.assign(context, ctx)
  }

  const close = () => base.value?.close()

  return {
    base,
    BaseContextMenu,
    open,
    close,
    context
  }
}
