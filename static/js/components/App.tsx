import * as React from 'react';

import { ImageList } from '../components/ImageList';
import { IPostData } from '../interfaces/api';

export interface IApp {
  startUp: Array<IPostData>;
}

export class App extends React.Component<IApp> {
  render() {
    return (
      <div id="images">
        <ImageList images={window.startUp} />
      </div>
    );
  }
}
