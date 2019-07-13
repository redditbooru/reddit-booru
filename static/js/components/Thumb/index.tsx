import * as React from 'react';
import * as classnames from 'classnames';

import './styles.scss';

export interface IThumb {
  imageUrl: string;
  thumbWidth: number;
  thumbHeight: number;
  displayWidth: string;
  title: string;
  className?: string | Array<string>;
}

export class Thumb extends React.Component<IThumb> {
  render() {
    const style = {
      backgroundImage: `url(${this.props.imageUrl}_${this.props.thumbWidth}_${this.props.thumbHeight}.jpg)`,
      width: `${this.props.displayWidth}`
    };

    return (
      <li style={style} className={classnames('thumb', this.props.className)} />
    );
  }
}
