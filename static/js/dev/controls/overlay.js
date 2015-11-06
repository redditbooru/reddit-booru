const TRANSITION_END = 'transitionend';
const IS_FIREFOX = window.navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

function onTransitionEnd(evt) {
  var el = evt.target;
  el.style.display = 'none';
  if (!IS_FIREFOX) {
    el.removeEventListener(TRANSITION_END, onTransitionEnd);
  }
}

export function initOverlay(el) {
  el.style.display = 'none';
}

export function showOverlay(el, callback) {
  el.style.display = 'block';
  // Run the callback on the next redraw, unless firefox. Fuck those guys...
  if (IS_FIREFOX) {
    callback();
  } else {
    window.requestAnimationFrame(callback);
  }
}

export function hideOverlay(el) {
  if (IS_FIREFOX) {
    onTransitionEnd({ target: el });
  } else {
    window.requestAnimationFrame(() => {
      el.addEventListener(TRANSITION_END, onTransitionEnd);
    });
  }
}