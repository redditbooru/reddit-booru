import * as React from 'react';

import { IPostData } from '../interfaces/api';

export interface IApp {
  startUp: Array<IPostData>;
}

export class App extends React.Component<IApp> {
  render() {
    return (
      <ul>
        {this.props.startUp.map(post => (
          <li>{post.title}</li>
        ))}
      </ul>
    );
  }
}
