/**
 * Filters View
 */
(function(undefined) {

    var EVT_UPDATE = 'update';

    RB.FiltersView = Backbone.View.extend({

        collection: null,
        $el: null,
        $sources: null,
        $sizes: null,
        template: RB.Templates.queryOptionItem,

        events: {
            'click button': 'handleRefreshClick'
        },

        initialize: function($el, collection) {
            this.collection = collection;
            this.$el = $el;
            this.$sources = this.$el.find('[name="sources"]');
            this.$sizes = this.$el.find('[name="sizes"]');
            this.render();
            _.extend(this, Backbone.Events);
        },

        handleRefreshClick: function(evt) {
            console.log(evt);
            var values = {};
            var changed = false;
            this.$sources.each(function(index, item) {
                values[item.getAttribute('value')] = !!item.checked;
            });

            this.collection.each(function(item) {
                var value = values[item.attributes.value];
                changed = changed || item.attributes.checked === value;
                item.attributes.checked = value;
            });

            if (changed) {
                this.trigger(EVT_UPDATE);
            }


        }

    });

}());