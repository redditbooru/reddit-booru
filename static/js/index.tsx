import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { App } from './components/App';
import { IPostData } from './interfaces/api';

// Hack until everything is components: get the styles
import '../scss/styles.scss';
import '../scss/mobile.scss';

declare global {
  interface Window {
    startUp: Array<IPostData>
  }
}

ReactDOM.render(
  <App startUp={window.startUp} />,
  document.getElementById('app')
);
