<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>AI Assistant</template>

      <div class="space-y-4">
        <p v-if="usesServerConfiguration" data-testid="server-configuration-notice">
          The assistant is currently set up in the server's configuration. Saving here replaces that setup.
        </p>
        <FormRow>
          <span>
            <CheckBox v-model="data.enabled" name="enabled" />
            <span class="ml-2">Turn on the AI assistant</span>
          </span>
        </FormRow>
        <FormRow>
          <template #label>Provider</template>
          <SelectBox v-model="data.provider" class="md:w-2/3" name="provider" required>
            <option v-for="(label, provider) in PROVIDERS" :key="provider" :value="provider">{{ label }}</option>
          </SelectBox>
        </FormRow>
        <FormRow>
          <template #label>API key</template>
          <template #help>
            <span v-if="canKeepApiKey">A key is saved. Leave this empty to keep it.</span>
            <span v-else>Your provider bills the assistant's use to this key.</span>
          </template>
          <PasswordField
            v-model="data.api_key"
            :required="data.enabled && !canKeepApiKey"
            autocomplete="off"
            class="md:w-2/3"
            name="api_key"
          />
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
}

const isOfferedProvider = (provider: string | null | undefined): provider is AiProvider =>
  Boolean(provider && provider in PROVIDERS)

const { showConfirmDialog } = useDialogBox()

const current = computed(() => settingStore.state.ai)

const { data, loading, handleSubmit } = useForm<{ enabled: boolean; provider: AiProvider; api_key: string }>({
  initialValues: {
    enabled: current.value?.enabled ?? false,
    provider: isOfferedProvider(current.value?.provider) ? current.value.provider : 'openai',
    api_key: '',
  },
  onSubmit: async ({ enabled, provider, api_key }) =>
    await settingStore.updateAi({ enabled, provider, ...(api_key ? { api_key } : {}) }),
  onSuccess: async () => {
    data.api_key = ''

    if (await showConfirmDialog('Settings saved. Reload to apply the changes?')) {
      forceReloadWindow()
    }
  },
})

const canKeepApiKey = computed(
  () =>
    current.value?.source === 'organization' && current.value.has_api_key && data.provider === current.value.provider,
)

const usesServerConfiguration = computed(
  () => current.value?.source === 'environment' && current.value.enabled && Boolean(current.value.provider),
)
</script>
