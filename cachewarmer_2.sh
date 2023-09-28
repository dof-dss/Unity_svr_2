#!/bin/bash

clear

echo "Warming up unity 2 sites with HTTrack ..."

echo "Warming CVOCNI..."
httrack https://www.cvocni.org/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming SRC..."
httrack https://www.sentencereview.org.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming CRC..."
httrack https://www.community-relations.org.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming Nijac..."
httrack https://www.nijac.gov.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming PPSNI..."
httrack https://www.ppsni.gov.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming NI Cyber Security..."
httrack https://www.nicybersecuritycentre.gov.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming Cosica..."
httrack https://www.cosica-ni.org/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming complete.:"
