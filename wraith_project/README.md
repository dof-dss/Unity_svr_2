# Wraith regression testing

Config files have been created here to allow you to run automated screenshot comparisons between production and
UAT versions of Unity 2 sites.
For a good introduction to Wraith see here https://www.axelerant.com/blog/visual-regression-testing-using-wraith

## Installation

You will need to install Wraith on your Mac as follows:

sudo gem install wraith
brew install phantomjs
brew install imagemagick
npm install -g casperjs

## Running the tests

Tests have been written for each Unity 2 site in the /configs directory e.g. imtac_capture.yaml to test the Imtac site.

To run all tests for all sites:

./unity2_wraith.sh

To run an individual test on one site:

wraith capture configs/imtac_capture.yaml

After running the tests you will be presented with a 'gallery' of screenshots taht show comparisons between the sites.
Note that a threshold value of 5 has been selected, so if the screenshots differ by more than 5% they will be flagged as
'failed'.

This tool gives a high level indication of possible differences that should be investigated, it is certainly not
foolproof and the results should be taken with a pinch of salt !
