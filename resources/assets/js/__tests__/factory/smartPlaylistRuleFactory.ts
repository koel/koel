import type { Faker } from '@faker-js/faker'
import models from '@/config/smart-playlist/models'

export default (faker: Faker): SmartPlaylistRule => ({
  id: faker.string.uuid(),
  model: faker.helpers.arrayElement(models),
  operator: faker.helpers.arrayElement<SmartPlaylistOperator['operator']>(['is', 'contains', 'isNot']),
  value: [faker.word.sample()],
})
