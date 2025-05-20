import type { Faker } from '@faker-js/faker'

export default (faker: Faker): Folder => ({
  type: 'folders',
  id: faker.datatype.uuid(),
  parent_id: faker.datatype.uuid(),
  path: faker.system.filePath(),
  name: faker.lorem.word(),
})
