/**
 * Image model
 */

(function(undefined) {

    RB.Image = Backbone.Model.extend({

        defaults: function() {
            return {
                rowId: null,
                cdnUrl: null,
                width: null,
                height: null,
                sourceId: null,
                sourceName: null,
                baseUrl: null,
                postId: null,
                title: null,
                dateCreated: null,
                externalId: null,
                score: null,
                userId: null,
                userName: null,
                nsfw: null,
                thumb: null,
                idxInAlbum: null,
                age: null,
                rendered: false
            };
        }

    });

}());