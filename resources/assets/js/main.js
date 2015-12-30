import $ from 'jquery';

import ls from './services/ls';

window.Vue = require('vue');
var app = new Vue(require('./app.vue'));

Vue.config.debug = false;
Vue.use(require('vue-resource'));
Vue.http.options.root = '/api';
Vue.http.interceptors.push({
    request(request) {
        var token = ls.get('jwt-token');

        if (token) {
            Vue.http.headers.common.Authorization = `Bearer ${token}`;
        }

        return request;
    },

    response(response) {
        if (response.status === 400 || response.status === 401) {
            app.logout();
        }

        if (response.headers && response.headers.Authorization) {
            ls.set('jwt-token', response.headers.Authorization);
        }

        if (response.data && response.data.token && response.data.token.length > 10) {
            ls.set('jwt-token', response.data.token);
        }

        return response;
    },
});

// Exit light,
// Enter night,
// Take my hand,
// We're off to never never land.
app.$mount('body');
