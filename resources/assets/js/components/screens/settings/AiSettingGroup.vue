<template>
  <SettingGroup class="md:w-2/3">
    <div class="flex flex-col gap-4">
      <FormRow>
        <label class="cursor-pointer">
          <CheckBox :model-value="enabled" name="enabled" @update:model-value="toggle" />
          <span class="ml-2">Use AI assistant</span>
        </label>
      </FormRow>

      <form v-if="enabled" class="space-y-4" data-testid="ai-configuration" @submit.prevent="handleSubmit">
        <FormRow>
          <template #label>Provider</template>
          <SelectBox v-model="data.provider" name="provider" required>
            <option disabled value="">Choose a provider</option>
            <option v-for="(label, provider) in PROVIDERS" :key="provider" :value="provider">{{ label }}</option>
          </SelectBox>
        </FormRow>
        <FormRow>
          <template #label>API key</template>
          <div>
            <PasswordField
              v-model="data.api_key"
              :required="!canKeepApiKey"
              :placeholder="canKeepApiKey ? 'Enter a new API key' : ''"
              autocomplete="off"
              name="api_key"
            />
          </div>
        </FormRow>
        <Btn :disabled="loading" type="submit">Save</Btn>
      </form>
    </div>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useForm } from '@/composables/useForm'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { settingStore } from '@/stores/settingStore'

import Btn from '@/components/ui/form/Btn.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const PROVIDERS: Record<AiProvider, string> = {
  openai: 'OpenAI',
  anthropic: 'Anthropic',
  gemini: 'Google Gemini',
  deepseek: 'DeepSeek',
  groq: 'Groq',
  mistral: 'Mistral',
  openrouter: 'OpenRouter',
  xai: 'xAI',
}

const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler('dialog')

const current = computed(() => settingStore.state.ai)
const enabled = ref(Boolean(current.value?.enabled))

const { data, loading, handleSubmit } = useForm<{ provider: AiProvider | ''; api_key: string }>({
  initialValues: {
    provider: current.value?.provider ?? '',
    api_key: '',
  },
  onSubmit: async ({ provider, api_key }) =>
    await settingStore.updateAi({ enabled: true, provider: provider as AiProvider, ...(api_key ? { api_key } : {}) }),
  onSuccess: () => {
    data.api_key = ''
    toastSuccess('AI assistant saved.')
  },
})

const canKeepApiKey = computed(() => Boolean(current.value?.has_api_key) && data.provider === current.value?.provider)
const saveEnabled = async (on: boolean, provider: AiProvider) => {
  try {
    await settingStore.updateAi({ enabled: on, provider })
    toastSuccess(on ? 'AI assistant turned on.' : 'AI assistant turned off.')
  } catch (error: unknown) {
    enabled.value = !on
    handleHttpError(error)
  }
}

const toggle = async (value: boolean | undefined) => {
  const on = Boolean(value)
  enabled.value = on

  const storedProvider = current.value?.provider
  const savesRightAway = on ? current.value?.has_api_key : current.value?.enabled

  if (storedProvider && savesRightAway) {
    await saveEnabled(on, storedProvider)
  }
}
</script>
