#!/bin/bash
set -euo pipefail

sudo service apache2 start
sudo service mariadb start

echo "✅ Apache e MariaDB avviati"
