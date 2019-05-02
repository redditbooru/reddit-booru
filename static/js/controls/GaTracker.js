export default function(category, event, data) {
    if (window._gaq) {
        window._gaq.push([ '_trackEvent', category, event ]);
    }
};