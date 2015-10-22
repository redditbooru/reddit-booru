import Backbone from 'backbone';
import _ from 'underscore';

import queryOptionItem from '../../../../views/queryOptionItem.handlebars';

const EVT_UPDATE = 'update';

export default Backbone.View.extend({

    collection: null,
    $el: null,
    $sources: null,
    $sizes: null,
    template: queryOptionItem,

    events: {
        'click button': 'handleRefreshClick',
        'change input[type="checkbox"]': 'handleCheckChange'
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

    },

    handleCheckChange: function(evt) {
        var $target = $(evt.currentTarget);
        var selector = '[name="' + $target.attr('name') + '"]';
        var $set = this.$el.find(selector);
        var checked = $target.is(':checked');

        if ($target.val() === 'all') {
            $set.prop('checked', checked);
        } else {
            // If only one checkbox isn't checked, it's the "all", so check it
            if (checked && this.$el.find(selector + ':not(:checked)').length === 1) {
                this.$el.find(selector + '[value="all"]').prop('checked', checked);
            } else {
                this.$el.find(selector + '[value="all"]').prop('checked', false);
            }
        }
    }

});