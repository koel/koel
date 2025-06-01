<template>
  <OnClickOutside @trigger="maybeHideInput">
    <form
      class="flex border rounded-md overflow-hidden border-solid border-white/10 focus-within:bg-black/10 focus-within:border-white/40"
      @submit.prevent
    >
      <Btn v-koel-tooltip title="Filter" transparent unrounded @click.prevent="showInput">
        <Icon :icon="faFilter" fixed-width />
      </Btn>
      <TextInput
        v-if="showingInput"
        ref="input"
        v-model="keywords"
        class="!text-k-text-primary !bg-transparent !rounded-none !pl-0 !h-[unset] placeholder:text-white/50 focus-visible:outline-0"
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
import { faFilter } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { FilterKeywordsKey } from '@/symbols'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

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
