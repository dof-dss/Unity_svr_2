#ddev-generated
ARG SOLR_BASE_IMAGE="solr:9.9"
FROM $SOLR_BASE_IMAGE

# Fix HTTPS redirect to HTTP which breaks URL for Solr Admin UI.
# The reason for this problem is that Solr uses Jetty as a webserver.
# Jetty has X-Forwarded- headers disabled by default, enable them here:
ARG SOLRUSER="solr"
ARG SOLRPASS="solr"
USER root
RUN sed -i '/<!-- Uncomment to enable handling of X-Forwarded- style headers/,/-->/c\<Call name="addCustomizer"><Arg><New class="org.eclipse.jetty.server.ForwardedRequestCustomizer"/></Arg></Call>' /opt/solr/server/etc/jetty.xml
RUN sed -i "/\$rootScope.exceptions = {};/a \$scope.username = '$SOLRUSER'; \$scope.password = '$SOLRPASS';" /opt/solr/server/solr-webapp/webapp/js/angular/controllers/login.js
USER solr
