const TRANSITION_END = 'transitionend';

function onTransitionEnd(evt) {
  var el = evt.target;
  el.style.display = 'none';
  el.removeEventListener(TRANSITION_END, onTransitionEnd);
}

export function initOverlay(el) {
  el.style.display = 'none';
}

export function showOverlay(el, callback) {
  el.style.display = 'block';
  // Async the callback so that the element can be refreshed
  setTimeout(callback, 0);
}

export function hideOverlay(el) {
  el.addEventListener(TRANSITION_END, onTransitionEnd);
}