FROM php:8.2-apache

# Copia os arquivos do seu projeto para o diretório do Apache
COPY . /var/www/html/

# Altera a porta padrão do Apache de 80 para 10000 (exigência do Render)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Habilita o mod_rewrite do Apache (ótimo para frameworks como Laravel/Slim)
RUN a2enmod rewrite

EXPOSE 10000
