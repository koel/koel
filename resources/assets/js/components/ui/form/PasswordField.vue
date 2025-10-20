<template>
  <div class="relative">
    <TextInput v-model="value" :type="type" class="w-full" data-testid="input" v-bind="$attrs" />
    <button
      class="absolute p-2.5 right-0 top-0 text-k-bg"
      data-testid="toggle"
      type="button"
      @click.prevent="toggleReveal"
    >
      <Icon v-if="type === 'password'" :icon="faEye" />
      <Icon v-else :icon="faEyeSlash" />
    </button>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { faEye, faEyeSlash } from '@fortawesome/free-regular-svg-icons'

import TextInput from '@/components/ui/form/TextInput.vue'

// we don't want the wrapping div to inherit the fallthrough attrs
defineOptions({ inheritAttrs: false })

const value = defineModel<string>()
const type = ref<'password' | 'text'>('password')

const toggleReveal = () => (type.value = type.value === 'password' ? 'text' : 'password')
</script>
