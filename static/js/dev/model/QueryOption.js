/**
 * Query option
 */
(function(undefined) {

    RB.QueryOption = Backbone.Model.extend({
        defaults: function() {
            return {
                title: '',
                value: '',
                name: '',
                checked: false
            };
        }
    });

}());