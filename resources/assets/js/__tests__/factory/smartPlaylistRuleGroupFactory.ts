import { Faker } from '@faker-js/faker'
import factory from 'factoria'

export default (faker: Faker): SmartPlaylistRuleGroup => ({
  id: faker.datatype.uuid(),
  rules: factory<SmartPlaylistRule>('smart-playlist-rule', 3)
})
