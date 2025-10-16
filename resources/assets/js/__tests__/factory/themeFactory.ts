import { faker } from '@faker-js/faker'

export default (): Theme => ({
  type: 'themes',
  id: faker.string.ulid(),
  name: faker.word.words(3),
  thumbnail_color: faker.color.rgb(),
  thumbnail_image: faker.image.url(),
  properties: {
    '--color-fg': faker.color.rgb(),
    '--color-bg': faker.color.rgb(),
    '--color-highlight': faker.color.rgb(),
    '--bg-image': `url("${faker.image.url()}")`,
    '--font-family': faker.helpers.arrayElement(['sans-serif', 'serif', 'monospace']),
    '--font-size': `${faker.number.int({ min: 12, max: 24 })}px`,
  },
  is_custom: true,
})
