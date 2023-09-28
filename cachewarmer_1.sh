#!/bin/bash

clear

echo "Warming up unity 2 sites with HTTrack ..."

echo "Warming IMTAC..."
httrack https://www.imtac.org.uk// -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming IRC..."
httrack http://ircommission.org/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming mental health champion..."
httrack https://www.mentalhealthchampion-ni.org.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming public appointments..."
httrack https://www.publicappointmentsni.org/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming NI Cert Office..."
httrack https://www.nicertoffice.org.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming Victims Payments..."
httrack https://www.victimspaymentsboard.org.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming HIA Redress..."
httrack http://hiaredressni.uk/ -c5R2b0s0aqzvp0C0r3F "HTTrack cache builder" "-*.jpg" "-*.css" "-*.js" "-*.png" "-*.gif" "-*.jpeg" "-*.ico" "-*/type/*" "-*/topic/*" "-*/date/*" "-*page=*"

echo "---------------------------------------------------"

echo "Warming complete.:"
