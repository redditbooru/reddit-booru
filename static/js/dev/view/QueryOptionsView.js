/**
 * QueryOptions view
 */
(function(undefined) {

    var EVT_UPDATE = 'update';

    RB.QueryOptionsView = Backbone.View.extend({

        collection: null,
        $el: null,
        template: RB.Templates.queryOptionItem,

        events: {
            'change .queryOption': 'handleQueryOptionChange'
        },

        initialize: function($el, collection) {
            this.collection = collection;
            this.$el = $el;
            this.name = $el.attr('id');
            this.render();
            _.extend(this, Backbone.Events);
        },

        render: function() {
            var tplData = {
                type: 'checkbox',
                items: this.collection.toJSON(),
                name: this.name
            };
            this.$el.html(this.template(tplData));
        },

        handleQueryOptionChange: function(evt) {
            var value = evt.target.value,
                checked = evt.target.checked,
                item = this._getItemForValue(value);
            
            if (null !== item) {
                item.attributes.checked = checked;
                this.trigger(EVT_UPDATE, item);
            }
        },

        _getItemForValue: function(value) {
            var retVal = null;
            this.collection.each(function(item) {
                if (item.attributes.value == value) {
                    retVal = item;
                }
            });
            return retVal;
        }

    });

}());