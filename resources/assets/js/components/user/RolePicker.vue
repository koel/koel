<template>
  <FormRow>
    <template #label>{{ t('users.role') }}</template>
    <SelectBox v-model="value" name="role" required>
      <option v-for="{ id } in assignableRoles" :key="id" :value="id">{{ t(`users.roles.${id}.label`) }}</option>
    </SelectBox>
    <template #help>{{ selectedRoleDescription }}</template>
  </FormRow>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { acl } from '@/services/acl'

import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const { t } = useI18n()

const props = withDefaults(defineProps<{ modelValue?: Role }>(), { modelValue: 'user' })
const emit = defineEmits<{ (e: 'update:modelValue', value: Role): void }>()

const assignableRoles = ref<{ id: Role, label: string, description: string }[]>([])

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const selectedRoleDescription = computed(() => {
  const selectedRole = assignableRoles.value.find(({ id }) => id === value.value)
  if (selectedRole) {
    return t(`users.roles.${selectedRole.id}.description`)
  }
  return ''
})

onMounted(async () => (assignableRoles.value = await acl.fetchAssignableRoles()))
</script>
