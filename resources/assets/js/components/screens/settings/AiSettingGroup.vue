<template>
  <form class="md:w-2/3" @submit.prevent="handleSubmit">
    <SettingGroup>
      <div class="space-y-4">
        <FormRow>
          <span>
            <CheckBox v-model="data.enabled" name="enabled" />
            <span class="ml-2">Use AI assistant</span>
          </span>
        </FormRow>
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
              :required="data.enabled && !canKeepApiKey"
              :placeholder="canKeepApiKey ? 'Enter a new API key' : ''"
              autocomplete="off"
              name="api_key"
            />
          </div>
        </FormRow>
      </div>

      <template #footer>
        <Btn :disabled="loading" type="submit">Save</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useForm } from '@/composables/useForm'
import { useDialogBox } from '@/composables/useDialogBox'
import { settingStore } from '@/stores/settingStore'
import { forceReloadWindow } from '@/utils/helpers'

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

const { showConfirmDialog } = useDialogBox()

const current = computed(() => settingStore.state.ai)

const { data, loading, handleSubmit } = useForm<{ enabled: boolean; provider: AiProvider | ''; api_key: string }>({
  initialValues: {
    enabled: Boolean(current.value?.enabled),
    provider: current.value?.provider ?? '',
    api_key: '',
  },
  onSubmit: async ({ enabled, provider, api_key }) =>
    await settingStore.updateAi({ enabled, provider: provider as AiProvider, ...(api_key ? { api_key } : {}) }),
  onSuccess: async () => {
    data.api_key = ''

    if (await showConfirmDialog('Settings saved. Reload to apply the changes?')) {
      forceReloadWindow()
    }
  },
})

const canKeepApiKey = computed(() => Boolean(current.value?.has_api_key) && data.provider === current.value?.provider)
</script>
