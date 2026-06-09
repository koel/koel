<template>
  <div class="space-y-4">
    <AlertBox> Save these recovery codes somewhere safe. You won't see them again. </AlertBox>

    <div class="group relative max-w-md">
      <ul
        class="font-mono text-sm rounded-md border border-k-fg-10 bg-k-bg-50 p-4 space-y-1 select-all"
        data-testid="recovery-codes"
      >
        <li v-for="code in codes" :key="code">{{ code }}</li>
      </ul>

      <button
        type="button"
        aria-label="Copy recovery codes"
        class="absolute top-2 right-2 p-2 rounded-md hover:bg-k-fg-10 cursor-pointer opacity-0 transition-opacity group-hover:opacity-100 focus:opacity-100"
        @click.prevent="copy"
      >
        <CopyIcon :size="16" />
      </button>
    </div>

    <Btn type="button" variant="success" @click.prevent="$emit('dismiss')">I've saved them</Btn>
  </div>
</template>

<script lang="ts" setup>
import { CopyIcon } from 'lucide-vue-next'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { copyText } from '@/utils/helpers'

import AlertBox from '@/components/ui/AlertBox.vue'
import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ codes: string[] }>()
defineEmits<{ (e: 'dismiss'): void }>()

const { toastSuccess } = useMessageToaster()

const copy = async () => {
  await copyText(props.codes.join('\n'))
  toastSuccess('Recovery codes copied.')
}
</script>
