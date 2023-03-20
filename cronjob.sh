#!/usr/bin/env bash
#
# nicsdru-logging/scripts/example.cronjob.sh
#
# Calls shiplog.sh to ship latest log entries from various logs
# to logz.io.

# /bin/bash /app/nicsdru-logging/scripts/shiplog.sh "[LOG_NAME]" "[LOG_PATH]" "[LOG_DATE_PATTERN]" "[LOG_TYPE]"

# Ship /var/log/access.log generated by nginx.
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "access" "/var/log/access.log" "$(date +%d/%b/%Y:)" "nginx_access"

# Uncomment following line to ship drupal.log generated by D9 filelog module.
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-mentalhealthchampionni" "/app/log/mentalhealthchampionni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-nicertoffice" "/app/log/nicertoffice/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-publicappointmentsni" "/app/log/publicappointmentsni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-imtac" "/app/log/imtac/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-victimspaymentsboard" "/app/log/victimspaymentsboard/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-hiaredressni" "/app/log/hiaredressni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-ircommission" "/app/log/ircommission/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-nijac" "/app/log/nijac/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-nicybersecuritycentre" "/app/log/nicybersecuritycentre/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-cvocni" "/app/log/cvocni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-ppsni" "/app/log/ppsni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-sentencereview" "/app/log/sentencereview/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-communityrelations" "/app/log/communityrelations/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-judiciaryni" "/app/log/judiciaryni/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
/bin/bash /app/nicsdru-logging/scripts/shiplog.sh "drupal-cosicani" "/app/log/cosicani/drupal.log" "$(date +'%a, %d/%m/%Y -')" "drupal"
