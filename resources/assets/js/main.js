import $ from 'jquery';

import ls from './services/ls';

window.Vue = require('vue');
var app = new Vue(require('./app.vue'));

Vue.config.debug = false;
Vue.use(require('vue-resource'));
Vue.http.options.root = '/api';
Vue.http.interceptors.push({
    request(r) {
        var token = ls.get('jwt-token');

        if (token) {
            Vue.http.headers.common.Authorization = `Bearer ${token}`;
        }

        return r;
    },

    response(r) {
        if (r.status === 400 || r.status === 401) {
            if (r.request.method !== 'POST' && r.request.url !== 'me') {
                // This is not a failed login. Log out then.
                app.logout();
            }
        }

        if (r.headers && r.headers.Authorization) {
            ls.set('jwt-token', r.headers.Authorization);
        }

        if (r.data && r.data.token && r.data.token.length > 10) {
            ls.set('jwt-token', r.data.token);
        }

        return r;
    },
});

// Exit light,
// Enter night,
// Take my hand,
// We're off to never never land.
app.$mount('body');
