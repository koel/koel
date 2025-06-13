import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Folder => ({
  type: 'folders',
  id: faker.string.uuid(),
  parent_id: faker.string.uuid(),
  path: faker.system.filePath(),
  name: faker.lorem.word(),
})
