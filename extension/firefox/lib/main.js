var
    cm = require('sdk/context-menu'),
    items = [
    
        cm.Item({
            label:'Search for repost',
            data:'http://redditbooru.com/#!q='
        }),
        
        cm.Item({
            label:'Rehost image',
            data:'http://redditbooru.com/upload.php?redirect=true&url='
        })
    
    ],
    
    menu = cm.Menu({
        label:'RedditBooru Repost Finder',
        context: cm.SelectorContext('img'),
        contentScript: 'self.on("click", function(node, data) { window.open(data + node.src, "_blank"); });',
        items:items
    });