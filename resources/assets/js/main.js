import $ from 'jquery';

import ls from './services/ls';

window.Vue = require('vue');
Vue.config.debug = false;
Vue.use(require('vue-resource'));
Vue.http.options.root = '/api';
Vue.http.interceptors.push({
    request(request) {
        var token = ls.get('jwt-token');

        if (token) {
            Vue.http.headers.common.Authorization = token;
        }

        return request;
    },

    response(response) {
        if (response.status && response.status.code == 401) {
            ls.remove('jwt-token');
        }

        if (response.headers && response.headers.Authorization) {
            ls.set('jwt-token', response.headers.Authorization);
        }

        if (response.data && response.data.token && response.data.token.length > 10) {
            ls.set('jwt-token', `Bearer ${response.data.token}`);
        }

        return response;
    },
});

// Exit light,
// Enter night,
// Take my hand,
// We're off to never never land.
new Vue(require('./app.vue')).$mount('body');
