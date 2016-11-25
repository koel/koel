export default [
  {
    id: 1,
    name: "All-4-One",
    albums: [
      {
        id: 1193,
        artist_id: 1,
        name: "All-4-One",
        cover: "/public/img/covers/565c0f7067425.jpeg",
        songs: [
          {
            id: "39189f4545f9d5671fb3dc964f0080a0",
            album_id: 1193,
            title: "I Swear",
            length: 259.92,
            playCount: 4,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          }
        ]
      },
      {
        id: 1194,
        artist_id: 1,
        name: "And The Music Speaks",
        cover: "/public/img/covers/unknown-album.png",
        songs: [
          {
            id: "a6a550f7d950d2a2520f9bf1a60f025a",
            album_id: 1194,
            title: "I can love you like that",
            length: 262.61,
            playCount: 2,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          }
        ]
      },
      {
        id: 1195,
        artist_id: 1,
        name: "Space Jam",
        cover: "/public/img/covers/565c0f7115e0f.png",
        songs: [
          {
            id: "d86c30fd34f13c1aff8db59b7fc9c610",
            album_id: 1195,
            title: "I turn to you",
            length: 293.04,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          }
        ]
      }
    ]
  },
  {
    id: 2,
    name: "Bob Dylan",
    albums: [
      {
        id: 1217,
        artist_id: 2,
        name: "Highway 61 Revisited",
        cover: "/public/img/covers/565c0f76dc6e8.jpeg",
        songs: [
          {
            id: "e6d3977f3ffa147801ca5d1fdf6fa55e",
            album_id: 1217,
            title: "Like a rolling stone",
            length: 373.63,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }

          }
        ]
      },
      {
        id: 1218,
        artist_id: 2,
        name: "Pat Garrett & Billy the Kid",
        cover: "/public/img/covers/unknown-album.png",
        songs: [
          {
            id: "aa16bbef6a9710eb9a0f41ecc534fad5",
            album_id: 1218,
            title: "Knockin' on heaven's door",
            length: 151.9,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }

          }
        ]
      },
      {
        id: 1219,
        artist_id: 2,
        name: "The Times They Are A-Changin'",
        cover: "/public/img/covers/unknown-album.png",
        songs: [
          {
            id: "cb7edeac1f097143e65b1b2cde102482",
            album_id: 1219,
            title: "The times they are a-changin'",
            length: 196,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          }
        ]
      }
    ]
  },
  {
    id: 3,
    name: "James Blunt",
    albums: [
      {
        id: 1268,
        artist_id: 3,
        name: "Back To Bedlam",
        cover: "/public/img/covers/unknown-album.png",
        songs: [
          {
            id: "0ba9fb128427b32683b9eb9140912a70",
            album_id: 1268,
            title: "No bravery",
            length: 243.12,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "123fd1ad32240ecab28a4e86ed5173",
            album_id: 1268,
            title: "So long, Jimmy",
            length: 265.04,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "6a54c674d8b16732f26df73f59c63e21",
            album_id: 1268,
            title: "Wisemen",
            length: 223.14,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "6df7d82a9a8701e40d1c291cf14a16bc",
            album_id: 1268,
            title: "Goodbye my lover",
            length: 258.61,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "74a2000d343e4587273d3ad14e2fd741",
            album_id: 1268,
            title: "High",
            length: 245.86,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "7900ab518f51775fe6cf06092c074ee5",
            album_id: 1268,
            title: "You're beautiful",
            length: 213.29,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "803910a51f9893347e087af851e38777",
            album_id: 1268,
            title: "Cry",
            length: 246.91,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          },
          {
            id: "d82b0d4d4803ebbcb61000a5b6a868f5",
            album_id: 1268,
            title: "Tears and rain",
            length: 244.45,
            genre_id: 42,
            genre: {
              id: 42,
              name: 'Rock',
            }
          }
        ]
      }
    ]
  }
];

export const genres = [
  {
    id: 42,
    image: null,
    name: 'Rock',
  } 
]

export const singleAlbum = {
  id: 9999,
  artist_id: 99,
  name: "Foo bar",
  cover: "/foo.jpg",
  songs: [
    {
      id: "39189f4545f0d5671fc3dc964f0080a0",
      album_id: 9999,
      title: "A Foo Song",
      length: 100,
      playCount: 4,
      genre_id: 42,
      genre: {
        id: 42,
        name: 'Rock',
      }
    }, {
      id: "39189f4545f9d5671fc3dc96cf1080a0",
      album_id: 9999,
      title: "A Bar Song",
      length: 200,
      playCount: 7,
      genre_id: 42,
      genre: {
        id: 42,
        name: 'Rock',
      }
    }
  ]
};

export const singleArtist = {
  id: 999,
  name: "John Cena",
  albums: [
    {
      id: 9991,
      artist_id: 999,
      name: "It's John Cena!!!!",
      cover: "/tmp/john.jpg",
      songs: [
        {
          id: "e6d3977f3ffa147801ca5d1fdf6fa55f",
          album_id: 9991,
          title: "John Cena to the Rescue",
          length: 300,
          genre_id: 42,
          genre: {
            id: 42,
            name: 'Rock',
          }
        }
      ]
    }
  ]
};

export const singleSong = {
  id: "dccb0d4d4803ebbcb61000a5b6a868f5",
  album_id: 1193,
  title: "Foo and Bar",
  length: 100,
  playCount: 4,
  lyrics: '',
  genre_id: 42
};
