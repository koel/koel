<template>
  <FormRow>
    <template #label>Role</template>
    <SelectBox v-model="value" name="role" required>
      <option v-for="{ id, label } in assignableRoles" :key="id" :value="id">{{ label }}</option>
    </SelectBox>
    <template #help>{{ selectedRoleDescription }}</template>
  </FormRow>
</template>

<script setup lang="ts">
import { computed, toRef } from 'vue'
import { commonStore } from '@/stores/commonStore'

import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const props = withDefaults(defineProps<{ modelValue?: Role }>(), { modelValue: 'user' })
const emit = defineEmits<{ (e: 'update:modelValue', value: Role): void }>()

const assignableRoles = toRef(commonStore.state, 'assignable_roles')

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const selectedRoleDescription = computed(() => {
  const selectedRole = assignableRoles.value.find(({ id }) => id === value.value)
  return selectedRole?.description || ''
})
</script>
