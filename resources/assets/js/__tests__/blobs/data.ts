import factory from '@/__tests__/factory'

const currentUser = factory<User>('user', {
  id: 1,
  name: 'Phan An',
  email: 'me@phanan.net',
  is_admin: true
})

export default {
  artists: [
    {
      id: 1,
      name: 'Unknown Artist'
    },
    {
      id: 2,
      name: 'Various Artists'
    },
    {
      id: 3,
      name: 'All-4-One'
    },
    {
      id: 4,
      name: 'Boy Dylan'
    },
    {
      id: 5,
      name: 'James Blunt'
    }
  ],
  albums: [
    {
      id: 1193,
      artist_id: 3,
      name: 'All-4-One',
      cover: '/img/covers/565c0f7067425.jpeg'
    },
    {
      id: 1194,
      artist_id: 3,
      name: 'And The Music Speaks',
      cover: '/img/covers/unknown-album.png'
    },
    {
      id: 1195,
      artist_id: 3,
      name: 'Space Jam',
      cover: '/img/covers/565c0f7115e0f.png'
    },
    {
      id: 1217,
      artist_id: 4,
      name: 'Highway 61 Revisited',
      cover: '/img/covers/565c0f76dc6e8.jpeg'
    },
    {
      id: 1218,
      artist_id: 4,
      name: 'Pat Garrett & Billy the Kid',
      cover: '/img/covers/unknown-album.png'
    },
    {
      id: 1219,
      artist_id: 4,
      name: "The Times They Are A-Changin",
      cover: '/img/covers/unknown-album.png'
    },
    {
      id: 1268,
      artist_id: 5,
      name: 'Back To Bedlam',
      cover: '/img/covers/unknown-album.png'
    }
  ],

  songs: [
    {
      id: '39189f4545f9d5671fb3dc964f0080a0',
      album_id: 1193,
      artist_id: 3,
      title: 'I Swear',
      length: 259.92,
      playCount: 4
    },
    {
      id: 'a6a550f7d950d2a2520f9bf1a60f025a',
      album_id: 1194,
      artist_id: 3,
      title: 'I can love you like that',
      length: 262.61,
      playCount: 2
    },
    {
      id: 'd86c30fd34f13c1aff8db59b7fc9c610',
      album_id: 1195,
      artist_id: 3,
      title: 'I turn to you',
      length: 293.04
    },
    {
      id: 'e6d3977f3ffa147801ca5d1fdf6fa55e',
      album_id: 1217,
      artist_id: 4,
      title: 'Like a rolling stone',
      length: 373.63
    },
    {
      id: 'aa16bbef6a9710eb9a0f41ecc534fad5',
      album_id: 1218,
      artist_id: 4,
      title: "Knockin' on heaven's door",
      length: 151.9
    },
    {
      id: 'cb7edeac1f097143e65b1b2cde102482',
      album_id: 1219,
      artist_id: 4,
      title: "The times they are a-changin'",
      length: 196
    },
    {
      id: '0ba9fb128427b32683b9eb9140912a70',
      album_id: 1268,
      artist_id: 5,
      title: 'No bravery',
      length: 243.12
    },
    {
      id: '123fd1ad32240ecab28a4e86ed5173',
      album_id: 1268,
      artist_id: 5,
      title: 'So long, Jimmy',
      length: 265.04
    },
    {
      id: '6a54c674d8b16732f26df73f59c63e21',
      album_id: 1268,
      artist_id: 5,
      title: 'Wisemen',
      length: 223.14
    },
    {
      id: '6df7d82a9a8701e40d1c291cf14a16bc',
      album_id: 1268,
      artist_id: 5,
      title: 'Goodbye my lover',
      length: 258.61
    },
    {
      id: '74a2000d343e4587273d3ad14e2fd741',
      album_id: 1268,
      artist_id: 5,
      title: 'High',
      length: 245.86
    },
    {
      id: '7900ab518f51775fe6cf06092c074ee5',
      album_id: 1268,
      artist_id: 5,
      title: "You're beautiful",
      length: 213.29
    },
    {
      id: '803910a51f9893347e087af851e38777',
      album_id: 1268,
      artist_id: 5,
      title: 'Cry',
      length: 246.91
    },
    {
      id: 'd82b0d4d4803ebbcb61000a5b6a868f5',
      album_id: 1268,
      artist_id: 5,
      title: 'Tears and rain',
      length: 244.45
    }
  ],
  interactions: [
    {
      id: 1,
      song_id: '7900ab518f51775fe6cf06092c074ee5',
      liked: false,
      play_count: 1
    },
    {
      id: 2,
      song_id: '95c0ffc33c08c8c14ea5de0a44d5df3c',
      liked: false,
      play_count: 2
    },
    {
      id: 3,
      song_id: 'c83b201502eb36f1084f207761fa195c',
      liked: false,
      play_count: 1
    },
    {
      id: 4,
      song_id: 'cb7edeac1f097143e65b1b2cde102482',
      liked: true,
      play_count: 3
    },
    {
      id: 5,
      song_id: 'ccc38cc14bb95aefdf6da4b34adcf548',
      liked: false,
      play_count: 4
    }
  ],
  currentUser,
  users: [
    currentUser,
    factory<User>('user', {
      id: 2,
      name: 'John Doe',
      email: 'john@doe.tld',
      is_admin: false
    })
  ]
}
