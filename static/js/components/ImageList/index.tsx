import * as React from 'react';

import { IPostData } from '@src/interfaces/api';
import { Thumb } from '@src/components/Thumb';

import './styles.scss';

const AVERAGE_COLUMN_WIDTH = 300;
const MIN_WIDTH_HEIGHT_RATIO = 0.9;

interface IImageListItem {
  image: IPostData;
  width: number;
}

type IImageListRow = Array<IImageListItem>;

export interface IImageListProps {
  images: Array<IPostData>;
}

export class ImageList extends React.Component<IImageListProps> {
  private elRef: React.RefObject<HTMLUListElement>;
  private elWidth: number;

  constructor(props: IImageListProps) {
    super(props);

    this.elRef = React.createRef<HTMLUListElement>();
  }

  /**
   * The number of columns ideal for the component's display width.
   */
  get columnCount(): number {
    return Math.floor(this.elWidth / AVERAGE_COLUMN_WIDTH);
  }

  /**
   * Waits until the element has been rendered, gets the element's
   * width, and the force re-renders the whole component.
   *
   * @return Always null, could be a loader though
   */
  calculateElementWidth(): React.ReactNode {
    // Introduce some async so that the element can be rendered
    setTimeout(() => {
      this.elWidth = this.elRef.current.clientWidth;

      // Force a re-render
      this.forceUpdate();
    }, 0);

    return null;
  }

  /**
   * Renders the passed images as a series of rows
   *
   * @return The array of rendered rows.
   */
  renderRows(): Array<React.ReactNode> {
    const rows: Array<IImageListRow> = [];
    let row: Array<IPostData> = [];
    let count = 0;

    const columnCount = this.columnCount;
    this.props.images.forEach(image => {
      if (count === columnCount) {
        // Calculate the widths for each image in this row
        const widthRatioSum = row.reduce((widthRatio, image) => {
          const ratio = image.width / image.height;
          return widthRatio + (ratio < MIN_WIDTH_HEIGHT_RATIO ? MIN_WIDTH_HEIGHT_RATIO : ratio);
        }, 0);

        rows.push(row.map(image => {
          const ratio = image.width / image.height;
          return {
            width: Math.round(ratio / widthRatioSum * 10000) / 100,
            image
          };
        }));

        row = [];
        count = 0;
      }
      row.push(image);
      count++;
    });

    return rows.map(row => row.map((imageContainer, index) => {
      const { image } = imageContainer;
      return (
        <Thumb
          key={image.id}
          imageUrl={image.thumb}
          thumbWidth={300}
          thumbHeight={300}
          displayWidth  ={`${imageContainer.width}%`}
          title={image.title}
          className={[ 'image-list__item', index === 0 ? 'image-list__item--first' : '' ]}
        />
      );
    }));
  }

  render() {
    // If the element hasn't been rendered yet, render the element empty
    // so that a width calculation can be retrieved. Otherwise, render
    // the list as normal.
    return (
      <ul className="image-list" ref={this.elRef}>
        {this.elWidth ? this.renderRows() : this.calculateElementWidth()}
      </ul>
    );
  }
}
