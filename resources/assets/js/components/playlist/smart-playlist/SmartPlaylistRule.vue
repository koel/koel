<template>
  <div class="row" data-test="smart-playlist-rule-row">
    <Btn class="remove-rule" red @click.prevent="removeRule"><i class="fa fa-times"></i></Btn>

    <select v-model="selectedModel" name="model[]">
      <option v-for="model in models" :key="model.name" :value="model">{{ model.label }}</option>
    </select>

    <select v-model="selectedOperator" name="operator[]">
      <option v-for="option in availableOperators" :key="option.operator" :value="option">{{ option.label }}</option>
    </select>

    <span class="value-wrapper">
      <RuleInput
        v-for="input in availableInputs"
        :key="input.id"
        v-model="input.value"
        :type="selectedOperator?.type || selectedModel?.type"
        :value="input.value"
        @update:modelValue="onInput"
      />

      <span v-if="valueSuffix" class="suffix">{{ valueSuffix }}</span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import models from '@/config/smart-playlist/models'
import inputTypes from '@/config/smart-playlist/inputTypes'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const RuleInput = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistRuleInput.vue'))

const props = defineProps<{ rule: SmartPlaylistRule }>()
const { rule } = toRefs(props)

const mutatedRule = Object.assign({}, rule.value)

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

const emit = defineEmits(['input', 'remove'])

const onInput = () => {
  emit('input', {
    id: mutatedRule.id,
    model: selectedModel.value,
    operator: selectedOperator.value?.operator,
    value: availableInputs.value.map(input => input.value)
  } as SmartPlaylistRule)
}

const removeRule = () => emit('remove')
</script>

<style lang="scss" scoped>
.row {
  display: flex;

  > * + * {
    margin-left: .5rem;
  }
}

.suffix {
  margin-left: .3rem;
}

button.remove-rule i {
  margin-right: 0;
}

.value-wrapper {
  flex: 1;
  display: inline-flex;
  place-items: center;
  column-gap: .5rem;

  input {
    flex: 1;
  }
}
</style>
