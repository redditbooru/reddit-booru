import * as React from 'react';

export interface IThumb {
  imageUrl: string;
  height: number;
  width: number;
  title: string;
}

export class Thumb extends React.Component<IThumb> {
  render() {
    return (
      <img src={`${this.props.imageUrl}_${this.props.height}_${this.props.width}.jpg`} alt={this.props.title} />
    );
  }
}
