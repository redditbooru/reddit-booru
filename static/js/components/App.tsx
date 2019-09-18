import * as React from 'react';

import { IPostData } from '@src/interfaces/api';
import { ImageList } from '@src/components/ImageList';

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
