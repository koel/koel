---
title: API Reference

language_tabs:
- javascript
- bash

includes:

search: true
---
<!-- START_INFO -->
# Info

Welcome to the generated API reference.
[Get Postman Collection](/api-docs/collection.json)

<!-- END_INFO -->
This is the official API documentation for [Koel](https://koel.phanan.net), generated from the source code using [Laravel API Documentation Generator](https://github.com/mpociot/laravel-apidoc-generator). 
If you spot any mistake or want to add an improvement, please [submit an issue](https://github.com/phanan/koel/issues/new) or [open a pull request](https://github.com/phanan/koel/compare).  


#1. Authentication
<!-- START_d131f717df7db546af1657d1e7ce10f6 -->
## Log a user in

Koel uses [JSON Web Tokens](https://jwt.io/) (JWT) for authentication.
After the user has been authenticated, a random "token" will be returned.
This token should then be saved in a local storage and used as an `Authorization: Bearer` header
for consecutive calls.

Notice: The token is valid for a week, after that the user will need to log in again.

> Example request:

```bash
curl -X POST "http://koel.test/api/me"     -d "email"="john@doe.com" \
    -d "password"="SoSecureMuchW0w" 
```

```javascript
const url = new URL("http://koel.test/api/me");

let headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "email": "john@doe.com",
    "password": "SoSecureMuchW0w",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "token": "<a-random-string>"
}
```

### HTTP Request
`POST api/me`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    email | string |  required  | The user's email.
    password | string |  required  | The password.

<!-- END_d131f717df7db546af1657d1e7ce10f6 -->

<!-- START_772eabda142fbed1f55b5e4c9605891c -->
## Log the current user out

> Example request:

```bash
curl -X DELETE "http://koel.test/api/me" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/me");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


### HTTP Request
`DELETE api/me`


<!-- END_772eabda142fbed1f55b5e4c9605891c -->

#2. Application data
<!-- START_024021c3c17f0cb3ad10ff7ab83b1aa0 -->
## Get application data

The big fat call to retrieve a set of application data catered for the current user
(songs, albums, artists, playlists, interactions, and if the user is an admin, settings as well).
Naturally, this call should be made right after the user has been logged in, when you need to populate
the application's interface with useful information.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/data" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/data");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "albums": [
        {
            "id": 42,
            "artist_id": 42,
            "name": "...And Justice For All"
        },
        {
            "...": "..."
        }
    ],
    "allowDownload": true,
    "artists": [
        {
            "id": 42,
            "name": "Metallica"
        },
        {
            "...": "..."
        }
    ],
    "cdnUrl": "https:\/\/yourcdn.koel.example\/",
    "currentUser": {
        "id": 1,
        "name": "John Doe",
        "email": "john@doe.net",
        "is_admin": true,
        "preferences": {
            "lastfm_session_key": "hidden"
        }
    },
    "currentVersion": "v3.7.2",
    "interactions": [
        {
            "song_id": "f88c7671623c6b8be881e2a04e685509",
            "liked": false,
            "play_count": 5
        },
        {
            "...": "..."
        }
    ],
    "latestVersion": "v3.7.2",
    "playlists": [
        {
            "id": 1,
            "name": "Ballads",
            "rules": null,
            "is_smart": false
        },
        {
            "...": "..."
        }
    ],
    "recentlyPlayed": [
        "f78de3724e2823e7e4cfb660c4f691e9",
        "aebb93a69d6c8af79a1004aceabb201c",
        "..."
    ],
    "settings": {
        "media_path": "\/var\/www\/media"
    },
    "songs": [
        {
            "id": "00037ec0715a8781104ffd8efe0db06a",
            "album_id": 42,
            "artist_id": 42,
            "title": "Carpe Diem Baby",
            "created_at": "2015-12-10 05:52:22",
            "disc": 1,
            "track": 7,
            "length": 372.27
        },
        {
            "...": "..."
        }
    ],
    "supportsTranscoding": true,
    "useLastfm": true,
    "useYouTube": true,
    "useiTunes": true,
    "users": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@doe.com",
            "is_admin": true
        },
        {
            "...": "..."
        }
    ]
}
```

### HTTP Request
`GET api/data`


<!-- END_024021c3c17f0cb3ad10ff7ab83b1aa0 -->

#3. Song interactions
<!-- START_8ea879d7ef5eb537c1999e83bffa08b4 -->
## Play a song

The GET request to play/stream a song. By default Koel will serve the file as-is, unless it's a FLAC.
If the value of `transcode` is truthy, Koel will attempt to transcode the file into `bitRate`kbps using ffmpeg.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/{song}/play/{transcode?}/{bitrate?}" 
```

```javascript
const url = new URL("http://koel.test/api/{song}/play/{transcode?}/{bitrate?}");

    let params = {
            "jwt-token": "IHcH5I1rWR40Sqg2",
        };
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/{song}/play/{transcode?}/{bitrate?}`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    jwt-token |  required  | The JWT token.

<!-- END_8ea879d7ef5eb537c1999e83bffa08b4 -->

<!-- START_a1c4d62f5a36b1ff9e0513802f860a12 -->
## Increase play count

Increase a song's play count as the currently authenticated user.
This request should be made whenever a song is played.
An "interaction" record including the song and current user's data will be returned.

> Example request:

```bash
curl -X POST "http://koel.test/api/interaction/play" \
    -H "Authorization: Bearer {token}" \
    -d "song"="0146d01afb742b01f28ab8b556f9a75d" 
```

```javascript
const url = new URL("http://koel.test/api/interaction/play");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "song": "0146d01afb742b01f28ab8b556f9a75d",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "song_id": "0146d01afb742b01f28ab8b556f9a75d",
    "liked": true,
    "play_count": 228,
    "song": {
        "id": "0146d01afb742b01f28ab8b556f9a75d",
        "album_id": 1363,
        "artist_id": 430,
        "title": "The Show Must Go On",
        "length": 407.33,
        "track": 0,
        "disc": 1,
        "created_at": "2017-02-07 10:35:03",
        "artist": {
            "id": 430,
            "name": "Queen",
            "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a7727c2afbb09.08223866.png"
        },
        "album": {
            "id": 1363,
            "artist_id": 430,
            "name": "Innuendo",
            "cover": "https:\/\/koel.yourdomain.net\/img\/covers\/5899a2d7a19c90.72864263.jpg",
            "created_at": "2017-02-07 10:35:03",
            "is_compilation": false
        }
    },
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@doe.com",
        "is_admin": true,
        "preferences": {
            "lastfm_session_key": "hidden"
        }
    }
}
```

### HTTP Request
`POST api/interaction/play`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    song | string |  required  | The ID of the song.

<!-- END_a1c4d62f5a36b1ff9e0513802f860a12 -->

<!-- START_a1095be9dc97ea1b85319566c3f18092 -->
## Like or unlike a song

An "interaction" record including the song and current user's data will be returned.

> Example request:

```bash
curl -X POST "http://koel.test/api/interaction/like" \
    -H "Authorization: Bearer {token}" \
    -d "song"="0146d01afb742b01f28ab8b556f9a75d" 
```

```javascript
const url = new URL("http://koel.test/api/interaction/like");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "song": "0146d01afb742b01f28ab8b556f9a75d",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "song_id": "0146d01afb742b01f28ab8b556f9a75d",
    "liked": true,
    "play_count": 228,
    "song": {
        "id": "0146d01afb742b01f28ab8b556f9a75d",
        "album_id": 1363,
        "artist_id": 430,
        "title": "The Show Must Go On",
        "length": 407.33,
        "track": 0,
        "disc": 1,
        "created_at": "2017-02-07 10:35:03",
        "artist": {
            "id": 430,
            "name": "Queen",
            "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a7727c2afbb09.08223866.png"
        },
        "album": {
            "id": 1363,
            "artist_id": 430,
            "name": "Innuendo",
            "cover": "https:\/\/koel.yourdomain.net\/img\/covers\/5899a2d7a19c90.72864263.jpg",
            "created_at": "2017-02-07 10:35:03",
            "is_compilation": false
        }
    },
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@doe.com",
        "is_admin": true,
        "preferences": {
            "lastfm_session_key": "hidden"
        }
    }
}
```

### HTTP Request
`POST api/interaction/like`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    song | string |  required  | The ID of the song.

<!-- END_a1095be9dc97ea1b85319566c3f18092 -->

<!-- START_70a0987edd62e0427ffd210d6dfeee0b -->
## Like multiple songs

Like several songs at once, useful for "batch" actions. An array of "interaction" records containing the song
and user data will be returned.

> Example request:

```bash
curl -X POST "http://koel.test/api/interaction/batch/like" \
    -H "Authorization: Bearer {token}" \
    -d "songs"="[]" 
```

```javascript
const url = new URL("http://koel.test/api/interaction/batch/like");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "songs": "[]",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[
    {
        "song_id": "0146d01afb742b01f28ab8b556f9a75d",
        "liked": true,
        "play_count": 228,
        "song": {
            "id": "0146d01afb742b01f28ab8b556f9a75d",
            "album_id": 1363,
            "artist_id": 430,
            "title": "The Show Must Go On",
            "length": 407.33,
            "track": 0,
            "disc": 1,
            "created_at": "2017-02-07 10:35:03",
            "artist": {
                "id": 430,
                "name": "Queen",
                "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a7727c2afbb09.08223866.png"
            },
            "album": {
                "id": 1363,
                "artist_id": 430,
                "name": "Innuendo",
                "cover": "https:\/\/koel.yourdomain.net\/img\/covers\/5899a2d7a19c90.72864263.jpg",
                "created_at": "2017-02-07 10:35:03",
                "is_compilation": false
            }
        },
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@doe.com",
            "is_admin": true,
            "preferences": {
                "lastfm_session_key": "hidden"
            }
        }
    },
    {
        "...": "..."
    }
]
```

### HTTP Request
`POST api/interaction/batch/like`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    songs | array |  required  | An array of song IDs.

<!-- END_70a0987edd62e0427ffd210d6dfeee0b -->

<!-- START_1ffdb72cb23b18d9ecb8b07d3c0240f0 -->
## Unlike multiple songs

Unlike several songs at once, useful for "batch" actions. An array of "interaction" records containing the song
and user data will be returned.

> Example request:

```bash
curl -X POST "http://koel.test/api/interaction/batch/unlike" \
    -H "Authorization: Bearer {token}" \
    -d "songs"="[]" 
```

```javascript
const url = new URL("http://koel.test/api/interaction/batch/unlike");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "songs": "[]",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[
    {
        "song_id": "0146d01afb742b01f28ab8b556f9a75d",
        "liked": true,
        "play_count": 228,
        "song": {
            "id": "0146d01afb742b01f28ab8b556f9a75d",
            "album_id": 1363,
            "artist_id": 430,
            "title": "The Show Must Go On",
            "length": 407.33,
            "track": 0,
            "disc": 1,
            "created_at": "2017-02-07 10:35:03",
            "artist": {
                "id": 430,
                "name": "Queen",
                "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a7727c2afbb09.08223866.png"
            },
            "album": {
                "id": 1363,
                "artist_id": 430,
                "name": "Innuendo",
                "cover": "https:\/\/koel.yourdomain.net\/img\/covers\/5899a2d7a19c90.72864263.jpg",
                "created_at": "2017-02-07 10:35:03",
                "is_compilation": false
            }
        },
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@doe.com",
            "is_admin": true,
            "preferences": {
                "lastfm_session_key": "hidden"
            }
        }
    },
    {
        "...": "..."
    }
]
```

### HTTP Request
`POST api/interaction/batch/unlike`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    songs | array |  required  | An array of song IDs.

<!-- END_1ffdb72cb23b18d9ecb8b07d3c0240f0 -->

<!-- START_98a64836de32d52385d203ab618f9ddd -->
## Get recently played songs

Get a list of songs recently played by the current user.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/interaction/recently-played/{count?}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/interaction/recently-played/{count?}");

    let params = {
            "count": "2",
        };
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[
    "0146d01afb742b01f28ab8b556f9a75d",
    "c741133cb8d1982a5c60b1ce2a1e6e47"
]
```

### HTTP Request
`GET api/interaction/recently-played/{count?}`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    count |  optional  | The maximum number of songs to be returned.

<!-- END_98a64836de32d52385d203ab618f9ddd -->

#4. Playlist management
<!-- START_0f95a89b7f06c40893a1e50400952f5c -->
## Get current user&#039;s playlists

> Example request:

```bash
curl -X GET -G "http://koel.test/api/playlist" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/playlist");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[
    {
        "id": 13,
        "name": "Ballads",
        "rules": null,
        "is_smart": false
    },
    {
        "id": 17,
        "name": "Brand New Tracks",
        "rules": [
            {
                "id": 1543242741773,
                "rules": [
                    {
                        "id": 1543242742767,
                        "model": "interactions.play_count",
                        "operator": "is",
                        "value": [
                            "0"
                        ]
                    }
                ]
            }
        ],
        "is_smart": true
    },
    {
        "id": 12,
        "name": "Great Solos",
        "rules": null,
        "is_smart": false
    }
]
```

### HTTP Request
`GET api/playlist`


<!-- END_0f95a89b7f06c40893a1e50400952f5c -->

<!-- START_3e7029f85581865fdc020295518c93f3 -->
## Create a new playlist

> Example request:

```bash
curl -X POST "http://koel.test/api/playlist" \
    -H "Authorization: Bearer {token}" \
    -d "name"="Sleepy Songs" \
    -d "rules"="[]" 
```

```javascript
const url = new URL("http://koel.test/api/playlist");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "name": "Sleepy Songs",
    "rules": "[]",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "id": 42,
    "name": "Sleepy Songs",
    "rules": [],
    "is_smart": false
}
```

### HTTP Request
`POST api/playlist`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    name | string |  required  | Name of the playlist.
    rules | array |  optional  | An array of rules if creating a "smart playlist."

<!-- END_3e7029f85581865fdc020295518c93f3 -->

<!-- START_e0cc8988ecbec0fac9181c28cd084238 -->
## Rename a playlist

> Example request:

```bash
curl -X PUT "http://koel.test/api/playlist/{playlist}" \
    -H "Authorization: Bearer {token}" \
    -d "name"="Catchy Songs" 
```

```javascript
const url = new URL("http://koel.test/api/playlist/{playlist}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "name": "Catchy Songs",
})

fetch(url, {
    method: "PUT",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "id": 42,
    "name": "Catchy Songs",
    "rules": [],
    "is_smart": false
}
```

### HTTP Request
`PUT api/playlist/{playlist}`

`PATCH api/playlist/{playlist}`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    name | string |  required  | New name of the playlist.

<!-- END_e0cc8988ecbec0fac9181c28cd084238 -->

<!-- START_356c5b315a285debadf8b289d3bae312 -->
## Delete a playlist

> Example request:

```bash
curl -X DELETE "http://koel.test/api/playlist/{playlist}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/playlist/{playlist}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`DELETE api/playlist/{playlist}`


<!-- END_356c5b315a285debadf8b289d3bae312 -->

<!-- START_68b67f3bf318fce97664a5d0c952b38b -->
## Replace a playlist&#039;s content

Instead of adding or removing songs individually, a playlist's content is replaced entirely with an array of song IDs.

> Example request:

```bash
curl -X PUT "http://koel.test/api/playlist/{playlist}/sync" \
    -H "Authorization: Bearer {token}" \
    -d "songs"="[]" 
```

```javascript
const url = new URL("http://koel.test/api/playlist/{playlist}/sync");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "songs": "[]",
})

fetch(url, {
    method: "PUT",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`PUT api/playlist/{playlist}/sync`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    songs | array |  required  | An array of song IDs.

<!-- END_68b67f3bf318fce97664a5d0c952b38b -->

<!-- START_82c6e7b4ff4186b87ca6c4b6514cfa74 -->
## Get a playlist&#039;s songs

> Example request:

```bash
curl -X GET -G "http://koel.test/api/playlist/{playlist}/songs" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/playlist/{playlist}/songs");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[
    "0146d01afb742b01f28ab8b556f9a75d",
    "c741133cb8d1982a5c60b1ce2a1e6e47",
    "..."
]
```

### HTTP Request
`GET api/playlist/{playlist}/songs`


<!-- END_82c6e7b4ff4186b87ca6c4b6514cfa74 -->

#5. Media information
<!-- START_8b76894631cd3b3d4f86fab8014bc4e1 -->
## Update song information

> Example request:

```bash
curl -X PUT "http://koel.test/api/songs" \
    -H "Authorization: Bearer {token}" \
    -d "songs"="[]" \
    -d "data"="{}" 
```

```javascript
const url = new URL("http://koel.test/api/songs");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "songs": "[]",
    "data": "{}",
})

fetch(url, {
    method: "PUT",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```


### HTTP Request
`PUT api/songs`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    songs | array |  required  | An array of song IDs to be updated.
    data | object |  required  | The new data, with these supported fields: `title`, `artistName`, `albumName`, and `lyrics`.

<!-- END_8b76894631cd3b3d4f86fab8014bc4e1 -->

<!-- START_a670fbc8f3161e7fda744d7cc52ca5ea -->
## Get album&#039;s extra information

Get extra information about an album via Last.fm.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/album/{album}/info" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/album/{album}/info");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "url": "https:\/\/www.last.fm\/music\/Queen\/Innuendo",
    "image": "https:\/\/lastfm-img2.akamaized.net\/i\/u\/300x300\/b56adcd16ca6454498981a8470a3ec06.png",
    "wiki": {
        "summary": "Innuendo is a 1991 album by English rock band Queen...",
        "full": "Innuendo is a 1991 album by English rock band Queen. It is the band's fourteenth studio album..."
    },
    "tracks": [
        {
            "title": "Innuendo",
            "length": 392,
            "url": "https:\/\/www.last.fm\/music\/Queen\/_\/Innuendo"
        },
        {
            "title": "I'm Going Slightly Mad",
            "length": 247,
            "url": "https:\/\/www.last.fm\/music\/Queen\/_\/I%27m+Going+Slightly+Mad"
        },
        {
            "...": "..."
        }
    ],
    "cover": "https:\/\/koel.yourdomain.net\/img\/covers\/5a771ec82a5d72.25096250.png"
}
```

### HTTP Request
`GET api/album/{album}/info`


<!-- END_a670fbc8f3161e7fda744d7cc52ca5ea -->

<!-- START_92d9d0e186f60300dfde56b152e8536b -->
## Get artist&#039;s extra information

Get extra information about an artist via Last.fm.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/artist/{artist}/info" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/artist/{artist}/info");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "url": "https:\/\/www.last.fm\/music\/Queen",
    "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a772708e7de19.84120679.png",
    "bio": {
        "summary": "Queen were an English rock band originally consisting of four members...",
        "full": "Queen were an English rock band originally consisting of four members: vocalist Freddie Mercury, guitarist Brian May, bass guitarist John Deacon, and drummer Roger Taylor..."
    }
}
```

### HTTP Request
`GET api/artist/{artist}/info`


<!-- END_92d9d0e186f60300dfde56b152e8536b -->

<!-- START_8f5482e7dc76601d5d24f0120eddfc14 -->
## Get song&#039;s extra information

Get a song's extra information. The response of this request is a superset of both corresponding
`album/{album}/info` and `artist/{artist}/info` requests, combined with the song's lyrics and related YouTube
videos, if applicable. This means you can (and should) cache this information somewhere ;)

> Example request:

```bash
curl -X GET -G "http://koel.test/api/song/{song}/info" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/song/{song}/info");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "lyrics": "...",
    "album_info": {
        "url": "https:\/\/www.last.fm\/music\/Queen\/Innuendo",
        "image": "https:\/\/lastfm-img2.akamaized.net\/i\/u\/300x300\/b56adcd16ca6454498981a8470a3ec06.png",
        "wiki": {
            "summary": "Innuendo is a 1991 album by English rock band Queen...",
            "full": "Innuendo is a 1991 album by English rock band Queen. It is the band's fourteenth studio album and the last..."
        },
        "tracks": [
            {
                "title": "Innuendo",
                "length": 392,
                "url": "https:\/\/www.last.fm\/music\/Queen\/_\/Innuendo"
            },
            {
                "title": "I'm Going Slightly Mad",
                "length": 247,
                "url": "https:\/\/www.last.fm\/music\/Queen\/_\/I%27m+Going+Slightly+Mad"
            },
            {
                "...": "..."
            }
        ]
    },
    "artist_info": {
        "url": "https:\/\/www.last.fm\/music\/Queen",
        "image": "https:\/\/koel.yourdomain.net\/img\/artists\/5a772708e7de19.84120679.png",
        "bio": {
            "summary": "Queen were an English rock band...",
            "full": "<br \/>\nQueen were an English rock band originally consisting of four members: vocalist Freddie Mercury, guitarist Brian May, bass guitarist John Deacon, and drummer Roger Taylor..."
        }
    },
    "youtube": {
        "kind": "youtube#searchListResponse",
        "etag": "\"XI7nbFXulYBIpL0ayR_gDh3eu1k\/UMIztE1sQ8L9tu7igiTaSoBA9tw\"",
        "nextPageToken": "CAoQAA",
        "regionCode": "CH",
        "pageInfo": {
            "totalResults": 1000000,
            "resultsPerPage": 10
        },
        "items": [
            {
                "kind": "youtube#searchResult",
                "etag": "\"XI7nbFXulYBIpL0ayR_gDh3eu1k\/bRRI2oEvvXIbCBFKv8WrLUaG-0A\"",
                "id": {
                    "kind": "youtube#video",
                    "videoId": "t99KH0TR-J4"
                },
                "snippet": {
                    "publishedAt": "2013-10-15T14:24:31.000Z",
                    "channelId": "UCiMhD4jzUqG-IgPzUmmytRQ",
                    "title": "Queen - The Show Must Go On (Official Video)",
                    "description": "Subscribe to the Official Queen Channel Here http:\/\/bit.ly\/Subscribe2Queen Taken from Innuendo, 1991. Queen - The Show Must Go On (promo video, 1991) ...",
                    "thumbnails": {
                        "default": {
                            "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/default.jpg",
                            "width": 120,
                            "height": 90
                        },
                        "medium": {
                            "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/mqdefault.jpg",
                            "width": 320,
                            "height": 180
                        },
                        "high": {
                            "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/hqdefault.jpg",
                            "width": 480,
                            "height": 360
                        }
                    },
                    "channelTitle": "Queen Official",
                    "liveBroadcastContent": "none"
                }
            },
            {
                "...": "..."
            }
        ]
    }
}
```

### HTTP Request
`GET api/song/{song}/info`


<!-- END_8f5482e7dc76601d5d24f0120eddfc14 -->

#6. Download
<!-- START_339c05326ab691afe5ba03de806b77b9 -->
## Download one or several songs

> Example request:

```bash
curl -X GET -G "http://koel.test/api/download/songs" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/download/songs");

    let params = {
            "songs": "xssITiRFm57vV9VF",
        };
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/download/songs`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    songs |  optional  | array An array of song IDs

<!-- END_339c05326ab691afe5ba03de806b77b9 -->

<!-- START_c4beea69287c52c5ddaf304c1881cfd8 -->
## Download a whole album

> Example request:

```bash
curl -X GET -G "http://koel.test/api/download/album/{album}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/download/album/{album}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/download/album/{album}`


<!-- END_c4beea69287c52c5ddaf304c1881cfd8 -->

<!-- START_d7a146e78a726566715eea4427009b54 -->
## Download all songs by an artist

Don't see why one would need this, really.
Let's pray to God the user doesn't trigger this on Elvis.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/download/artist/{artist}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/download/artist/{artist}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/download/artist/{artist}`


<!-- END_d7a146e78a726566715eea4427009b54 -->

<!-- START_c450a89b6bb24daa242d077b01238e7d -->
## Download a whole playlist

> Example request:

```bash
curl -X GET -G "http://koel.test/api/download/playlist/{playlist}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/download/playlist/{playlist}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/download/playlist/{playlist}`


<!-- END_c450a89b6bb24daa242d077b01238e7d -->

<!-- START_2ada2dccdced8279b3ab405334d3298f -->
## Download all songs favorite&#039;d by the current user

> Example request:

```bash
curl -X GET -G "http://koel.test/api/download/favorites" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/download/favorites");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`GET api/download/favorites`


<!-- END_2ada2dccdced8279b3ab405334d3298f -->

#7. User management
<!-- START_f0654d3f2fc63c11f5723f233cc53c83 -->
## Create a new user

> Example request:

```bash
curl -X POST "http://koel.test/api/user" \
    -H "Authorization: Bearer {token}" \
    -d "name"="John Doe" \
    -d "email"="john@doe.com" \
    -d "password"="SoSecureMuchW0w" 
```

```javascript
const url = new URL("http://koel.test/api/user");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "name": "John Doe",
    "email": "john@doe.com",
    "password": "SoSecureMuchW0w",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "id": 42,
    "name": "John Doe",
    "email": "john@doe.com"
}
```

### HTTP Request
`POST api/user`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    name | string |  required  | User's name.
    email | string |  required  | User's email.
    password | string |  required  | User's password.

<!-- END_f0654d3f2fc63c11f5723f233cc53c83 -->

<!-- START_a4a2abed1e8e8cad5e6a3282812fe3f3 -->
## Update a user

> Example request:

```bash
curl -X PUT "http://koel.test/api/user/{user}" \
    -H "Authorization: Bearer {token}" \
    -d "name"="Johny Doe" \
    -d "email"="johny@doe.com" \
    -d "password"="wJgmKkTITwHmdgUG" 
```

```javascript
const url = new URL("http://koel.test/api/user/{user}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "name": "Johny Doe",
    "email": "johny@doe.com",
    "password": "wJgmKkTITwHmdgUG",
})

fetch(url, {
    method: "PUT",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`PUT api/user/{user}`

`PATCH api/user/{user}`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    name | string |  required  | New name.
    email | string |  required  | New email.
    password | string |  optional  | New password (null/blank for no change)

<!-- END_a4a2abed1e8e8cad5e6a3282812fe3f3 -->

<!-- START_4bb7fb4a7501d3cb1ed21acfc3b205a9 -->
## Delete a user

> Example request:

```bash
curl -X DELETE "http://koel.test/api/user/{user}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/user/{user}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`DELETE api/user/{user}`


<!-- END_4bb7fb4a7501d3cb1ed21acfc3b205a9 -->

<!-- START_b19e2ecbb41b5fa6802edaf581aab5f6 -->
## Get current user&#039;s profile

> Example request:

```bash
curl -X GET -G "http://koel.test/api/me" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/me");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "id": 42,
    "name": "John Doe",
    "email": "john@doe.com"
}
```

### HTTP Request
`GET api/me`


<!-- END_b19e2ecbb41b5fa6802edaf581aab5f6 -->

<!-- START_fa77e70040eb60f0488db2d285d1cdc7 -->
## Update current user&#039;s profile

> Example request:

```bash
curl -X PUT "http://koel.test/api/me" \
    -H "Authorization: Bearer {token}" \
    -d "name"="Johny Doe" \
    -d "email"="johny@doe.com" \
    -d "password"="Lm4qQDyLb3NNddWI" 
```

```javascript
const url = new URL("http://koel.test/api/me");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "name": "Johny Doe",
    "email": "johny@doe.com",
    "password": "Lm4qQDyLb3NNddWI",
})

fetch(url, {
    method: "PUT",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`PUT api/me`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    name | string |  required  | New name.
    email | string |  required  | New email.
    password | string |  optional  | New password (null/blank for no change)

<!-- END_fa77e70040eb60f0488db2d285d1cdc7 -->

#8. Settings
<!-- START_1e1aaba3a713ac3ce04a89d5f4ad0f2e -->
## Save the application settings

Save the application settings. Right now there's only one setting to be saved (`media_path`).

> Example request:

```bash
curl -X POST "http://koel.test/api/settings" \
    -H "Authorization: Bearer {token}" \
    -d "media_path"="/var/www/media/" 
```

```javascript
const url = new URL("http://koel.test/api/settings");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "media_path": "/var/www/media/",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`POST api/settings`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    media_path | string |  required  | Absolute path to the media folder.

<!-- END_1e1aaba3a713ac3ce04a89d5f4ad0f2e -->

#AWS integration

These routes are meant for Amazon Web Services (AWS) integration with Koel. For more information, visit
[koel-aws](https://github.com/koel/koel-aws).
<!-- START_9999a98649bc4a1c25373dcae1994fbc -->
## Store a song

Create a new song or update an existing one with data sent from AWS.

> Example request:

```bash
curl -X POST "http://koel.test/api/os/s3/song" 
```

```javascript
const url = new URL("http://koel.test/api/os/s3/song");

let headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


### HTTP Request
`POST api/os/s3/song`


<!-- END_9999a98649bc4a1c25373dcae1994fbc -->

<!-- START_0c973c710226495c9d34381152b6e78f -->
## Remove a song

Remove a song whose information matches with data sent from AWS.

> Example request:

```bash
curl -X DELETE "http://koel.test/api/os/s3/song" 
```

```javascript
const url = new URL("http://koel.test/api/os/s3/song");

let headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "DELETE",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


### HTTP Request
`DELETE api/os/s3/song`


<!-- END_0c973c710226495c9d34381152b6e78f -->

#Last.fm integration
<!-- START_3f0f1280d6348b0337e5b773d2dabbb1 -->
## Scrobble a song

Create a [Last.fm scrobble entry](https://www.last.fm/api/scrobbling) for a song.

> Example request:

```bash
curl -X POST "http://koel.test/api/{song}/scrobble/{timestamp}" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/{song}/scrobble/{timestamp}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "POST",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```


### HTTP Request
`POST api/{song}/scrobble/{timestamp}`


<!-- END_3f0f1280d6348b0337e5b773d2dabbb1 -->

<!-- START_ada8e3ef973c35c16e20e6e72b30a68a -->
## Connect to Last.fm

[Connect](https://www.last.fm/api/authentication) the current user to Last.fm.
This is actually NOT an API request. The application should instead redirect the current user to this route,
which will send them to Last.fm for authentication. After authentication is successful, the user will be
redirected back to `api/lastfm/callback?token=<Last.fm token>`.

> Example request:

```bash
curl -X GET -G "http://koel.test/api/lastfm/connect" \
    -H "Authorization: Bearer {token}"
```

```javascript
const url = new URL("http://koel.test/api/lastfm/connect");

    let params = {
            "jwt-token": "AgOg6hUdoPnDBWHi",
        };
    Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

fetch(url, {
    method: "GET",
    headers: headers,
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (401):

```json
{
    "error": "token_not_provided"
}
```

### HTTP Request
`GET api/lastfm/connect`

#### Query Parameters

Parameter | Status | Description
--------- | ------- | ------- | -----------
    jwt-token |  required  | The JWT token of the user.

<!-- END_ada8e3ef973c35c16e20e6e72b30a68a -->

<!-- START_a53df47a60b7ce5a088aa7f84af2885c -->
## Set Last.fm session key

Set the Last.fm session key for the current user. This call should be made after the user is
[connected to Last.fm](https://www.last.fm/api/authentication).

> Example request:

```bash
curl -X POST "http://koel.test/api/lastfm/session-key" \
    -H "Authorization: Bearer {token}" \
    -d "key"="9mVYhfjKFsD9W160" 
```

```javascript
const url = new URL("http://koel.test/api/lastfm/session-key");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "key": "9mVYhfjKFsD9W160",
})

fetch(url, {
    method: "POST",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
[]
```

### HTTP Request
`POST api/lastfm/session-key`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    key | string |  required  | The Last.fm [session key](https://www.last.fm/api/show/auth.getSession).

<!-- END_a53df47a60b7ce5a088aa7f84af2885c -->

#YouTube integration
<!-- START_4389db36c36e0737f5cdb85b59f8279b -->
## Search for YouTube videos

Search YouTube for videos related to a song (using its title and artist name).

> Example request:

```bash
curl -X GET -G "http://koel.test/api/youtube/search/song/{song}" \
    -H "Authorization: Bearer {token}" \
    -d "pageToken"="26tAbdCbQhXPqp73" 
```

```javascript
const url = new URL("http://koel.test/api/youtube/search/song/{song}");

let headers = {
    "Authorization": "Bearer {token}",
    "Accept": "application/json",
    "Content-Type": "application/json",
}

let body = JSON.stringify({
    "pageToken": "26tAbdCbQhXPqp73",
})

fetch(url, {
    method: "GET",
    headers: headers,
    body: body
})
    .then(response => response.json())
    .then(json => console.log(json));
```

> Example response (200):

```json
{
    "kind": "youtube#searchListResponse",
    "etag": "\"XI7nbFXulYBIpL0ayR_gDh3eu1k\/UMIztE1sQ8L9tu7igiTaSoBA9tw\"",
    "nextPageToken": "CAoQAA",
    "regionCode": "CH",
    "pageInfo": {
        "totalResults": 1000000,
        "resultsPerPage": 10
    },
    "items": [
        {
            "kind": "youtube#searchResult",
            "etag": "\"XI7nbFXulYBIpL0ayR_gDh3eu1k\/bRRI2oEvvXIbCBFKv8WrLUaG-0A\"",
            "id": {
                "kind": "youtube#video",
                "videoId": "t99KH0TR-J4"
            },
            "snippet": {
                "publishedAt": "2013-10-15T14:24:31.000Z",
                "channelId": "UCiMhD4jzUqG-IgPzUmmytRQ",
                "title": "Queen - The Show Must Go On (Official Video)",
                "description": "Subscribe to the Official Queen Channel Here http:\/\/bit.ly\/Subscribe2Queen Taken from Innuendo, 1991. Queen - The Show Must Go On (promo video, 1991) ...",
                "thumbnails": {
                    "default": {
                        "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/default.jpg",
                        "width": 120,
                        "height": 90
                    },
                    "medium": {
                        "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/mqdefault.jpg",
                        "width": 320,
                        "height": 180
                    },
                    "high": {
                        "url": "https:\/\/i.ytimg.com\/vi\/t99KH0TR-J4\/hqdefault.jpg",
                        "width": 480,
                        "height": 360
                    }
                },
                "channelTitle": "Queen Official",
                "liveBroadcastContent": "none"
            }
        },
        {
            "...": "..."
        }
    ]
}
```

### HTTP Request
`GET api/youtube/search/song/{song}`

#### Body Parameters

Parameter | Type | Status | Description
--------- | ------- | ------- | ------- | -----------
    pageToken | string |  optional  | The [`nextPageToken`](https://developers.google.com/youtube/v3/guides/implementation/pagination), if applicable.

<!-- END_4389db36c36e0737f5cdb85b59f8279b -->


