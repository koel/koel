<template>
  <div class="space-y-4">
    <AlertBox>
      Save these recovery codes somewhere safe. Each can be used once if you lose access to your authenticator app — you
      won't be able to see them again.
    </AlertBox>

    <ul
      class="font-mono text-sm rounded-md border border-k-fg-10 bg-k-bg-50 p-4 grid grid-cols-2 gap-2"
      data-testid="recovery-codes"
    >
      <li v-for="code in codes" :key="code">{{ code }}</li>
    </ul>

    <div class="flex gap-2">
      <Btn type="button" @click.prevent="copy"> <CopyIcon :size="16" /> Copy </Btn>
      <Btn type="button" variant="outline" @click.prevent="$emit('dismiss')">I've saved them</Btn>
    </div>
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
