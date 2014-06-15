/**
 * RedditBooru routes
 */
(function(undefined) {
    RB.Router = function(handleCurrentState) {

        this._routes = {};
        this._supportsHistory = 'history' in window;

        if (this._supportsHistory) {
            window.addEventListener('popstate', function(evt) {

            });
        }

    };

    RB.Router.prototype.addRoute = function(name, path, callback) {

        this._routes[name] = {
            path: path,
            callbacks: []
        };

        if (typeof callback === 'function') {
            this._routes[name].callbacks.push(callback);
        }

    };

    RB.Router.prototype.on = function(route, callback) {
        if (route in this._routes) {
            this._routes[route].callbacks.push(callback);
        }
    };

    RB.Router.prototype.go = function(route, params) {
        if (route in this._routes) {

            var url = this._routes[route].path,
                oldUrl = url,
                qs = [];

            _.each(this._routes[route].callbacks, function(callback) {
                var addParams = callback(params);

                // If the callback returned additional parameters, add them to the list
                // so that all values are correctly represented in the final URL
                if (typeof addParams === 'object') {
                    _.defaults(params, addParams);
                }
            });

            if (this._supportsHistory) {
                _.each(params, function(value, key) {
                    url = url.replace(new RegExp('(\\*|\\:)' + key), value);

                    // If the parameter wasn't found as part of the route, throw it on the query string
                    if (oldUrl === url) {
                        qs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    }

                    oldUrl = url;
                });

                url = qs.length ? url + '?' + qs.join('&') : url;
                window.history.pushState({
                    route: route,
                    params: params
                }, null, url);
            }

        }
    };

}());