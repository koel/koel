import Vue from 'vue';
import $ from 'jquery';
import NProgress from 'nprogress';

import { event } from '../utils';
import { ls } from '../services';

/**
 * Responsible for all HTTP requests.
 */
export const http = {
  request(method, url, data, successCb = null, errorCb = null) {
    return $.ajax({
      data,
      dataType: 'json',
      url: `/api/${url}`,
      method: method.toUpperCase(),
      headers: {
        Authorization: `Bearer ${ls.get('jwt-token')}`,
      }
    }).done(successCb).fail(errorCb);
  },

  get(url, successCb = null, errorCb = null) {
    return this.request('get', url, {}, successCb, errorCb);
  },

  post(url, data, successCb = null, errorCb = null) {
    return this.request('post', url, data, successCb, errorCb);
  },

  put(url, data, successCb = null, errorCb = null) {
    return this.request('put', url, data, successCb, errorCb);
  },

  delete(url, data = {}, successCb = null, errorCb = null) {
    return this.request('delete', url, data, successCb, errorCb);
  },

  /**
   * A shortcut method to ping and check if the user session is still valid.
   */
  ping() {
    return this.get('/');
  },

  /**
   * Init the service.
   */
  init() {
    $(document).ajaxComplete((e, r, settings) => {
      NProgress.done();

      if (r.status === 400 || r.status === 401) {
        if (!(settings.method === 'POST' && /\/api\/me\/?/.test(settings.url))) {
          // This is not a failed login. Log out then.
          event.emit('logout');
          return;
        }
      }

      const token = r.getResponseHeader('Authorization');
      if (token) {
        ls.set('jwt-token', token);
      }

      if (r.responseJSON && r.responseJSON.token && r.responseJSON.token.length > 10) {
        ls.set('jwt-token', r.responseJSON.token);
      }
    });
  },
};
