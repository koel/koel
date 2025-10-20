<template>
  <ul>
    <MenuItem @click="applyTheme">Apply Theme</MenuItem>

    <template v-if="theme.is_custom">
      <Separator />
      <MenuItem @click="destroy">Delete</MenuItem>
    </template>
  </ul>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { themeStore } from '@/stores/themeStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useDialogBox } from '@/composables/useDialogBox'
import { useContextMenu } from '@/composables/useContextMenu'

const props = defineProps<{ theme: Theme }>()
const { theme } = toRefs(props)

const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler()
const { showConfirmDialog } = useDialogBox()
const { Separator, MenuItem, trigger } = useContextMenu()

const applyTheme = () => trigger(() => themeStore.setTheme(theme.value))

const destroy = () => trigger(async () => {
  if (!await showConfirmDialog('Are you sure you want to delete this theme?')) {
    return
  }

  try {
    await themeStore.destroy(theme.value)
    toastSuccess('Theme deleted.')
  } catch (e: unknown) {
    handleHttpError(e)
  }
})
</script>
