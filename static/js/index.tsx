import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { App } from './components/App';
import { IPostData } from './interfaces/api';

declare global {
  interface Window {
    startUp: Array<IPostData>
  }
}

ReactDOM.render(
  <App startUp={window.startUp} />,
  document.getElementById('images')
);
