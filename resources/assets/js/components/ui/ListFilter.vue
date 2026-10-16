<template>
  <OnClickOutside @trigger="maybeHideInput">
    <form class="flex border rounded-md border-k-fg-10 focus-within:border-k-highlight" @submit.prevent>
      <button v-koel-tooltip class="px-3.5 py-2" type="button" @click.prevent="showInput">
        <Icon :icon fixed-width />
        <span class="sr-only">{{ label }}</span>
      </button>
      <TextInput
        v-if="showingInput"
        ref="input"
        v-model="keywords"
        class="text-k-fg bg-transparent border-0 rounded-none pl-0! h-[unset]! placeholder:text-k-fg-50 outline-hidden"
        placeholder="Keywords"
        type="search"
        @blur="inputting = false"
        @focus="inputting = true"
      />
    </form>
  </OnClickOutside>
</template>

<script lang="ts" setup>
import { OnClickOutside } from '@vueuse/components'
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core'
import { faFilter } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { FilterKeywordsKey } from '@/config/symbols'

import TextInput from '@/components/ui/form/TextInput.vue'

const { label = 'Filter', icon = faFilter } = defineProps<{ label?: string; icon?: IconDefinition }>()

const input = ref<InstanceType<typeof TextInput>>()
const inputting = ref(false)

const keywords = requireInjection(FilterKeywordsKey, ref(''))

// We show the input if the user is currently typing in it, or if there are any keywords entered
const showingInput = computed(() => inputting.value || keywords.value.trim())

const maybeHideInput = () => {
  inputting.value = false
}

const showInput = () => {
  inputting.value = true

  nextTick(() => {
    input.value?.el?.focus()
    input.value?.el?.select()
  })
}
</script>
