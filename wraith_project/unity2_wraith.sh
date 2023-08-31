wraith capture configs/imtac_capture.yaml > output.txt
wraith capture configs/mental_health_capture.yaml >> output.txt
wraith capture configs/public_appointments_capture.yaml >> output.txt
wraith capture configs/victims_payments_capture.yaml >> output.txt
wraith capture configs/hia_redress_capture.yaml >> output.txt
wraith capture configs/cvocni_capture.yaml >> output.txt
wraith capture configs/nijac_capture.yaml >> output.txt
wraith capture configs/cyber_capture.yaml >> output.txt
wraith capture configs/cosica_capture.yaml >> output.txt
wraith capture configs/ircommission_capture.yaml >> output.txt
wraith capture configs/ppsni_capture.yaml >> output.txt
wraith capture configs/nicertoffice_capture.yaml >> output.txt
wraith capture configs/src_capture.yaml >> output.txt
grep -A3 'failed' output.txt
echo '************'
grep gallery output.txt
