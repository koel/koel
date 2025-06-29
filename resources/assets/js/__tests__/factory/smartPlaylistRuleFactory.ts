import models from '@/config/smart-playlist/models'
import { faker } from '@faker-js/faker'

export default (): SmartPlaylistRule => ({
  id: faker.string.uuid(),
  model: faker.helpers.arrayElement(models),
  operator: faker.helpers.arrayElement<SmartPlaylistOperator['operator']>(['is', 'contains', 'isNot']),
  value: [faker.word.sample()],
})
