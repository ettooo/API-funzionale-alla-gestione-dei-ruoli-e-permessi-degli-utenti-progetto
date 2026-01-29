#!/bin/bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

# CONFIGURA QUI IL TUO UTENTE E PASSWORD
PMA_USER="utente_phpmyadmin"
PMA_PASS="ringraziandoPENNETTA"
BLOWFISH_SECRET="qwertyuiopasdfghjklzxcvbnmqwerty"

# PATHS
PMA_DIR="/var/www/html/phpmyadmin"
APACHE_CONF="/etc/apache2/conf-available/phpmyadmin.conf"

echo "🛠️  Aggiornamento pacchetti e installazione Apache, PHP, MariaDB..."
sudo apt update -y
sudo apt install -y apache2 wget unzip mariadb-server \
    php libapache2-mod-php php-mysql php-mbstring php-zip php-gd php-curl curl

echo "🚀 Abilitazione e avvio servizi..."
sudo systemctl enable --now apache2
sudo systemctl enable --now mariadb

echo "🔒 Esecuzione configurazione sicura MariaDB (automatica con expect)..."
sudo mariadb <<EOF
DELETE FROM mysql.user WHERE User='';
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

echo "📂 Installazione phpMyAdmin..."
tmpdir="$(mktemp -d)"
cd "$tmpdir"
sudo wget -q -O phpmyadmin.zip "https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip"
sudo unzip -q phpmyadmin.zip
pma_extracted="$(find . -maxdepth 1 -type d -name 'phpMyAdmin-*-all-languages' | head -n 1)"
if [[ -z "${pma_extracted}" ]]; then
    echo "❌ Errore: cartella estratta phpMyAdmin-*-all-languages non trovata."
    exit 1
fi
sudo rm -rf "$PMA_DIR"
sudo mv "$pma_extracted" "$PMA_DIR"
sudo chown -R www-data:www-data "$PMA_DIR"

echo "⚙️  Configurazione phpMyAdmin..."
cd "$PMA_DIR"
sudo cp -f config.sample.inc.php config.inc.php
sudo sed -i "s/\(\$cfg\['blowfish_secret'\]\s*=\s*\).*/\1'${BLOWFISH_SECRET}';/" config.inc.php

# TempDir per evitare warning runtime
sudo mkdir -p /var/lib/phpmyadmin/tmp
sudo chown -R www-data:www-data /var/lib/phpmyadmin
sudo chmod 700 /var/lib/phpmyadmin/tmp
if ! sudo grep -q "TempDir" config.inc.php; then
    echo "\$cfg['TempDir'] = '/var/lib/phpmyadmin/tmp';" | sudo tee -a config.inc.php >/dev/null
fi

echo "🌐 Configurazione Apache per phpMyAdmin..."
sudo tee "$APACHE_CONF" >/dev/null <<EOCONF
Alias /phpmyadmin $PMA_DIR

<Directory $PMA_DIR>
    Options FollowSymLinks
    DirectoryIndex index.php
    AllowOverride All
    Require all granted
</Directory>
EOCONF

# Evita warning AH00558 (ServerName mancante)
echo "ServerName localhost" | sudo tee /etc/apache2/conf-available/servername.conf >/dev/null
sudo a2enconf servername >/dev/null || true

sudo a2enconf phpmyadmin >/dev/null || true
sudo systemctl reload apache2

echo "👤 Creazione utente MariaDB per phpMyAdmin..."
sudo mariadb <<EOF
CREATE USER IF NOT EXISTS '$PMA_USER'@'localhost' IDENTIFIED BY '$PMA_PASS';
GRANT ALL PRIVILEGES ON *.* TO '$PMA_USER'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

echo ""
echo "✅ Installazione completata!"
echo "🔗 Accedi a phpMyAdmin all'indirizzo:"
echo "    http://localhost/phpmyadmin"
echo ""
echo "👤 Credenziali di accesso:"
echo "    Utente: $PMA_USER"
echo "    Password: $PMA_PASS"