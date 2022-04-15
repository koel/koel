<template>
  <div class="row" data-test="smart-playlist-rule-row">
    <btn @click.prevent="removeRule" class="remove-rule" red><i class="fa fa-times"></i></btn>

    <select v-model="selectedModel" name="model[]">
      <option v-for="model in models" :key="model.name" :value="model">{{ model.label }}</option>
    </select>

    <select v-model="selectedOperator" name="operator[]">
      <option v-for="option in options" :value="option" :key="option.operator">{{ option.label }}</option>
    </select>

    <span class="value-wrapper">
      <rule-input
        v-for="input in availableInputs"
        :key="input.id"
        :type="selectedOperator.type || selectedModel.type"
        v-model="input.value"
        @input="onInput"
      />

      <span class="suffix" v-if="valueSuffix">{{ valueSuffix }}</span>
    </span>
  </div>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import models from '@/config/smart-playlist/models'
import types from '@/config/smart-playlist/types'

export default Vue.extend({
  name: 'SmartPlaylistRule',

  components: {
    Btn: () => import('@/components/ui/btn.vue'),
    RuleInput: () => import('@/components/playlist/smart-playlist/rule-input.vue')
  },

  props: {
    rule: {
      type: Object,
      required: true
    } as PropOptions<SmartPlaylistRule>
  },

  data: () => ({
    models,
    selectedModel: null as unknown as SmartPlaylistModel,
    selectedOperator: null as unknown as SmartPlaylistOperator,
    inputValues: [],
    mutatedRule: null as unknown as SmartPlaylistRule
  }),

  watch: {
    options (): void {
      if (this.selectedModel.name === this.mutatedRule.model.name) {
        this.selectedOperator = this.options.find(o => o.operator === this.mutatedRule.operator)!
      } else {
        this.selectedOperator = this.options[0]
      }
    }
  },

  computed: {
    options (): SmartPlaylistOperator[] {
      return this.selectedModel ? types[this.selectedModel.type] : []
    },

    availableInputs (): { id: string, value: any }[] {
      if (!this.selectedOperator) {
        return []
      }

      const inputs: Array<{ id: string, value: string }> = []

      for (let i = 0, inputCount = this.selectedOperator.inputs || 1; i < inputCount; ++i) {
        inputs.push({
          id: `${this.mutatedRule.model}_${this.selectedOperator.operator}_${i}`,
          value: this.isOriginalOperatorSelected ? this.mutatedRule.value[i] : ''
        })
      }

      return inputs
    },

    isOriginalOperatorSelected (): boolean {
      return this.selectedModel.name === this.mutatedRule.model.name &&
        this.selectedOperator.operator === this.mutatedRule.operator
    },

    valueSuffix (): string | undefined {
      return this.selectedOperator.unit || this.selectedModel.unit
    }
  },

  created (): void {
    this.mutatedRule = Object.assign({}, this.rule)

    const model = this.models.find((m: SmartPlaylistModel) => m.name === this.mutatedRule.model.name)

    if (!model) {
      throw new Error(`Invalid smart playlist model: ${this.mutatedRule.model.name}`)
    }

    this.mutatedRule.model = this.selectedModel = model

    const operator = this.options.find(o => o.operator === this.mutatedRule.operator)

    if (!operator) {
      throw new Error(`Invalid smart playlist operator: ${this.mutatedRule.operator}`)
    }

    this.selectedOperator = operator
  },

  methods: {
    onInput (): void {
      this.$emit('input', {
        id: this.mutatedRule.id,
        model: this.selectedModel,
        operator: this.selectedOperator.operator,
        value: this.availableInputs.map(input => input.value)
      } as SmartPlaylistRule)
    },

    removeRule (): void {
      this.$emit('remove')
    }
  }
})
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
