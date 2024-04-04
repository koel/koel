<template>
  <form v-koel-clickaway="maybeClose" @submit.prevent>
    <Btn v-koel-tooltip title="Filter" unrounded transparent @click.prevent="toggleInput">
      <Icon :icon="faFilter" fixed-width />
    </Btn>
    <input
      v-show="showingInput"
      ref="input"
      v-model="keywords"
      type="search"
      placeholder="Keywords"
      class="text-secondary"
    >
  </form>
</template>

<script lang="ts" setup>
import { faFilter } from '@fortawesome/free-solid-svg-icons'
import { nextTick, ref, watch } from 'vue'

import Btn from '@/components/ui/Btn.vue'

const emit = defineEmits<{ (event: 'change', value: string): void }>()

const showingInput = ref(false)
const input = ref<HTMLInputElement>()
const keywords = ref('')

watch(keywords, value => emit('change', value))

const toggleInput = () => {
  showingInput.value = !showingInput.value

  if (showingInput.value) {
    nextTick(() => {
      input.value?.focus()
      input.value?.select()
    })
  } else {
    input.value?.blur()
    keywords.value = ''
  }
}

const maybeClose = () => {
  if (keywords.value.trim() !== '') return

  showingInput.value = false
  input.value?.blur()
  keywords.value = ''
}
</script>

<style lang="postcss" scoped>
form {
  display: flex;
  border: 1px solid rgba(255, 255, 255, .1);
  border-radius: 4px;
  overflow: hidden;

  input {
    background-color: transparent;
    border-radius: 0;
    padding-left: 0;
    height: unset;

    &::placeholder {
      color: rgba(255, 255, 255, .5);
    }
  }

  &:focus-within {
    background: rgba(0, 0, 0, .1);
    border-color: rgba(255, 255, 255, .4);
  }
}
</style>
