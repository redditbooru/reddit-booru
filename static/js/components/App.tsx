import * as React from 'react';

import { Thumb } from '../components/Thumb';
import { IPostData } from '../interfaces/api';

export interface IApp {
  startUp: Array<IPostData>;
}

export class App extends React.Component<IApp> {
  render() {
    return (
      <ul>
        {this.props.startUp.map(post => (
          <li><Thumb imageUrl={post.thumb} width={300} height={300} title={post.title} /></li>
        ))}
      </ul>
    );
  }
}
