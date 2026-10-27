<template>
  <FormRow>
    <div class="w-full flex gap-2 relative">
      <SelectBox v-model="selectedModel" name="model[]">
        <option v-for="m in models" :key="m.name" :value="m">{{ m.label }}</option>
      </SelectBox>

      <SelectBox v-model="selectedOperator" class="flex-1 max-w-44" name="operator[]">
        <option v-for="option in availableOperators" :key="option.operator" :value="option">{{ option.label }}</option>
      </SelectBox>

      <span class="inline-flex flex-1 items-center gap-3">
        <RuleInput
          v-for="(_, index) in inputCount"
          :key="index"
          v-model="values[index]"
          :type="inputType"
          class="flex-1!"
        />

        <span v-if="valueSuffix" class="suffix mr-5 text-sm">{{ valueSuffix }}</span>
      </span>

      <Btn
        size="small"
        variant="destructive"
        class="absolute right-[-14px] aspect-square top-1 scale-[60%] hover:scale-75 active:scale-[60%]"
        rounded
        data-testid="remove-rule-btn"
        @click.prevent="removeRule"
      >
        <Icon :icon="faMinus" />
        <span class="sr-only">Remove this rule</span>
      </Btn>
    </div>
  </FormRow>
</template>

<script lang="ts" setup>
import { faMinus } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, watch } from 'vue'
import models from '@/config/smart-playlist/models'
import inputTypes from '@/config/smart-playlist/inputTypes'

import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import Btn from '@/components/ui/form/Btn.vue'
import RuleInput from '@/components/playlist/smart-playlist/SmartPlaylistRuleInput.vue'

const props = defineProps<{ rule: SmartPlaylistRule }>()

const emit = defineEmits<{
  (e: 'input', rule: SmartPlaylistRule): void
  (e: 'remove'): void
}>()

const findModel = (name: SmartPlaylistModel['name']) => {
  const model = models.find(candidate => candidate.name === name)

  if (!model) {
    throw new Error(`Invalid smart playlist model: ${name}`)
  }

  return model
}

const selectedModel = ref<SmartPlaylistModel>(findModel(props.rule.model.name))
const availableOperators = computed<SmartPlaylistOperator[]>(() => inputTypes[selectedModel.value.type])

const selectedOperator = ref<SmartPlaylistOperator>(
  availableOperators.value.find(({ operator }) => operator === props.rule.operator) ?? availableOperators.value[0],
)

const values = ref<any[]>([...props.rule.value])

const inputCount = computed(() => selectedOperator.value.inputs || 1)
const inputType = computed(() => selectedOperator.value.type || selectedModel.value.type)
const valueSuffix = computed(() => selectedOperator.value.unit || selectedModel.value.unit)

watch(availableOperators, (operators, previousOperators) => {
  if (operators !== previousOperators) {
    selectedOperator.value = operators[0]
  }
})

watch([inputType, inputCount], ([type, count], [previousType, previousCount]) => {
  if (type !== previousType || count !== previousCount) {
    values.value = Array.from({ length: count }, () => '')
  }
})

watch(
  [selectedModel, selectedOperator, values],
  () =>
    emit('input', {
      id: props.rule.id,
      model: selectedModel.value,
      operator: selectedOperator.value.operator,
      value: values.value.slice(0, inputCount.value),
    }),
  { deep: true },
)

const removeRule = () => emit('remove')
</script>
