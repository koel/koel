import { faker } from '@faker-js/faker'

export default (): Folder => ({
  type: 'folders',
  id: faker.string.uuid(),
  parent_id: faker.string.uuid(),
  name: faker.lorem.word(),
  is_uploads: false,
})
