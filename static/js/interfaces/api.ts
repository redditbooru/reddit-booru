// Reflects Api\PostData
export interface IPostData {
  id: number;
  imageId: number;
  postId: number;
  width: number;
  height: number;
  caption: string;
  sourceUrl: string;
  type: string;
  sourceId: number;
  sourceName: string;
  title: string;
  keywords: string;
  nsfw: boolean;
  dateCreated: number;
  externalId: string;
  score: number;
  visible: boolean;
  userId: number;
  userName: string;
  cdnUrl: string;
  thumb: string;
}
