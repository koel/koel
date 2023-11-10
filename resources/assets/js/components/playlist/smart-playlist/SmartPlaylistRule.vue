<template>
  <div class="row" data-testid="smart-playlist-rule-row">
    <Btn class="remove-rule" red title="Remove this rule" @click.prevent="removeRule">
      <Icon :icon="faTrashCan" />
    </Btn>

    <select v-model="selectedModel" name="model[]">
      <option v-for="m in models" :key="m.name" :value="m">{{ m.label }}</option>
    </select>

    <select v-model="selectedOperator" name="operator[]">
      <option v-for="option in availableOperators" :key="option.operator" :value="option">{{ option.label }}</option>
    </select>

    <span class="value-wrapper">
      <RuleInput
        v-for="input in availableInputs"
        :key="input.id"
        v-model="input.value"
        :type="(selectedOperator?.type || selectedModel?.type)!"
        :value="input.value"
        @update:model-value="onInput"
      />

      <span v-if="valueSuffix" class="suffix">{{ valueSuffix }}</span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import models from '@/config/smart-playlist/models'
import inputTypes from '@/config/smart-playlist/inputTypes'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const RuleInput = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistRuleInput.vue'))

const props = defineProps<{ rule: SmartPlaylistRule }>()
const { rule } = toRefs(props)

const mutatedRule = Object.assign({}, rule.value) as SmartPlaylistRule

const selectedModel = ref<SmartPlaylistModel>()
const selectedOperator = ref<SmartPlaylistOperator>()

const model = models.find(m => m.name === mutatedRule.model.name)

if (!model) {
  throw new Error(`Invalid smart playlist model: ${mutatedRule.model.name}`)
}

mutatedRule.model = selectedModel.value = model

const availableOperators = computed<SmartPlaylistOperator[]>(() => {
  return selectedModel.value ? inputTypes[selectedModel.value.type] : []
})

const operator = availableOperators.value.find(o => o.operator === mutatedRule.operator)

if (!operator) {
  throw new Error(`Invalid smart playlist operator: ${mutatedRule.operator}`)
}

selectedOperator.value = operator

const isOriginalOperatorSelected = computed(() => {
  return selectedModel.value?.name === mutatedRule.model.name &&
    selectedOperator.value?.operator === mutatedRule.operator
})

const availableInputs = computed<{ id: string, value: any }[]>(() => {
  if (!selectedOperator.value) {
    return []
  }

  const inputs: Array<{ id: string, value: string }> = []

  for (let i = 0, inputCount = selectedOperator.value.inputs || 1; i < inputCount; ++i) {
    inputs.push({
      id: `${mutatedRule.model.name}_${selectedOperator.value.operator}_${i}`,
      value: isOriginalOperatorSelected.value ? mutatedRule.value[i] : ''
    })
  }

  return inputs
})

watch(availableOperators, () => {
  if (selectedModel.value?.name === mutatedRule.model.name) {
    selectedOperator.value = availableOperators.value.find(o => o.operator === mutatedRule.operator)!
  } else {
    selectedOperator.value = availableOperators.value[0]
  }
})

const valueSuffix = computed(() => selectedOperator.value?.unit || selectedModel.value?.unit)

const emit = defineEmits<{
  (e: 'input', rule: SmartPlaylistRule): void,
  (e: 'remove'): void
}>()

const onInput = () => {
  emit('input', {
    id: mutatedRule.id,
    model: selectedModel.value!,
    operator: selectedOperator.value?.operator!,
    value: availableInputs.value.map(input => input.value)
  })
}

const removeRule = () => emit('remove')
</script>

<style lang="scss" scoped>
.row {
  display: flex;
  gap: .5rem;
}

.value-wrapper {
  flex: 1;
  display: inline-flex;
  place-items: center;
  gap: .5rem;

  input {
    flex: 1;
  }
}

select, input {
  margin-top: 0 !important;
}
</style>
