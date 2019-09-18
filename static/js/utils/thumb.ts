export function createThumbUrl(url: string, width: number = 300, height: number = 300) {
  // As noted in https://github.com/dxprog/rbthumbs/blob/master/src/url-tools.js
  const encodedUrl = btoa(url).replace(/\=/g, '-').replace(/\//g, '_');
  return `https://beta.thumb.awwni.me/${encodedUrl}_${width}_${height}.jpg`;
}
