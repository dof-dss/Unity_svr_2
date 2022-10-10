// var Transform = require('stream').Transform;
// var util = require('util');
const fs = require('fs');

var input = 'css/kss-chroma-markup.css'; // read file
var output = 'src/scss/init/chroma.twig'; // write file

// input // take input
//   .pipe($.replace(/(\/\*|\*|\/)\n/g, '')) // pipe through line remover
//   .pipe(output); // save to file
fs.readFile(input, function(err, data) { // read file to memory
  if (err) {
    return  console.log(err);
  }
  var file;
  data = data.toString(); // stringify buffer
  file = data.replace(/(\/\*|\*\/)/g, '');
  fs.writeFile(output, file, function(err) { // write file
    if (err) { // if error, report
      console.log (err);
    }
  });
});
