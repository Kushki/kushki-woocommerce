FROM wordpress:latest

ENV WOOCOMMERCE_VERSION 3.1.2
ENV WOOCOMMERCE_UPSTREAM_VERSION 3.1.2

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip wget \
    && wget https://downloads.wordpress.org/plugin/woocommerce.3.1.2.zip -O /tmp/temp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/temp.zip \
    && rm /tmp/temp.zip \
    && rm -rf /var/lib/apt/lists/*

# Bundle app source
RUN mkdir /usr/src/wordpress/wp-content/plugins/woocommerce-gateway-kushki
COPY . /usr/src/wordpress/wp-content/plugins/woocommerce-gateway-kushki

## Install plugins
#RUN apt-get -y install php5-xdebug
#
## Add configuration script
#ADD config_xdebug.sh /config_xdebug.sh
#ADD run_wordpress_xdebug.sh /run_wordpress_xdebug.sh
#RUN chmod 755 /*.sh

# Xdebug environment variables
ENV XDEBUG_PORT 9000

#CMD ["/run_wordpress_xdebug.sh"]