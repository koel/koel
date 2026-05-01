import factory from 'factoria'
import { faker } from '@faker-js/faker'

export default (): SmartPlaylistRuleGroup => ({
  id: faker.string.uuid(),
  rules: factory('smart-playlist-rule').make(3) as unknown as SmartPlaylistRule[],
})
