import { defineAsyncComponent, ref } from 'vue'

export const useContextMenu = () => {
  const BaseContextMenu = defineAsyncComponent(() => import('@/components/ui/context-menu.vue'))
  const base = ref<InstanceType<typeof BaseContextMenu>>()

  const open = (top: number, left: number) => base.value?.open(top, left)
  const close = () => base.value?.close()

  return {
    base,
    BaseContextMenu,
    open,
    close
  }
}
