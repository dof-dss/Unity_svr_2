const imagemin = require('imagemin');
const PNGImages = 'src/images/**/*.png';
const JPEGImages = 'src/images/**/*.{jpg,jpeg,JPG,JPEG}';
const GIFImages = 'src/images/**/*.gif';
const output = 'images';

const imageminJpegtran = require('imagemin-jpegtran');
const imageminJpegoptim = require('imagemin-jpegoptim');
const imageminOptipng = require('imagemin-optipng');
const imageminPngcrush = require('imagemin-pngcrush');
const imageminPngout = require('imagemin-pngout');
const imageminZopfli = require('imagemin-zopfli');
const imageminGifsicle = require('imagemin-gifsicle');

const optimiseJPEGImages = () =>
  imagemin([JPEGImages], {
    destination: output,
    plugins: [
      imageminJpegoptim(),
      imageminJpegtran(),
    ]
  });

const optimisePNGImages = () =>
  imagemin([PNGImages], {
    destination: output,
    plugins: [
      imageminOptipng(),
      imageminPngcrush({
        reduce: true,
      }),
      imageminPngout(),
      imageminZopfli(),
    ]
  });

const optimiseGIFImages = () =>
  imagemin([GIFImages], {
    destination: output,
    plugins: [
      imageminGifsicle(),
    ]
  });

(async () => {
  const files = await optimisePNGImages()
    .then(() => optimiseJPEGImages())
    .then(() => optimiseGIFImages())
    .catch(error => console.log(error));

  console.log(files);
})();
