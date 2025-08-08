import { faker } from '@faker-js/faker'

export default (): RadioStation => {
  return {
    type: 'radio-stations',
    id: faker.string.ulid(),
    name: faker.company.name(),
    logo: faker.image.url(),
    url: faker.internet.url(),
    description: faker.lorem.sentence(),
    created_at: faker.date.past().toISOString(),
    is_public: faker.datatype.boolean(),
    favorite: faker.datatype.boolean(),
    playback_state: 'Stopped',
  }
}
