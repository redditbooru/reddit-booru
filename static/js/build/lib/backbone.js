require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
"use strict";

(function () {
  var t = this;var e = t.Backbone;var i = [];var r = i.push;var s = i.slice;var n = i.splice;var a;if (typeof exports !== "undefined") {
    a = exports;
  } else {
    a = t.Backbone = {};
  }a.VERSION = "1.1.0";var h = t._;if (!h && typeof require !== "undefined") h = require("underscore");a.$ = t.jQuery || t.Zepto || t.ender || t.$;a.noConflict = function () {
    t.Backbone = e;return this;
  };a.emulateHTTP = false;a.emulateJSON = false;var o = a.Events = { on: function on(t, e, i) {
      if (!l(this, "on", t, [e, i]) || !e) return this;this._events || (this._events = {});var r = this._events[t] || (this._events[t] = []);r.push({ callback: e, context: i, ctx: i || this });return this;
    }, once: function once(t, e, i) {
      if (!l(this, "once", t, [e, i]) || !e) return this;var r = this;var s = h.once(function () {
        r.off(t, s);e.apply(this, arguments);
      });s._callback = e;return this.on(t, s, i);
    }, off: function off(t, e, i) {
      var r, s, n, a, o, u, c, f;if (!this._events || !l(this, "off", t, [e, i])) return this;if (!t && !e && !i) {
        this._events = {};return this;
      }a = t ? [t] : h.keys(this._events);for (o = 0, u = a.length; o < u; o++) {
        t = a[o];if (n = this._events[t]) {
          this._events[t] = r = [];if (e || i) {
            for (c = 0, f = n.length; c < f; c++) {
              s = n[c];if (e && e !== s.callback && e !== s.callback._callback || i && i !== s.context) {
                r.push(s);
              }
            }
          }if (!r.length) delete this._events[t];
        }
      }return this;
    }, trigger: function trigger(t) {
      if (!this._events) return this;var e = s.call(arguments, 1);if (!l(this, "trigger", t, e)) return this;var i = this._events[t];var r = this._events.all;if (i) c(i, e);if (r) c(r, arguments);return this;
    }, stopListening: function stopListening(t, e, i) {
      var r = this._listeningTo;if (!r) return this;var s = !e && !i;if (!i && typeof e === "object") i = this;if (t) (r = {})[t._listenId] = t;for (var n in r) {
        t = r[n];t.off(e, i, this);if (s || h.isEmpty(t._events)) delete this._listeningTo[n];
      }return this;
    } };var u = /\s+/;var l = function l(t, e, i, r) {
    if (!i) return true;if (typeof i === "object") {
      for (var s in i) {
        t[e].apply(t, [s, i[s]].concat(r));
      }return false;
    }if (u.test(i)) {
      var n = i.split(u);for (var a = 0, h = n.length; a < h; a++) {
        t[e].apply(t, [n[a]].concat(r));
      }return false;
    }return true;
  };var c = function c(t, e) {
    var i,
        r = -1,
        s = t.length,
        n = e[0],
        a = e[1],
        h = e[2];switch (e.length) {case 0:
        while (++r < s) (i = t[r]).callback.call(i.ctx);return;case 1:
        while (++r < s) (i = t[r]).callback.call(i.ctx, n);return;case 2:
        while (++r < s) (i = t[r]).callback.call(i.ctx, n, a);return;case 3:
        while (++r < s) (i = t[r]).callback.call(i.ctx, n, a, h);return;default:
        while (++r < s) (i = t[r]).callback.apply(i.ctx, e);}
  };var f = { listenTo: "on", listenToOnce: "once" };h.each(f, function (t, e) {
    o[e] = function (e, i, r) {
      var s = this._listeningTo || (this._listeningTo = {});var n = e._listenId || (e._listenId = h.uniqueId("l"));s[n] = e;if (!r && typeof i === "object") r = this;e[t](i, r, this);return this;
    };
  });o.bind = o.on;o.unbind = o.off;h.extend(a, o);var d = a.Model = function (t, e) {
    var i = t || {};e || (e = {});this.cid = h.uniqueId("c");this.attributes = {};if (e.collection) this.collection = e.collection;if (e.parse) i = this.parse(i, e) || {};i = h.defaults({}, i, h.result(this, "defaults"));this.set(i, e);this.changed = {};this.initialize.apply(this, arguments);
  };h.extend(d.prototype, o, { changed: null, validationError: null, idAttribute: "id", initialize: function initialize() {}, toJSON: function toJSON(t) {
      return h.clone(this.attributes);
    }, sync: function sync() {
      return a.sync.apply(this, arguments);
    }, get: function get(t) {
      return this.attributes[t];
    }, escape: function escape(t) {
      return h.escape(this.get(t));
    }, has: function has(t) {
      return this.get(t) != null;
    }, set: function set(t, e, i) {
      var r, s, n, a, o, u, l, c;if (t == null) return this;if (typeof t === "object") {
        s = t;i = e;
      } else {
        (s = {})[t] = e;
      }i || (i = {});if (!this._validate(s, i)) return false;n = i.unset;o = i.silent;a = [];u = this._changing;this._changing = true;if (!u) {
        this._previousAttributes = h.clone(this.attributes);this.changed = {};
      }c = this.attributes, l = this._previousAttributes;if (this.idAttribute in s) this.id = s[this.idAttribute];for (r in s) {
        e = s[r];if (!h.isEqual(c[r], e)) a.push(r);if (!h.isEqual(l[r], e)) {
          this.changed[r] = e;
        } else {
          delete this.changed[r];
        }n ? delete c[r] : c[r] = e;
      }if (!o) {
        if (a.length) this._pending = true;for (var f = 0, d = a.length; f < d; f++) {
          this.trigger("change:" + a[f], this, c[a[f]], i);
        }
      }if (u) return this;if (!o) {
        while (this._pending) {
          this._pending = false;this.trigger("change", this, i);
        }
      }this._pending = false;this._changing = false;return this;
    }, unset: function unset(t, e) {
      return this.set(t, void 0, h.extend({}, e, { unset: true }));
    }, clear: function clear(t) {
      var e = {};for (var i in this.attributes) e[i] = void 0;return this.set(e, h.extend({}, t, { unset: true }));
    }, hasChanged: function hasChanged(t) {
      if (t == null) return !h.isEmpty(this.changed);return h.has(this.changed, t);
    }, changedAttributes: function changedAttributes(t) {
      if (!t) return this.hasChanged() ? h.clone(this.changed) : false;var e,
          i = false;var r = this._changing ? this._previousAttributes : this.attributes;for (var s in t) {
        if (h.isEqual(r[s], e = t[s])) continue;(i || (i = {}))[s] = e;
      }return i;
    }, previous: function previous(t) {
      if (t == null || !this._previousAttributes) return null;return this._previousAttributes[t];
    }, previousAttributes: function previousAttributes() {
      return h.clone(this._previousAttributes);
    }, fetch: function fetch(t) {
      t = t ? h.clone(t) : {};if (t.parse === void 0) t.parse = true;var e = this;var i = t.success;t.success = function (r) {
        if (!e.set(e.parse(r, t), t)) return false;if (i) i(e, r, t);e.trigger("sync", e, r, t);
      };M(this, t);return this.sync("read", this, t);
    }, save: function save(t, e, i) {
      var r,
          s,
          n,
          a = this.attributes;if (t == null || typeof t === "object") {
        r = t;i = e;
      } else {
        (r = {})[t] = e;
      }i = h.extend({ validate: true }, i);if (r && !i.wait) {
        if (!this.set(r, i)) return false;
      } else {
        if (!this._validate(r, i)) return false;
      }if (r && i.wait) {
        this.attributes = h.extend({}, a, r);
      }if (i.parse === void 0) i.parse = true;var o = this;var u = i.success;i.success = function (t) {
        o.attributes = a;var e = o.parse(t, i);if (i.wait) e = h.extend(r || {}, e);if (h.isObject(e) && !o.set(e, i)) {
          return false;
        }if (u) u(o, t, i);o.trigger("sync", o, t, i);
      };M(this, i);s = this.isNew() ? "create" : i.patch ? "patch" : "update";if (s === "patch") i.attrs = r;n = this.sync(s, this, i);if (r && i.wait) this.attributes = a;return n;
    }, destroy: function destroy(t) {
      t = t ? h.clone(t) : {};var e = this;var i = t.success;var r = function r() {
        e.trigger("destroy", e, e.collection, t);
      };t.success = function (s) {
        if (t.wait || e.isNew()) r();if (i) i(e, s, t);if (!e.isNew()) e.trigger("sync", e, s, t);
      };if (this.isNew()) {
        t.success();return false;
      }M(this, t);var s = this.sync("delete", this, t);if (!t.wait) r();return s;
    }, url: function url() {
      var t = h.result(this, "urlRoot") || h.result(this.collection, "url") || U();if (this.isNew()) return t;return t + (t.charAt(t.length - 1) === "/" ? "" : "/") + encodeURIComponent(this.id);
    }, parse: function parse(t, e) {
      return t;
    }, clone: function clone() {
      return new this.constructor(this.attributes);
    }, isNew: function isNew() {
      return this.id == null;
    }, isValid: function isValid(t) {
      return this._validate({}, h.extend(t || {}, { validate: true }));
    }, _validate: function _validate(t, e) {
      if (!e.validate || !this.validate) return true;t = h.extend({}, this.attributes, t);var i = this.validationError = this.validate(t, e) || null;if (!i) return true;this.trigger("invalid", this, i, h.extend(e, { validationError: i }));return false;
    } });var p = ["keys", "values", "pairs", "invert", "pick", "omit"];h.each(p, function (t) {
    d.prototype[t] = function () {
      var e = s.call(arguments);e.unshift(this.attributes);return h[t].apply(h, e);
    };
  });var v = a.Collection = function (t, e) {
    e || (e = {});if (e.model) this.model = e.model;if (e.comparator !== void 0) this.comparator = e.comparator;this._reset();this.initialize.apply(this, arguments);if (t) this.reset(t, h.extend({ silent: true }, e));
  };var g = { add: true, remove: true, merge: true };var m = { add: true, remove: false };h.extend(v.prototype, o, { model: d, initialize: function initialize() {}, toJSON: function toJSON(t) {
      return this.map(function (e) {
        return e.toJSON(t);
      });
    }, sync: function sync() {
      return a.sync.apply(this, arguments);
    }, add: function add(t, e) {
      return this.set(t, h.extend({ merge: false }, e, m));
    }, remove: function remove(t, e) {
      var i = !h.isArray(t);t = i ? [t] : h.clone(t);e || (e = {});var r, s, n, a;for (r = 0, s = t.length; r < s; r++) {
        a = t[r] = this.get(t[r]);if (!a) continue;delete this._byId[a.id];delete this._byId[a.cid];n = this.indexOf(a);this.models.splice(n, 1);this.length--;if (!e.silent) {
          e.index = n;a.trigger("remove", a, this, e);
        }this._removeReference(a);
      }return i ? t[0] : t;
    }, set: function set(t, e) {
      e = h.defaults({}, e, g);if (e.parse) t = this.parse(t, e);var i = !h.isArray(t);t = i ? t ? [t] : [] : h.clone(t);var r, s, n, a, o, u, l;var c = e.at;var f = this.model;var p = this.comparator && c == null && e.sort !== false;var v = h.isString(this.comparator) ? this.comparator : null;var m = [],
          y = [],
          _ = {};var w = e.add,
          b = e.merge,
          x = e.remove;var E = !p && w && x ? [] : false;for (r = 0, s = t.length; r < s; r++) {
        o = t[r];if (o instanceof d) {
          n = a = o;
        } else {
          n = o[f.prototype.idAttribute];
        }if (u = this.get(n)) {
          if (x) _[u.cid] = true;if (b) {
            o = o === a ? a.attributes : o;if (e.parse) o = u.parse(o, e);u.set(o, e);if (p && !l && u.hasChanged(v)) l = true;
          }t[r] = u;
        } else if (w) {
          a = t[r] = this._prepareModel(o, e);if (!a) continue;m.push(a);a.on("all", this._onModelEvent, this);this._byId[a.cid] = a;if (a.id != null) this._byId[a.id] = a;
        }if (E) E.push(u || a);
      }if (x) {
        for (r = 0, s = this.length; r < s; ++r) {
          if (!_[(a = this.models[r]).cid]) y.push(a);
        }if (y.length) this.remove(y, e);
      }if (m.length || E && E.length) {
        if (p) l = true;this.length += m.length;if (c != null) {
          for (r = 0, s = m.length; r < s; r++) {
            this.models.splice(c + r, 0, m[r]);
          }
        } else {
          if (E) this.models.length = 0;var T = E || m;for (r = 0, s = T.length; r < s; r++) {
            this.models.push(T[r]);
          }
        }
      }if (l) this.sort({ silent: true });if (!e.silent) {
        for (r = 0, s = m.length; r < s; r++) {
          (a = m[r]).trigger("add", a, this, e);
        }if (l || E && E.length) this.trigger("sort", this, e);
      }return i ? t[0] : t;
    }, reset: function reset(t, e) {
      e || (e = {});for (var i = 0, r = this.models.length; i < r; i++) {
        this._removeReference(this.models[i]);
      }e.previousModels = this.models;this._reset();t = this.add(t, h.extend({ silent: true }, e));if (!e.silent) this.trigger("reset", this, e);return t;
    }, push: function push(t, e) {
      return this.add(t, h.extend({ at: this.length }, e));
    }, pop: function pop(t) {
      var e = this.at(this.length - 1);this.remove(e, t);return e;
    }, unshift: function unshift(t, e) {
      return this.add(t, h.extend({ at: 0 }, e));
    }, shift: function shift(t) {
      var e = this.at(0);this.remove(e, t);return e;
    }, slice: function slice() {
      return s.apply(this.models, arguments);
    }, get: function get(t) {
      if (t == null) return void 0;return this._byId[t.id] || this._byId[t.cid] || this._byId[t];
    }, at: function at(t) {
      return this.models[t];
    }, where: function where(t, e) {
      if (h.isEmpty(t)) return e ? void 0 : [];return this[e ? "find" : "filter"](function (e) {
        for (var i in t) {
          if (t[i] !== e.get(i)) return false;
        }return true;
      });
    }, findWhere: function findWhere(t) {
      return this.where(t, true);
    }, sort: function sort(t) {
      if (!this.comparator) throw new Error("Cannot sort a set without a comparator");t || (t = {});if (h.isString(this.comparator) || this.comparator.length === 1) {
        this.models = this.sortBy(this.comparator, this);
      } else {
        this.models.sort(h.bind(this.comparator, this));
      }if (!t.silent) this.trigger("sort", this, t);return this;
    }, pluck: function pluck(t) {
      return h.invoke(this.models, "get", t);
    }, fetch: function fetch(t) {
      t = t ? h.clone(t) : {};if (t.parse === void 0) t.parse = true;var e = t.success;var i = this;t.success = function (r) {
        var s = t.reset ? "reset" : "set";i[s](r, t);if (e) e(i, r, t);i.trigger("sync", i, r, t);
      };M(this, t);return this.sync("read", this, t);
    }, create: function create(t, e) {
      e = e ? h.clone(e) : {};if (!(t = this._prepareModel(t, e))) return false;if (!e.wait) this.add(t, e);var i = this;var r = e.success;e.success = function (t, e, s) {
        if (s.wait) i.add(t, s);if (r) r(t, e, s);
      };t.save(null, e);return t;
    }, parse: function parse(t, e) {
      return t;
    }, clone: function clone() {
      return new this.constructor(this.models);
    }, _reset: function _reset() {
      this.length = 0;this.models = [];this._byId = {};
    }, _prepareModel: function _prepareModel(t, e) {
      if (t instanceof d) {
        if (!t.collection) t.collection = this;return t;
      }e = e ? h.clone(e) : {};e.collection = this;var i = new this.model(t, e);if (!i.validationError) return i;this.trigger("invalid", this, i.validationError, e);return false;
    }, _removeReference: function _removeReference(t) {
      if (this === t.collection) delete t.collection;t.off("all", this._onModelEvent, this);
    }, _onModelEvent: function _onModelEvent(t, e, i, r) {
      if ((t === "add" || t === "remove") && i !== this) return;if (t === "destroy") this.remove(e, r);if (e && t === "change:" + e.idAttribute) {
        delete this._byId[e.previous(e.idAttribute)];if (e.id != null) this._byId[e.id] = e;
      }this.trigger.apply(this, arguments);
    } });var y = ["forEach", "each", "map", "collect", "reduce", "foldl", "inject", "reduceRight", "foldr", "find", "detect", "filter", "select", "reject", "every", "all", "some", "any", "include", "contains", "invoke", "max", "min", "toArray", "size", "first", "head", "take", "initial", "rest", "tail", "drop", "last", "without", "difference", "indexOf", "shuffle", "lastIndexOf", "isEmpty", "chain"];h.each(y, function (t) {
    v.prototype[t] = function () {
      var e = s.call(arguments);e.unshift(this.models);return h[t].apply(h, e);
    };
  });var _ = ["groupBy", "countBy", "sortBy"];h.each(_, function (t) {
    v.prototype[t] = function (e, i) {
      var r = h.isFunction(e) ? e : function (t) {
        return t.get(e);
      };return h[t](this.models, r, i);
    };
  });var w = a.View = function (t) {
    this.cid = h.uniqueId("view");t || (t = {});h.extend(this, h.pick(t, x));this._ensureElement();this.initialize.apply(this, arguments);this.delegateEvents();
  };var b = /^(\S+)\s*(.*)$/;var x = ["model", "collection", "el", "id", "attributes", "className", "tagName", "events"];h.extend(w.prototype, o, { tagName: "div", $: function $(t) {
      return this.$el.find(t);
    }, initialize: function initialize() {}, render: function render() {
      return this;
    }, remove: function remove() {
      this.$el.remove();this.stopListening();return this;
    }, setElement: function setElement(t, e) {
      if (this.$el) this.undelegateEvents();this.$el = t instanceof a.$ ? t : a.$(t);this.el = this.$el[0];if (e !== false) this.delegateEvents();return this;
    }, delegateEvents: function delegateEvents(t) {
      if (!(t || (t = h.result(this, "events")))) return this;this.undelegateEvents();for (var e in t) {
        var i = t[e];if (!h.isFunction(i)) i = this[t[e]];if (!i) continue;var r = e.match(b);var s = r[1],
            n = r[2];i = h.bind(i, this);s += ".delegateEvents" + this.cid;if (n === "") {
          this.$el.on(s, i);
        } else {
          this.$el.on(s, n, i);
        }
      }return this;
    }, undelegateEvents: function undelegateEvents() {
      this.$el.off(".delegateEvents" + this.cid);return this;
    }, _ensureElement: function _ensureElement() {
      if (!this.el) {
        var t = h.extend({}, h.result(this, "attributes"));if (this.id) t.id = h.result(this, "id");if (this.className) t["class"] = h.result(this, "className");var e = a.$("<" + h.result(this, "tagName") + ">").attr(t);this.setElement(e, false);
      } else {
        this.setElement(h.result(this, "el"), false);
      }
    } });a.sync = function (t, e, i) {
    var r = T[t];h.defaults(i || (i = {}), { emulateHTTP: a.emulateHTTP, emulateJSON: a.emulateJSON });var s = { type: r, dataType: "json" };if (!i.url) {
      s.url = h.result(e, "url") || U();
    }if (i.data == null && e && (t === "create" || t === "update" || t === "patch")) {
      s.contentType = "application/json";s.data = JSON.stringify(i.attrs || e.toJSON(i));
    }if (i.emulateJSON) {
      s.contentType = "application/x-www-form-urlencoded";s.data = s.data ? { model: s.data } : {};
    }if (i.emulateHTTP && (r === "PUT" || r === "DELETE" || r === "PATCH")) {
      s.type = "POST";if (i.emulateJSON) s.data._method = r;var n = i.beforeSend;i.beforeSend = function (t) {
        t.setRequestHeader("X-HTTP-Method-Override", r);if (n) return n.apply(this, arguments);
      };
    }if (s.type !== "GET" && !i.emulateJSON) {
      s.processData = false;
    }if (s.type === "PATCH" && E) {
      s.xhr = function () {
        return new ActiveXObject("Microsoft.XMLHTTP");
      };
    }var o = i.xhr = a.ajax(h.extend(s, i));e.trigger("request", e, o, i);return o;
  };var E = typeof window !== "undefined" && !!window.ActiveXObject && !(window.XMLHttpRequest && new XMLHttpRequest().dispatchEvent);var T = { create: "POST", update: "PUT", patch: "PATCH", "delete": "DELETE", read: "GET" };a.ajax = function () {
    return a.$.ajax.apply(a.$, arguments);
  };var k = a.Router = function (t) {
    t || (t = {});if (t.routes) this.routes = t.routes;this._bindRoutes();this.initialize.apply(this, arguments);
  };var S = /\((.*?)\)/g;var $ = /(\(\?)?:\w+/g;var H = /\*\w+/g;var A = /[\-{}\[\]+?.,\\\^$|#\s]/g;h.extend(k.prototype, o, { initialize: function initialize() {}, route: function route(t, e, i) {
      if (!h.isRegExp(t)) t = this._routeToRegExp(t);if (h.isFunction(e)) {
        i = e;e = "";
      }if (!i) i = this[e];var r = this;a.history.route(t, function (s) {
        var n = r._extractParameters(t, s);i && i.apply(r, n);r.trigger.apply(r, ["route:" + e].concat(n));r.trigger("route", e, n);a.history.trigger("route", r, e, n);
      });return this;
    }, navigate: function navigate(t, e) {
      a.history.navigate(t, e);return this;
    }, _bindRoutes: function _bindRoutes() {
      if (!this.routes) return;this.routes = h.result(this, "routes");var t,
          e = h.keys(this.routes);while ((t = e.pop()) != null) {
        this.route(t, this.routes[t]);
      }
    }, _routeToRegExp: function _routeToRegExp(t) {
      t = t.replace(A, "\\$&").replace(S, "(?:$1)?").replace($, function (t, e) {
        return e ? t : "([^/]+)";
      }).replace(H, "(.*?)");return new RegExp("^" + t + "$");
    }, _extractParameters: function _extractParameters(t, e) {
      var i = t.exec(e).slice(1);return h.map(i, function (t) {
        return t ? decodeURIComponent(t) : null;
      });
    } });var I = a.History = function () {
    this.handlers = [];h.bindAll(this, "checkUrl");if (typeof window !== "undefined") {
      this.location = window.location;this.history = window.history;
    }
  };var N = /^[#\/]|\s+$/g;var O = /^\/+|\/+$/g;var P = /msie [\w.]+/;var C = /\/$/;var j = /[?#].*$/;I.started = false;h.extend(I.prototype, o, { interval: 50, getHash: function getHash(t) {
      var e = (t || this).location.href.match(/#(.*)$/);return e ? e[1] : "";
    }, getFragment: function getFragment(t, e) {
      if (t == null) {
        if (this._hasPushState || !this._wantsHashChange || e) {
          t = this.location.pathname;var i = this.root.replace(C, "");if (!t.indexOf(i)) t = t.slice(i.length);
        } else {
          t = this.getHash();
        }
      }return t.replace(N, "");
    }, start: function start(t) {
      if (I.started) throw new Error("Backbone.history has already been started");I.started = true;this.options = h.extend({ root: "/" }, this.options, t);this.root = this.options.root;this._wantsHashChange = this.options.hashChange !== false;this._wantsPushState = !!this.options.pushState;this._hasPushState = !!(this.options.pushState && this.history && this.history.pushState);var e = this.getFragment();var i = document.documentMode;var r = P.exec(navigator.userAgent.toLowerCase()) && (!i || i <= 7);this.root = ("/" + this.root + "/").replace(O, "/");if (r && this._wantsHashChange) {
        this.iframe = a.$('<iframe src="javascript:0" tabindex="-1" />').hide().appendTo("body")[0].contentWindow;this.navigate(e);
      }if (this._hasPushState) {
        a.$(window).on("popstate", this.checkUrl);
      } else if (this._wantsHashChange && "onhashchange" in window && !r) {
        a.$(window).on("hashchange", this.checkUrl);
      } else if (this._wantsHashChange) {
        this._checkUrlInterval = setInterval(this.checkUrl, this.interval);
      }this.fragment = e;var s = this.location;var n = s.pathname.replace(/[^\/]$/, "$&/") === this.root;if (this._wantsHashChange && this._wantsPushState) {
        if (!this._hasPushState && !n) {
          this.fragment = this.getFragment(null, true);this.location.replace(this.root + this.location.search + "#" + this.fragment);return true;
        } else if (this._hasPushState && n && s.hash) {
          this.fragment = this.getHash().replace(N, "");this.history.replaceState({}, document.title, this.root + this.fragment + s.search);
        }
      }if (!this.options.silent) return this.loadUrl();
    }, stop: function stop() {
      a.$(window).off("popstate", this.checkUrl).off("hashchange", this.checkUrl);clearInterval(this._checkUrlInterval);I.started = false;
    }, route: function route(t, e) {
      this.handlers.unshift({ route: t, callback: e });
    }, checkUrl: function checkUrl(t) {
      var e = this.getFragment();if (e === this.fragment && this.iframe) {
        e = this.getFragment(this.getHash(this.iframe));
      }if (e === this.fragment) return false;if (this.iframe) this.navigate(e);this.loadUrl();
    }, loadUrl: function loadUrl(t) {
      t = this.fragment = this.getFragment(t);return h.any(this.handlers, function (e) {
        if (e.route.test(t)) {
          e.callback(t);return true;
        }
      });
    }, navigate: function navigate(t, e) {
      if (!I.started) return false;if (!e || e === true) e = { trigger: !!e };var i = this.root + (t = this.getFragment(t || ""));t = t.replace(j, "");if (this.fragment === t) return;this.fragment = t;if (t === "" && i !== "/") i = i.slice(0, -1);if (this._hasPushState) {
        this.history[e.replace ? "replaceState" : "pushState"]({}, document.title, i);
      } else if (this._wantsHashChange) {
        this._updateHash(this.location, t, e.replace);if (this.iframe && t !== this.getFragment(this.getHash(this.iframe))) {
          if (!e.replace) this.iframe.document.open().close();this._updateHash(this.iframe.location, t, e.replace);
        }
      } else {
        return this.location.assign(i);
      }if (e.trigger) return this.loadUrl(t);
    }, _updateHash: function _updateHash(t, e, i) {
      if (i) {
        var r = t.href.replace(/(javascript:|#).*$/, "");t.replace(r + "#" + e);
      } else {
        t.hash = "#" + e;
      }
    } });a.history = new I();var R = function R(t, e) {
    var i = this;var r;if (t && h.has(t, "constructor")) {
      r = t.constructor;
    } else {
      r = function () {
        return i.apply(this, arguments);
      };
    }h.extend(r, i, e);var s = function s() {
      this.constructor = r;
    };s.prototype = i.prototype;r.prototype = new s();if (t) h.extend(r.prototype, t);r.__super__ = i.prototype;return r;
  };d.extend = v.extend = k.extend = w.extend = I.extend = R;var U = function U() {
    throw new Error('A "url" property or function must be specified');
  };var M = function M(t, e) {
    var i = e.error;e.error = function (r) {
      if (i) i(t, r, e);t.trigger("error", t, r, e);
    };
  };
}).call(undefined);


},{"underscore":"/node_modules/underscore/underscore.js"}],"/node_modules/underscore/underscore.js":[function(require,module,exports){
//     Underscore.js 1.8.3
//     http://underscorejs.org
//     (c) 2009-2015 Jeremy Ashkenas, DocumentCloud and Investigative Reporters & Editors
//     Underscore may be freely distributed under the MIT license.

(function() {

  // Baseline setup
  // --------------

  // Establish the root object, `window` in the browser, or `exports` on the server.
  var root = this;

  // Save the previous value of the `_` variable.
  var previousUnderscore = root._;

  // Save bytes in the minified (but not gzipped) version:
  var ArrayProto = Array.prototype, ObjProto = Object.prototype, FuncProto = Function.prototype;

  // Create quick reference variables for speed access to core prototypes.
  var
    push             = ArrayProto.push,
    slice            = ArrayProto.slice,
    toString         = ObjProto.toString,
    hasOwnProperty   = ObjProto.hasOwnProperty;

  // All **ECMAScript 5** native function implementations that we hope to use
  // are declared here.
  var
    nativeIsArray      = Array.isArray,
    nativeKeys         = Object.keys,
    nativeBind         = FuncProto.bind,
    nativeCreate       = Object.create;

  // Naked function reference for surrogate-prototype-swapping.
  var Ctor = function(){};

  // Create a safe reference to the Underscore object for use below.
  var _ = function(obj) {
    if (obj instanceof _) return obj;
    if (!(this instanceof _)) return new _(obj);
    this._wrapped = obj;
  };

  // Export the Underscore object for **Node.js**, with
  // backwards-compatibility for the old `require()` API. If we're in
  // the browser, add `_` as a global object.
  if (typeof exports !== 'undefined') {
    if (typeof module !== 'undefined' && module.exports) {
      exports = module.exports = _;
    }
    exports._ = _;
  } else {
    root._ = _;
  }

  // Current version.
  _.VERSION = '1.8.3';

  // Internal function that returns an efficient (for current engines) version
  // of the passed-in callback, to be repeatedly applied in other Underscore
  // functions.
  var optimizeCb = function(func, context, argCount) {
    if (context === void 0) return func;
    switch (argCount == null ? 3 : argCount) {
      case 1: return function(value) {
        return func.call(context, value);
      };
      case 2: return function(value, other) {
        return func.call(context, value, other);
      };
      case 3: return function(value, index, collection) {
        return func.call(context, value, index, collection);
      };
      case 4: return function(accumulator, value, index, collection) {
        return func.call(context, accumulator, value, index, collection);
      };
    }
    return function() {
      return func.apply(context, arguments);
    };
  };

  // A mostly-internal function to generate callbacks that can be applied
  // to each element in a collection, returning the desired result — either
  // identity, an arbitrary callback, a property matcher, or a property accessor.
  var cb = function(value, context, argCount) {
    if (value == null) return _.identity;
    if (_.isFunction(value)) return optimizeCb(value, context, argCount);
    if (_.isObject(value)) return _.matcher(value);
    return _.property(value);
  };
  _.iteratee = function(value, context) {
    return cb(value, context, Infinity);
  };

  // An internal function for creating assigner functions.
  var createAssigner = function(keysFunc, undefinedOnly) {
    return function(obj) {
      var length = arguments.length;
      if (length < 2 || obj == null) return obj;
      for (var index = 1; index < length; index++) {
        var source = arguments[index],
            keys = keysFunc(source),
            l = keys.length;
        for (var i = 0; i < l; i++) {
          var key = keys[i];
          if (!undefinedOnly || obj[key] === void 0) obj[key] = source[key];
        }
      }
      return obj;
    };
  };

  // An internal function for creating a new object that inherits from another.
  var baseCreate = function(prototype) {
    if (!_.isObject(prototype)) return {};
    if (nativeCreate) return nativeCreate(prototype);
    Ctor.prototype = prototype;
    var result = new Ctor;
    Ctor.prototype = null;
    return result;
  };

  var property = function(key) {
    return function(obj) {
      return obj == null ? void 0 : obj[key];
    };
  };

  // Helper for collection methods to determine whether a collection
  // should be iterated as an array or as an object
  // Related: http://people.mozilla.org/~jorendorff/es6-draft.html#sec-tolength
  // Avoids a very nasty iOS 8 JIT bug on ARM-64. #2094
  var MAX_ARRAY_INDEX = Math.pow(2, 53) - 1;
  var getLength = property('length');
  var isArrayLike = function(collection) {
    var length = getLength(collection);
    return typeof length == 'number' && length >= 0 && length <= MAX_ARRAY_INDEX;
  };

  // Collection Functions
  // --------------------

  // The cornerstone, an `each` implementation, aka `forEach`.
  // Handles raw objects in addition to array-likes. Treats all
  // sparse array-likes as if they were dense.
  _.each = _.forEach = function(obj, iteratee, context) {
    iteratee = optimizeCb(iteratee, context);
    var i, length;
    if (isArrayLike(obj)) {
      for (i = 0, length = obj.length; i < length; i++) {
        iteratee(obj[i], i, obj);
      }
    } else {
      var keys = _.keys(obj);
      for (i = 0, length = keys.length; i < length; i++) {
        iteratee(obj[keys[i]], keys[i], obj);
      }
    }
    return obj;
  };

  // Return the results of applying the iteratee to each element.
  _.map = _.collect = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length,
        results = Array(length);
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      results[index] = iteratee(obj[currentKey], currentKey, obj);
    }
    return results;
  };

  // Create a reducing function iterating left or right.
  function createReduce(dir) {
    // Optimized iterator function as using arguments.length
    // in the main function will deoptimize the, see #1991.
    function iterator(obj, iteratee, memo, keys, index, length) {
      for (; index >= 0 && index < length; index += dir) {
        var currentKey = keys ? keys[index] : index;
        memo = iteratee(memo, obj[currentKey], currentKey, obj);
      }
      return memo;
    }

    return function(obj, iteratee, memo, context) {
      iteratee = optimizeCb(iteratee, context, 4);
      var keys = !isArrayLike(obj) && _.keys(obj),
          length = (keys || obj).length,
          index = dir > 0 ? 0 : length - 1;
      // Determine the initial value if none is provided.
      if (arguments.length < 3) {
        memo = obj[keys ? keys[index] : index];
        index += dir;
      }
      return iterator(obj, iteratee, memo, keys, index, length);
    };
  }

  // **Reduce** builds up a single result from a list of values, aka `inject`,
  // or `foldl`.
  _.reduce = _.foldl = _.inject = createReduce(1);

  // The right-associative version of reduce, also known as `foldr`.
  _.reduceRight = _.foldr = createReduce(-1);

  // Return the first value which passes a truth test. Aliased as `detect`.
  _.find = _.detect = function(obj, predicate, context) {
    var key;
    if (isArrayLike(obj)) {
      key = _.findIndex(obj, predicate, context);
    } else {
      key = _.findKey(obj, predicate, context);
    }
    if (key !== void 0 && key !== -1) return obj[key];
  };

  // Return all the elements that pass a truth test.
  // Aliased as `select`.
  _.filter = _.select = function(obj, predicate, context) {
    var results = [];
    predicate = cb(predicate, context);
    _.each(obj, function(value, index, list) {
      if (predicate(value, index, list)) results.push(value);
    });
    return results;
  };

  // Return all the elements for which a truth test fails.
  _.reject = function(obj, predicate, context) {
    return _.filter(obj, _.negate(cb(predicate)), context);
  };

  // Determine whether all of the elements match a truth test.
  // Aliased as `all`.
  _.every = _.all = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length;
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      if (!predicate(obj[currentKey], currentKey, obj)) return false;
    }
    return true;
  };

  // Determine if at least one element in the object matches a truth test.
  // Aliased as `any`.
  _.some = _.any = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = !isArrayLike(obj) && _.keys(obj),
        length = (keys || obj).length;
    for (var index = 0; index < length; index++) {
      var currentKey = keys ? keys[index] : index;
      if (predicate(obj[currentKey], currentKey, obj)) return true;
    }
    return false;
  };

  // Determine if the array or object contains a given item (using `===`).
  // Aliased as `includes` and `include`.
  _.contains = _.includes = _.include = function(obj, item, fromIndex, guard) {
    if (!isArrayLike(obj)) obj = _.values(obj);
    if (typeof fromIndex != 'number' || guard) fromIndex = 0;
    return _.indexOf(obj, item, fromIndex) >= 0;
  };

  // Invoke a method (with arguments) on every item in a collection.
  _.invoke = function(obj, method) {
    var args = slice.call(arguments, 2);
    var isFunc = _.isFunction(method);
    return _.map(obj, function(value) {
      var func = isFunc ? method : value[method];
      return func == null ? func : func.apply(value, args);
    });
  };

  // Convenience version of a common use case of `map`: fetching a property.
  _.pluck = function(obj, key) {
    return _.map(obj, _.property(key));
  };

  // Convenience version of a common use case of `filter`: selecting only objects
  // containing specific `key:value` pairs.
  _.where = function(obj, attrs) {
    return _.filter(obj, _.matcher(attrs));
  };

  // Convenience version of a common use case of `find`: getting the first object
  // containing specific `key:value` pairs.
  _.findWhere = function(obj, attrs) {
    return _.find(obj, _.matcher(attrs));
  };

  // Return the maximum element (or element-based computation).
  _.max = function(obj, iteratee, context) {
    var result = -Infinity, lastComputed = -Infinity,
        value, computed;
    if (iteratee == null && obj != null) {
      obj = isArrayLike(obj) ? obj : _.values(obj);
      for (var i = 0, length = obj.length; i < length; i++) {
        value = obj[i];
        if (value > result) {
          result = value;
        }
      }
    } else {
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index, list) {
        computed = iteratee(value, index, list);
        if (computed > lastComputed || computed === -Infinity && result === -Infinity) {
          result = value;
          lastComputed = computed;
        }
      });
    }
    return result;
  };

  // Return the minimum element (or element-based computation).
  _.min = function(obj, iteratee, context) {
    var result = Infinity, lastComputed = Infinity,
        value, computed;
    if (iteratee == null && obj != null) {
      obj = isArrayLike(obj) ? obj : _.values(obj);
      for (var i = 0, length = obj.length; i < length; i++) {
        value = obj[i];
        if (value < result) {
          result = value;
        }
      }
    } else {
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index, list) {
        computed = iteratee(value, index, list);
        if (computed < lastComputed || computed === Infinity && result === Infinity) {
          result = value;
          lastComputed = computed;
        }
      });
    }
    return result;
  };

  // Shuffle a collection, using the modern version of the
  // [Fisher-Yates shuffle](http://en.wikipedia.org/wiki/Fisher–Yates_shuffle).
  _.shuffle = function(obj) {
    var set = isArrayLike(obj) ? obj : _.values(obj);
    var length = set.length;
    var shuffled = Array(length);
    for (var index = 0, rand; index < length; index++) {
      rand = _.random(0, index);
      if (rand !== index) shuffled[index] = shuffled[rand];
      shuffled[rand] = set[index];
    }
    return shuffled;
  };

  // Sample **n** random values from a collection.
  // If **n** is not specified, returns a single random element.
  // The internal `guard` argument allows it to work with `map`.
  _.sample = function(obj, n, guard) {
    if (n == null || guard) {
      if (!isArrayLike(obj)) obj = _.values(obj);
      return obj[_.random(obj.length - 1)];
    }
    return _.shuffle(obj).slice(0, Math.max(0, n));
  };

  // Sort the object's values by a criterion produced by an iteratee.
  _.sortBy = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    return _.pluck(_.map(obj, function(value, index, list) {
      return {
        value: value,
        index: index,
        criteria: iteratee(value, index, list)
      };
    }).sort(function(left, right) {
      var a = left.criteria;
      var b = right.criteria;
      if (a !== b) {
        if (a > b || a === void 0) return 1;
        if (a < b || b === void 0) return -1;
      }
      return left.index - right.index;
    }), 'value');
  };

  // An internal function used for aggregate "group by" operations.
  var group = function(behavior) {
    return function(obj, iteratee, context) {
      var result = {};
      iteratee = cb(iteratee, context);
      _.each(obj, function(value, index) {
        var key = iteratee(value, index, obj);
        behavior(result, value, key);
      });
      return result;
    };
  };

  // Groups the object's values by a criterion. Pass either a string attribute
  // to group by, or a function that returns the criterion.
  _.groupBy = group(function(result, value, key) {
    if (_.has(result, key)) result[key].push(value); else result[key] = [value];
  });

  // Indexes the object's values by a criterion, similar to `groupBy`, but for
  // when you know that your index values will be unique.
  _.indexBy = group(function(result, value, key) {
    result[key] = value;
  });

  // Counts instances of an object that group by a certain criterion. Pass
  // either a string attribute to count by, or a function that returns the
  // criterion.
  _.countBy = group(function(result, value, key) {
    if (_.has(result, key)) result[key]++; else result[key] = 1;
  });

  // Safely create a real, live array from anything iterable.
  _.toArray = function(obj) {
    if (!obj) return [];
    if (_.isArray(obj)) return slice.call(obj);
    if (isArrayLike(obj)) return _.map(obj, _.identity);
    return _.values(obj);
  };

  // Return the number of elements in an object.
  _.size = function(obj) {
    if (obj == null) return 0;
    return isArrayLike(obj) ? obj.length : _.keys(obj).length;
  };

  // Split a collection into two arrays: one whose elements all satisfy the given
  // predicate, and one whose elements all do not satisfy the predicate.
  _.partition = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var pass = [], fail = [];
    _.each(obj, function(value, key, obj) {
      (predicate(value, key, obj) ? pass : fail).push(value);
    });
    return [pass, fail];
  };

  // Array Functions
  // ---------------

  // Get the first element of an array. Passing **n** will return the first N
  // values in the array. Aliased as `head` and `take`. The **guard** check
  // allows it to work with `_.map`.
  _.first = _.head = _.take = function(array, n, guard) {
    if (array == null) return void 0;
    if (n == null || guard) return array[0];
    return _.initial(array, array.length - n);
  };

  // Returns everything but the last entry of the array. Especially useful on
  // the arguments object. Passing **n** will return all the values in
  // the array, excluding the last N.
  _.initial = function(array, n, guard) {
    return slice.call(array, 0, Math.max(0, array.length - (n == null || guard ? 1 : n)));
  };

  // Get the last element of an array. Passing **n** will return the last N
  // values in the array.
  _.last = function(array, n, guard) {
    if (array == null) return void 0;
    if (n == null || guard) return array[array.length - 1];
    return _.rest(array, Math.max(0, array.length - n));
  };

  // Returns everything but the first entry of the array. Aliased as `tail` and `drop`.
  // Especially useful on the arguments object. Passing an **n** will return
  // the rest N values in the array.
  _.rest = _.tail = _.drop = function(array, n, guard) {
    return slice.call(array, n == null || guard ? 1 : n);
  };

  // Trim out all falsy values from an array.
  _.compact = function(array) {
    return _.filter(array, _.identity);
  };

  // Internal implementation of a recursive `flatten` function.
  var flatten = function(input, shallow, strict, startIndex) {
    var output = [], idx = 0;
    for (var i = startIndex || 0, length = getLength(input); i < length; i++) {
      var value = input[i];
      if (isArrayLike(value) && (_.isArray(value) || _.isArguments(value))) {
        //flatten current level of array or arguments object
        if (!shallow) value = flatten(value, shallow, strict);
        var j = 0, len = value.length;
        output.length += len;
        while (j < len) {
          output[idx++] = value[j++];
        }
      } else if (!strict) {
        output[idx++] = value;
      }
    }
    return output;
  };

  // Flatten out an array, either recursively (by default), or just one level.
  _.flatten = function(array, shallow) {
    return flatten(array, shallow, false);
  };

  // Return a version of the array that does not contain the specified value(s).
  _.without = function(array) {
    return _.difference(array, slice.call(arguments, 1));
  };

  // Produce a duplicate-free version of the array. If the array has already
  // been sorted, you have the option of using a faster algorithm.
  // Aliased as `unique`.
  _.uniq = _.unique = function(array, isSorted, iteratee, context) {
    if (!_.isBoolean(isSorted)) {
      context = iteratee;
      iteratee = isSorted;
      isSorted = false;
    }
    if (iteratee != null) iteratee = cb(iteratee, context);
    var result = [];
    var seen = [];
    for (var i = 0, length = getLength(array); i < length; i++) {
      var value = array[i],
          computed = iteratee ? iteratee(value, i, array) : value;
      if (isSorted) {
        if (!i || seen !== computed) result.push(value);
        seen = computed;
      } else if (iteratee) {
        if (!_.contains(seen, computed)) {
          seen.push(computed);
          result.push(value);
        }
      } else if (!_.contains(result, value)) {
        result.push(value);
      }
    }
    return result;
  };

  // Produce an array that contains the union: each distinct element from all of
  // the passed-in arrays.
  _.union = function() {
    return _.uniq(flatten(arguments, true, true));
  };

  // Produce an array that contains every item shared between all the
  // passed-in arrays.
  _.intersection = function(array) {
    var result = [];
    var argsLength = arguments.length;
    for (var i = 0, length = getLength(array); i < length; i++) {
      var item = array[i];
      if (_.contains(result, item)) continue;
      for (var j = 1; j < argsLength; j++) {
        if (!_.contains(arguments[j], item)) break;
      }
      if (j === argsLength) result.push(item);
    }
    return result;
  };

  // Take the difference between one array and a number of other arrays.
  // Only the elements present in just the first array will remain.
  _.difference = function(array) {
    var rest = flatten(arguments, true, true, 1);
    return _.filter(array, function(value){
      return !_.contains(rest, value);
    });
  };

  // Zip together multiple lists into a single array -- elements that share
  // an index go together.
  _.zip = function() {
    return _.unzip(arguments);
  };

  // Complement of _.zip. Unzip accepts an array of arrays and groups
  // each array's elements on shared indices
  _.unzip = function(array) {
    var length = array && _.max(array, getLength).length || 0;
    var result = Array(length);

    for (var index = 0; index < length; index++) {
      result[index] = _.pluck(array, index);
    }
    return result;
  };

  // Converts lists into objects. Pass either a single array of `[key, value]`
  // pairs, or two parallel arrays of the same length -- one of keys, and one of
  // the corresponding values.
  _.object = function(list, values) {
    var result = {};
    for (var i = 0, length = getLength(list); i < length; i++) {
      if (values) {
        result[list[i]] = values[i];
      } else {
        result[list[i][0]] = list[i][1];
      }
    }
    return result;
  };

  // Generator function to create the findIndex and findLastIndex functions
  function createPredicateIndexFinder(dir) {
    return function(array, predicate, context) {
      predicate = cb(predicate, context);
      var length = getLength(array);
      var index = dir > 0 ? 0 : length - 1;
      for (; index >= 0 && index < length; index += dir) {
        if (predicate(array[index], index, array)) return index;
      }
      return -1;
    };
  }

  // Returns the first index on an array-like that passes a predicate test
  _.findIndex = createPredicateIndexFinder(1);
  _.findLastIndex = createPredicateIndexFinder(-1);

  // Use a comparator function to figure out the smallest index at which
  // an object should be inserted so as to maintain order. Uses binary search.
  _.sortedIndex = function(array, obj, iteratee, context) {
    iteratee = cb(iteratee, context, 1);
    var value = iteratee(obj);
    var low = 0, high = getLength(array);
    while (low < high) {
      var mid = Math.floor((low + high) / 2);
      if (iteratee(array[mid]) < value) low = mid + 1; else high = mid;
    }
    return low;
  };

  // Generator function to create the indexOf and lastIndexOf functions
  function createIndexFinder(dir, predicateFind, sortedIndex) {
    return function(array, item, idx) {
      var i = 0, length = getLength(array);
      if (typeof idx == 'number') {
        if (dir > 0) {
            i = idx >= 0 ? idx : Math.max(idx + length, i);
        } else {
            length = idx >= 0 ? Math.min(idx + 1, length) : idx + length + 1;
        }
      } else if (sortedIndex && idx && length) {
        idx = sortedIndex(array, item);
        return array[idx] === item ? idx : -1;
      }
      if (item !== item) {
        idx = predicateFind(slice.call(array, i, length), _.isNaN);
        return idx >= 0 ? idx + i : -1;
      }
      for (idx = dir > 0 ? i : length - 1; idx >= 0 && idx < length; idx += dir) {
        if (array[idx] === item) return idx;
      }
      return -1;
    };
  }

  // Return the position of the first occurrence of an item in an array,
  // or -1 if the item is not included in the array.
  // If the array is large and already in sort order, pass `true`
  // for **isSorted** to use binary search.
  _.indexOf = createIndexFinder(1, _.findIndex, _.sortedIndex);
  _.lastIndexOf = createIndexFinder(-1, _.findLastIndex);

  // Generate an integer Array containing an arithmetic progression. A port of
  // the native Python `range()` function. See
  // [the Python documentation](http://docs.python.org/library/functions.html#range).
  _.range = function(start, stop, step) {
    if (stop == null) {
      stop = start || 0;
      start = 0;
    }
    step = step || 1;

    var length = Math.max(Math.ceil((stop - start) / step), 0);
    var range = Array(length);

    for (var idx = 0; idx < length; idx++, start += step) {
      range[idx] = start;
    }

    return range;
  };

  // Function (ahem) Functions
  // ------------------

  // Determines whether to execute a function as a constructor
  // or a normal function with the provided arguments
  var executeBound = function(sourceFunc, boundFunc, context, callingContext, args) {
    if (!(callingContext instanceof boundFunc)) return sourceFunc.apply(context, args);
    var self = baseCreate(sourceFunc.prototype);
    var result = sourceFunc.apply(self, args);
    if (_.isObject(result)) return result;
    return self;
  };

  // Create a function bound to a given object (assigning `this`, and arguments,
  // optionally). Delegates to **ECMAScript 5**'s native `Function.bind` if
  // available.
  _.bind = function(func, context) {
    if (nativeBind && func.bind === nativeBind) return nativeBind.apply(func, slice.call(arguments, 1));
    if (!_.isFunction(func)) throw new TypeError('Bind must be called on a function');
    var args = slice.call(arguments, 2);
    var bound = function() {
      return executeBound(func, bound, context, this, args.concat(slice.call(arguments)));
    };
    return bound;
  };

  // Partially apply a function by creating a version that has had some of its
  // arguments pre-filled, without changing its dynamic `this` context. _ acts
  // as a placeholder, allowing any combination of arguments to be pre-filled.
  _.partial = function(func) {
    var boundArgs = slice.call(arguments, 1);
    var bound = function() {
      var position = 0, length = boundArgs.length;
      var args = Array(length);
      for (var i = 0; i < length; i++) {
        args[i] = boundArgs[i] === _ ? arguments[position++] : boundArgs[i];
      }
      while (position < arguments.length) args.push(arguments[position++]);
      return executeBound(func, bound, this, this, args);
    };
    return bound;
  };

  // Bind a number of an object's methods to that object. Remaining arguments
  // are the method names to be bound. Useful for ensuring that all callbacks
  // defined on an object belong to it.
  _.bindAll = function(obj) {
    var i, length = arguments.length, key;
    if (length <= 1) throw new Error('bindAll must be passed function names');
    for (i = 1; i < length; i++) {
      key = arguments[i];
      obj[key] = _.bind(obj[key], obj);
    }
    return obj;
  };

  // Memoize an expensive function by storing its results.
  _.memoize = function(func, hasher) {
    var memoize = function(key) {
      var cache = memoize.cache;
      var address = '' + (hasher ? hasher.apply(this, arguments) : key);
      if (!_.has(cache, address)) cache[address] = func.apply(this, arguments);
      return cache[address];
    };
    memoize.cache = {};
    return memoize;
  };

  // Delays a function for the given number of milliseconds, and then calls
  // it with the arguments supplied.
  _.delay = function(func, wait) {
    var args = slice.call(arguments, 2);
    return setTimeout(function(){
      return func.apply(null, args);
    }, wait);
  };

  // Defers a function, scheduling it to run after the current call stack has
  // cleared.
  _.defer = _.partial(_.delay, _, 1);

  // Returns a function, that, when invoked, will only be triggered at most once
  // during a given window of time. Normally, the throttled function will run
  // as much as it can, without ever going more than once per `wait` duration;
  // but if you'd like to disable the execution on the leading edge, pass
  // `{leading: false}`. To disable execution on the trailing edge, ditto.
  _.throttle = function(func, wait, options) {
    var context, args, result;
    var timeout = null;
    var previous = 0;
    if (!options) options = {};
    var later = function() {
      previous = options.leading === false ? 0 : _.now();
      timeout = null;
      result = func.apply(context, args);
      if (!timeout) context = args = null;
    };
    return function() {
      var now = _.now();
      if (!previous && options.leading === false) previous = now;
      var remaining = wait - (now - previous);
      context = this;
      args = arguments;
      if (remaining <= 0 || remaining > wait) {
        if (timeout) {
          clearTimeout(timeout);
          timeout = null;
        }
        previous = now;
        result = func.apply(context, args);
        if (!timeout) context = args = null;
      } else if (!timeout && options.trailing !== false) {
        timeout = setTimeout(later, remaining);
      }
      return result;
    };
  };

  // Returns a function, that, as long as it continues to be invoked, will not
  // be triggered. The function will be called after it stops being called for
  // N milliseconds. If `immediate` is passed, trigger the function on the
  // leading edge, instead of the trailing.
  _.debounce = function(func, wait, immediate) {
    var timeout, args, context, timestamp, result;

    var later = function() {
      var last = _.now() - timestamp;

      if (last < wait && last >= 0) {
        timeout = setTimeout(later, wait - last);
      } else {
        timeout = null;
        if (!immediate) {
          result = func.apply(context, args);
          if (!timeout) context = args = null;
        }
      }
    };

    return function() {
      context = this;
      args = arguments;
      timestamp = _.now();
      var callNow = immediate && !timeout;
      if (!timeout) timeout = setTimeout(later, wait);
      if (callNow) {
        result = func.apply(context, args);
        context = args = null;
      }

      return result;
    };
  };

  // Returns the first function passed as an argument to the second,
  // allowing you to adjust arguments, run code before and after, and
  // conditionally execute the original function.
  _.wrap = function(func, wrapper) {
    return _.partial(wrapper, func);
  };

  // Returns a negated version of the passed-in predicate.
  _.negate = function(predicate) {
    return function() {
      return !predicate.apply(this, arguments);
    };
  };

  // Returns a function that is the composition of a list of functions, each
  // consuming the return value of the function that follows.
  _.compose = function() {
    var args = arguments;
    var start = args.length - 1;
    return function() {
      var i = start;
      var result = args[start].apply(this, arguments);
      while (i--) result = args[i].call(this, result);
      return result;
    };
  };

  // Returns a function that will only be executed on and after the Nth call.
  _.after = function(times, func) {
    return function() {
      if (--times < 1) {
        return func.apply(this, arguments);
      }
    };
  };

  // Returns a function that will only be executed up to (but not including) the Nth call.
  _.before = function(times, func) {
    var memo;
    return function() {
      if (--times > 0) {
        memo = func.apply(this, arguments);
      }
      if (times <= 1) func = null;
      return memo;
    };
  };

  // Returns a function that will be executed at most one time, no matter how
  // often you call it. Useful for lazy initialization.
  _.once = _.partial(_.before, 2);

  // Object Functions
  // ----------------

  // Keys in IE < 9 that won't be iterated by `for key in ...` and thus missed.
  var hasEnumBug = !{toString: null}.propertyIsEnumerable('toString');
  var nonEnumerableProps = ['valueOf', 'isPrototypeOf', 'toString',
                      'propertyIsEnumerable', 'hasOwnProperty', 'toLocaleString'];

  function collectNonEnumProps(obj, keys) {
    var nonEnumIdx = nonEnumerableProps.length;
    var constructor = obj.constructor;
    var proto = (_.isFunction(constructor) && constructor.prototype) || ObjProto;

    // Constructor is a special case.
    var prop = 'constructor';
    if (_.has(obj, prop) && !_.contains(keys, prop)) keys.push(prop);

    while (nonEnumIdx--) {
      prop = nonEnumerableProps[nonEnumIdx];
      if (prop in obj && obj[prop] !== proto[prop] && !_.contains(keys, prop)) {
        keys.push(prop);
      }
    }
  }

  // Retrieve the names of an object's own properties.
  // Delegates to **ECMAScript 5**'s native `Object.keys`
  _.keys = function(obj) {
    if (!_.isObject(obj)) return [];
    if (nativeKeys) return nativeKeys(obj);
    var keys = [];
    for (var key in obj) if (_.has(obj, key)) keys.push(key);
    // Ahem, IE < 9.
    if (hasEnumBug) collectNonEnumProps(obj, keys);
    return keys;
  };

  // Retrieve all the property names of an object.
  _.allKeys = function(obj) {
    if (!_.isObject(obj)) return [];
    var keys = [];
    for (var key in obj) keys.push(key);
    // Ahem, IE < 9.
    if (hasEnumBug) collectNonEnumProps(obj, keys);
    return keys;
  };

  // Retrieve the values of an object's properties.
  _.values = function(obj) {
    var keys = _.keys(obj);
    var length = keys.length;
    var values = Array(length);
    for (var i = 0; i < length; i++) {
      values[i] = obj[keys[i]];
    }
    return values;
  };

  // Returns the results of applying the iteratee to each element of the object
  // In contrast to _.map it returns an object
  _.mapObject = function(obj, iteratee, context) {
    iteratee = cb(iteratee, context);
    var keys =  _.keys(obj),
          length = keys.length,
          results = {},
          currentKey;
      for (var index = 0; index < length; index++) {
        currentKey = keys[index];
        results[currentKey] = iteratee(obj[currentKey], currentKey, obj);
      }
      return results;
  };

  // Convert an object into a list of `[key, value]` pairs.
  _.pairs = function(obj) {
    var keys = _.keys(obj);
    var length = keys.length;
    var pairs = Array(length);
    for (var i = 0; i < length; i++) {
      pairs[i] = [keys[i], obj[keys[i]]];
    }
    return pairs;
  };

  // Invert the keys and values of an object. The values must be serializable.
  _.invert = function(obj) {
    var result = {};
    var keys = _.keys(obj);
    for (var i = 0, length = keys.length; i < length; i++) {
      result[obj[keys[i]]] = keys[i];
    }
    return result;
  };

  // Return a sorted list of the function names available on the object.
  // Aliased as `methods`
  _.functions = _.methods = function(obj) {
    var names = [];
    for (var key in obj) {
      if (_.isFunction(obj[key])) names.push(key);
    }
    return names.sort();
  };

  // Extend a given object with all the properties in passed-in object(s).
  _.extend = createAssigner(_.allKeys);

  // Assigns a given object with all the own properties in the passed-in object(s)
  // (https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Object/assign)
  _.extendOwn = _.assign = createAssigner(_.keys);

  // Returns the first key on an object that passes a predicate test
  _.findKey = function(obj, predicate, context) {
    predicate = cb(predicate, context);
    var keys = _.keys(obj), key;
    for (var i = 0, length = keys.length; i < length; i++) {
      key = keys[i];
      if (predicate(obj[key], key, obj)) return key;
    }
  };

  // Return a copy of the object only containing the whitelisted properties.
  _.pick = function(object, oiteratee, context) {
    var result = {}, obj = object, iteratee, keys;
    if (obj == null) return result;
    if (_.isFunction(oiteratee)) {
      keys = _.allKeys(obj);
      iteratee = optimizeCb(oiteratee, context);
    } else {
      keys = flatten(arguments, false, false, 1);
      iteratee = function(value, key, obj) { return key in obj; };
      obj = Object(obj);
    }
    for (var i = 0, length = keys.length; i < length; i++) {
      var key = keys[i];
      var value = obj[key];
      if (iteratee(value, key, obj)) result[key] = value;
    }
    return result;
  };

   // Return a copy of the object without the blacklisted properties.
  _.omit = function(obj, iteratee, context) {
    if (_.isFunction(iteratee)) {
      iteratee = _.negate(iteratee);
    } else {
      var keys = _.map(flatten(arguments, false, false, 1), String);
      iteratee = function(value, key) {
        return !_.contains(keys, key);
      };
    }
    return _.pick(obj, iteratee, context);
  };

  // Fill in a given object with default properties.
  _.defaults = createAssigner(_.allKeys, true);

  // Creates an object that inherits from the given prototype object.
  // If additional properties are provided then they will be added to the
  // created object.
  _.create = function(prototype, props) {
    var result = baseCreate(prototype);
    if (props) _.extendOwn(result, props);
    return result;
  };

  // Create a (shallow-cloned) duplicate of an object.
  _.clone = function(obj) {
    if (!_.isObject(obj)) return obj;
    return _.isArray(obj) ? obj.slice() : _.extend({}, obj);
  };

  // Invokes interceptor with the obj, and then returns obj.
  // The primary purpose of this method is to "tap into" a method chain, in
  // order to perform operations on intermediate results within the chain.
  _.tap = function(obj, interceptor) {
    interceptor(obj);
    return obj;
  };

  // Returns whether an object has a given set of `key:value` pairs.
  _.isMatch = function(object, attrs) {
    var keys = _.keys(attrs), length = keys.length;
    if (object == null) return !length;
    var obj = Object(object);
    for (var i = 0; i < length; i++) {
      var key = keys[i];
      if (attrs[key] !== obj[key] || !(key in obj)) return false;
    }
    return true;
  };


  // Internal recursive comparison function for `isEqual`.
  var eq = function(a, b, aStack, bStack) {
    // Identical objects are equal. `0 === -0`, but they aren't identical.
    // See the [Harmony `egal` proposal](http://wiki.ecmascript.org/doku.php?id=harmony:egal).
    if (a === b) return a !== 0 || 1 / a === 1 / b;
    // A strict comparison is necessary because `null == undefined`.
    if (a == null || b == null) return a === b;
    // Unwrap any wrapped objects.
    if (a instanceof _) a = a._wrapped;
    if (b instanceof _) b = b._wrapped;
    // Compare `[[Class]]` names.
    var className = toString.call(a);
    if (className !== toString.call(b)) return false;
    switch (className) {
      // Strings, numbers, regular expressions, dates, and booleans are compared by value.
      case '[object RegExp]':
      // RegExps are coerced to strings for comparison (Note: '' + /a/i === '/a/i')
      case '[object String]':
        // Primitives and their corresponding object wrappers are equivalent; thus, `"5"` is
        // equivalent to `new String("5")`.
        return '' + a === '' + b;
      case '[object Number]':
        // `NaN`s are equivalent, but non-reflexive.
        // Object(NaN) is equivalent to NaN
        if (+a !== +a) return +b !== +b;
        // An `egal` comparison is performed for other numeric values.
        return +a === 0 ? 1 / +a === 1 / b : +a === +b;
      case '[object Date]':
      case '[object Boolean]':
        // Coerce dates and booleans to numeric primitive values. Dates are compared by their
        // millisecond representations. Note that invalid dates with millisecond representations
        // of `NaN` are not equivalent.
        return +a === +b;
    }

    var areArrays = className === '[object Array]';
    if (!areArrays) {
      if (typeof a != 'object' || typeof b != 'object') return false;

      // Objects with different constructors are not equivalent, but `Object`s or `Array`s
      // from different frames are.
      var aCtor = a.constructor, bCtor = b.constructor;
      if (aCtor !== bCtor && !(_.isFunction(aCtor) && aCtor instanceof aCtor &&
                               _.isFunction(bCtor) && bCtor instanceof bCtor)
                          && ('constructor' in a && 'constructor' in b)) {
        return false;
      }
    }
    // Assume equality for cyclic structures. The algorithm for detecting cyclic
    // structures is adapted from ES 5.1 section 15.12.3, abstract operation `JO`.

    // Initializing stack of traversed objects.
    // It's done here since we only need them for objects and arrays comparison.
    aStack = aStack || [];
    bStack = bStack || [];
    var length = aStack.length;
    while (length--) {
      // Linear search. Performance is inversely proportional to the number of
      // unique nested structures.
      if (aStack[length] === a) return bStack[length] === b;
    }

    // Add the first object to the stack of traversed objects.
    aStack.push(a);
    bStack.push(b);

    // Recursively compare objects and arrays.
    if (areArrays) {
      // Compare array lengths to determine if a deep comparison is necessary.
      length = a.length;
      if (length !== b.length) return false;
      // Deep compare the contents, ignoring non-numeric properties.
      while (length--) {
        if (!eq(a[length], b[length], aStack, bStack)) return false;
      }
    } else {
      // Deep compare objects.
      var keys = _.keys(a), key;
      length = keys.length;
      // Ensure that both objects contain the same number of properties before comparing deep equality.
      if (_.keys(b).length !== length) return false;
      while (length--) {
        // Deep compare each member
        key = keys[length];
        if (!(_.has(b, key) && eq(a[key], b[key], aStack, bStack))) return false;
      }
    }
    // Remove the first object from the stack of traversed objects.
    aStack.pop();
    bStack.pop();
    return true;
  };

  // Perform a deep comparison to check if two objects are equal.
  _.isEqual = function(a, b) {
    return eq(a, b);
  };

  // Is a given array, string, or object empty?
  // An "empty" object has no enumerable own-properties.
  _.isEmpty = function(obj) {
    if (obj == null) return true;
    if (isArrayLike(obj) && (_.isArray(obj) || _.isString(obj) || _.isArguments(obj))) return obj.length === 0;
    return _.keys(obj).length === 0;
  };

  // Is a given value a DOM element?
  _.isElement = function(obj) {
    return !!(obj && obj.nodeType === 1);
  };

  // Is a given value an array?
  // Delegates to ECMA5's native Array.isArray
  _.isArray = nativeIsArray || function(obj) {
    return toString.call(obj) === '[object Array]';
  };

  // Is a given variable an object?
  _.isObject = function(obj) {
    var type = typeof obj;
    return type === 'function' || type === 'object' && !!obj;
  };

  // Add some isType methods: isArguments, isFunction, isString, isNumber, isDate, isRegExp, isError.
  _.each(['Arguments', 'Function', 'String', 'Number', 'Date', 'RegExp', 'Error'], function(name) {
    _['is' + name] = function(obj) {
      return toString.call(obj) === '[object ' + name + ']';
    };
  });

  // Define a fallback version of the method in browsers (ahem, IE < 9), where
  // there isn't any inspectable "Arguments" type.
  if (!_.isArguments(arguments)) {
    _.isArguments = function(obj) {
      return _.has(obj, 'callee');
    };
  }

  // Optimize `isFunction` if appropriate. Work around some typeof bugs in old v8,
  // IE 11 (#1621), and in Safari 8 (#1929).
  if (typeof /./ != 'function' && typeof Int8Array != 'object') {
    _.isFunction = function(obj) {
      return typeof obj == 'function' || false;
    };
  }

  // Is a given object a finite number?
  _.isFinite = function(obj) {
    return isFinite(obj) && !isNaN(parseFloat(obj));
  };

  // Is the given value `NaN`? (NaN is the only number which does not equal itself).
  _.isNaN = function(obj) {
    return _.isNumber(obj) && obj !== +obj;
  };

  // Is a given value a boolean?
  _.isBoolean = function(obj) {
    return obj === true || obj === false || toString.call(obj) === '[object Boolean]';
  };

  // Is a given value equal to null?
  _.isNull = function(obj) {
    return obj === null;
  };

  // Is a given variable undefined?
  _.isUndefined = function(obj) {
    return obj === void 0;
  };

  // Shortcut function for checking if an object has a given property directly
  // on itself (in other words, not on a prototype).
  _.has = function(obj, key) {
    return obj != null && hasOwnProperty.call(obj, key);
  };

  // Utility Functions
  // -----------------

  // Run Underscore.js in *noConflict* mode, returning the `_` variable to its
  // previous owner. Returns a reference to the Underscore object.
  _.noConflict = function() {
    root._ = previousUnderscore;
    return this;
  };

  // Keep the identity function around for default iteratees.
  _.identity = function(value) {
    return value;
  };

  // Predicate-generating functions. Often useful outside of Underscore.
  _.constant = function(value) {
    return function() {
      return value;
    };
  };

  _.noop = function(){};

  _.property = property;

  // Generates a function for a given object that returns a given property.
  _.propertyOf = function(obj) {
    return obj == null ? function(){} : function(key) {
      return obj[key];
    };
  };

  // Returns a predicate for checking whether an object has a given set of
  // `key:value` pairs.
  _.matcher = _.matches = function(attrs) {
    attrs = _.extendOwn({}, attrs);
    return function(obj) {
      return _.isMatch(obj, attrs);
    };
  };

  // Run a function **n** times.
  _.times = function(n, iteratee, context) {
    var accum = Array(Math.max(0, n));
    iteratee = optimizeCb(iteratee, context, 1);
    for (var i = 0; i < n; i++) accum[i] = iteratee(i);
    return accum;
  };

  // Return a random integer between min and max (inclusive).
  _.random = function(min, max) {
    if (max == null) {
      max = min;
      min = 0;
    }
    return min + Math.floor(Math.random() * (max - min + 1));
  };

  // A (possibly faster) way to get the current timestamp as an integer.
  _.now = Date.now || function() {
    return new Date().getTime();
  };

   // List of HTML entities for escaping.
  var escapeMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#x27;',
    '`': '&#x60;'
  };
  var unescapeMap = _.invert(escapeMap);

  // Functions for escaping and unescaping strings to/from HTML interpolation.
  var createEscaper = function(map) {
    var escaper = function(match) {
      return map[match];
    };
    // Regexes for identifying a key that needs to be escaped
    var source = '(?:' + _.keys(map).join('|') + ')';
    var testRegexp = RegExp(source);
    var replaceRegexp = RegExp(source, 'g');
    return function(string) {
      string = string == null ? '' : '' + string;
      return testRegexp.test(string) ? string.replace(replaceRegexp, escaper) : string;
    };
  };
  _.escape = createEscaper(escapeMap);
  _.unescape = createEscaper(unescapeMap);

  // If the value of the named `property` is a function then invoke it with the
  // `object` as context; otherwise, return it.
  _.result = function(object, property, fallback) {
    var value = object == null ? void 0 : object[property];
    if (value === void 0) {
      value = fallback;
    }
    return _.isFunction(value) ? value.call(object) : value;
  };

  // Generate a unique integer id (unique within the entire client session).
  // Useful for temporary DOM ids.
  var idCounter = 0;
  _.uniqueId = function(prefix) {
    var id = ++idCounter + '';
    return prefix ? prefix + id : id;
  };

  // By default, Underscore uses ERB-style template delimiters, change the
  // following template settings to use alternative delimiters.
  _.templateSettings = {
    evaluate    : /<%([\s\S]+?)%>/g,
    interpolate : /<%=([\s\S]+?)%>/g,
    escape      : /<%-([\s\S]+?)%>/g
  };

  // When customizing `templateSettings`, if you don't want to define an
  // interpolation, evaluation or escaping regex, we need one that is
  // guaranteed not to match.
  var noMatch = /(.)^/;

  // Certain characters need to be escaped so that they can be put into a
  // string literal.
  var escapes = {
    "'":      "'",
    '\\':     '\\',
    '\r':     'r',
    '\n':     'n',
    '\u2028': 'u2028',
    '\u2029': 'u2029'
  };

  var escaper = /\\|'|\r|\n|\u2028|\u2029/g;

  var escapeChar = function(match) {
    return '\\' + escapes[match];
  };

  // JavaScript micro-templating, similar to John Resig's implementation.
  // Underscore templating handles arbitrary delimiters, preserves whitespace,
  // and correctly escapes quotes within interpolated code.
  // NB: `oldSettings` only exists for backwards compatibility.
  _.template = function(text, settings, oldSettings) {
    if (!settings && oldSettings) settings = oldSettings;
    settings = _.defaults({}, settings, _.templateSettings);

    // Combine delimiters into one regular expression via alternation.
    var matcher = RegExp([
      (settings.escape || noMatch).source,
      (settings.interpolate || noMatch).source,
      (settings.evaluate || noMatch).source
    ].join('|') + '|$', 'g');

    // Compile the template source, escaping string literals appropriately.
    var index = 0;
    var source = "__p+='";
    text.replace(matcher, function(match, escape, interpolate, evaluate, offset) {
      source += text.slice(index, offset).replace(escaper, escapeChar);
      index = offset + match.length;

      if (escape) {
        source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
      } else if (interpolate) {
        source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
      } else if (evaluate) {
        source += "';\n" + evaluate + "\n__p+='";
      }

      // Adobe VMs need the match returned to produce the correct offest.
      return match;
    });
    source += "';\n";

    // If a variable is not specified, place data values in local scope.
    if (!settings.variable) source = 'with(obj||{}){\n' + source + '}\n';

    source = "var __t,__p='',__j=Array.prototype.join," +
      "print=function(){__p+=__j.call(arguments,'');};\n" +
      source + 'return __p;\n';

    try {
      var render = new Function(settings.variable || 'obj', '_', source);
    } catch (e) {
      e.source = source;
      throw e;
    }

    var template = function(data) {
      return render.call(this, data, _);
    };

    // Provide the compiled source as a convenience for precompilation.
    var argument = settings.variable || 'obj';
    template.source = 'function(' + argument + '){\n' + source + '}';

    return template;
  };

  // Add a "chain" function. Start chaining a wrapped Underscore object.
  _.chain = function(obj) {
    var instance = _(obj);
    instance._chain = true;
    return instance;
  };

  // OOP
  // ---------------
  // If Underscore is called as a function, it returns a wrapped object that
  // can be used OO-style. This wrapper holds altered versions of all the
  // underscore functions. Wrapped objects may be chained.

  // Helper function to continue chaining intermediate results.
  var result = function(instance, obj) {
    return instance._chain ? _(obj).chain() : obj;
  };

  // Add your own custom functions to the Underscore object.
  _.mixin = function(obj) {
    _.each(_.functions(obj), function(name) {
      var func = _[name] = obj[name];
      _.prototype[name] = function() {
        var args = [this._wrapped];
        push.apply(args, arguments);
        return result(this, func.apply(_, args));
      };
    });
  };

  // Add all of the Underscore functions to the wrapper object.
  _.mixin(_);

  // Add all mutator Array functions to the wrapper.
  _.each(['pop', 'push', 'reverse', 'shift', 'sort', 'splice', 'unshift'], function(name) {
    var method = ArrayProto[name];
    _.prototype[name] = function() {
      var obj = this._wrapped;
      method.apply(obj, arguments);
      if ((name === 'shift' || name === 'splice') && obj.length === 0) delete obj[0];
      return result(this, obj);
    };
  });

  // Add all accessor Array functions to the wrapper.
  _.each(['concat', 'join', 'slice'], function(name) {
    var method = ArrayProto[name];
    _.prototype[name] = function() {
      return result(this, method.apply(this._wrapped, arguments));
    };
  });

  // Extracts the result from a wrapped and chained object.
  _.prototype.value = function() {
    return this._wrapped;
  };

  // Provide unwrapping proxy for some methods used in engine operations
  // such as arithmetic and JSON stringification.
  _.prototype.valueOf = _.prototype.toJSON = _.prototype.value;

  _.prototype.toString = function() {
    return '' + this._wrapped;
  };

  // AMD registration happens at the end for compatibility with AMD loaders
  // that may not enforce next-turn semantics on modules. Even though general
  // practice for AMD registration is to be anonymous, underscore registers
  // as a named module because, like jQuery, it is a base library that is
  // popular enough to be bundled in a third party lib, but not be part of
  // an AMD load request. Those cases could generate an error when an
  // anonymous define() is called outside of a loader request.
  if (typeof define === 'function' && define.amd) {
    define('underscore', [], function() {
      return _;
    });
  }
}.call(this));

},{}]},{},[1]);
