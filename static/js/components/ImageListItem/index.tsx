import * as React from 'react';
import classnames from 'classnames';

import './styles.scss';

import { IPostData } from '@src/interfaces/api';
import { createThumbUrl } from '@src/utils/thumb';

export interface IImageListItem {
  post: IPostData;
  displayWidth: number;
  className?: string;
  isFirst?: boolean;
}

export class ImageListItem extends React.Component<IImageListItem> {
  render() {
    const { post } = this.props;

    const thumbUrl = createThumbUrl(post.cdnUrl);
    const style = {
      backgroundImage: `url(${thumbUrl})`,
      width: `${this.props.displayWidth}%`
    };
    const className = classnames([
      'image-list__item',
      this.props.isFirst ? 'image-list__item--first' : ''
    ]);

    return (
      <li style={style} className={className}>
        <a href={`https://redd.it/${post.externalId}`} target="_blank" className="image-list__link">
        </a>
      </li>
    );
  }
}
