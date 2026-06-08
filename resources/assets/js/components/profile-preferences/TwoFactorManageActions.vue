<template>
  <div class="space-y-4">
    <p class="text-k-success">Two-factor authentication is active on your account.</p>

    <form v-if="action" class="space-y-3 max-w-md" data-testid="two-factor-manage-form" @submit.prevent="handleSubmit">
      <FormRow>
        <template #label>
          Enter a code from your authenticator app or a recovery code to
          {{ action === 'disable' ? 'disable' : 'regenerate recovery codes' }}.
        </template>
        <TextInput v-model="data.code" v-koel-focus autocomplete="one-time-code" placeholder="123 456" required />
      </FormRow>
      <div class="flex gap-2">
        <Btn :disabled="submitting" type="submit">Submit</Btn>
        <Btn type="button" variant="ghost" @click.prevent="cancel">Cancel</Btn>
      </div>
    </form>

    <div v-else class="flex gap-2">
      <Btn type="button" @click.prevent="action = 'regenerate'">Regenerate Recovery Codes</Btn>
      <Btn type="button" variant="destructive" @click.prevent="action = 'disable'">Disable</Btn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

type Action = 'regenerate' | 'disable'

defineProps<{ submitting?: boolean }>()
const emit = defineEmits<{ (e: 'regenerate', code: string): void; (e: 'disable', code: string): void }>()

const action = ref<Action | null>(null)

const { data, handleSubmit } = useForm<{ code: string }>({
  initialValues: { code: '' },
  useOverlay: false,
  onSubmit: form => {
    if (action.value) {
      emit(action.value, form.code)
    }
  },
})

const cancel = () => {
  action.value = null
  data.code = ''
}

defineExpose({ reset: cancel })
</script>
