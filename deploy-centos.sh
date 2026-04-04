#!/usr/bin/env bash
# =============================================================================
# deploy-centos.sh
# Deploy / update the HMS-ABDM Gateway on CentOS 7 (Apache + MySQL + PHP 8.3)
#
# Run as root or a user with sudo rights from the project root directory:
#   sudo bash deploy-centos.sh
# =============================================================================

set -euo pipefail

# --------------------------------------------------------------------------
# Helpers
# --------------------------------------------------------------------------
info()  { echo -e "\033[1;34m[INFO]\033[0m  $*"; }
ok()    { echo -e "\033[1;32m[ OK ]\033[0m  $*"; }
warn()  { echo -e "\033[1;33m[WARN]\033[0m  $*"; }
error() { echo -e "\033[1;31m[ERR ]\033[0m  $*" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# --------------------------------------------------------------------------
# 1. Check PHP version
# --------------------------------------------------------------------------
info "Checking PHP version..."
PHP_BIN="${PHP_BIN:-php}"
PHP_VER=$($PHP_BIN -r 'echo PHP_VERSION;')
PHP_MAJOR=$($PHP_BIN -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$($PHP_BIN -r 'echo PHP_MINOR_VERSION;')

if [[ "$PHP_MAJOR" -lt 8 ]] || { [[ "$PHP_MAJOR" -eq 8 ]] && [[ "$PHP_MINOR" -lt 2 ]]; }; then
    error "PHP 8.2+ required, found $PHP_VER"
fi
ok "PHP $PHP_VER detected"

# --------------------------------------------------------------------------
# 2. Check required PHP extensions
# --------------------------------------------------------------------------
info "Checking required PHP extensions..."
REQUIRED_EXTS=(intl mbstring curl json mysqlnd pdo pdo_mysql xml zip)
MISSING_EXTS=()

for ext in "${REQUIRED_EXTS[@]}"; do
    if ! $PHP_BIN -r "if (!extension_loaded('$ext')) exit(1);"; then
        MISSING_EXTS+=("$ext")
    fi
done

if [[ ${#MISSING_EXTS[@]} -gt 0 ]]; then
    warn "Missing PHP extensions: ${MISSING_EXTS[*]}"
    warn "Install with: sudo yum install -y ${MISSING_EXTS[*]/#/php83-php-}"
    error "Please install missing extensions and re-run this script."
fi
ok "All required PHP extensions are present"

# --------------------------------------------------------------------------
# 3. Install / update Composer dependencies
# --------------------------------------------------------------------------
info "Installing Composer dependencies (no-dev, optimised autoloader)..."

if ! command -v composer &>/dev/null; then
    error "Composer not found. Install it first:\n  php -r \"copy('https://getcomposer.org/installer','composer-setup.php');\"\n  php composer-setup.php --install-dir=/usr/local/bin --filename=composer"
fi

composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist
ok "Composer dependencies installed"

# --------------------------------------------------------------------------
# 4. Set up .env
# --------------------------------------------------------------------------
if [[ ! -f .env ]]; then
    info "Creating .env from env template..."
    cp env .env
    # Switch to production mode by default
    sed -i 's/^# CI_ENVIRONMENT = production/CI_ENVIRONMENT = production/' .env
    warn ".env created — please edit it now and set your DB and ABDM credentials:"
    warn "  nano .env"
    warn "Then re-run this script, or run migrations manually: php spark migrate"

    if command -v nano &>/dev/null; then
        read -r -p "Open .env in nano now? [Y/n] " yn
        [[ "${yn:-Y}" =~ ^[Yy]$ ]] && nano .env
    fi
else
    ok ".env already exists — skipping (edit manually if credentials changed)"
fi

# --------------------------------------------------------------------------
# 5. Set writable-directory permissions
# --------------------------------------------------------------------------
info "Setting ownership and permissions on writable/..."
APACHE_USER="apache"   # CentOS default; change to 'www-data' for Ubuntu/Debian

mkdir -p writable/{cache,logs,session,uploads}
chown -R "${APACHE_USER}:${APACHE_USER}" writable
chmod -R 775 writable
ok "writable/ permissions set"

# --------------------------------------------------------------------------
# 6. Run database migrations
# --------------------------------------------------------------------------
info "Running database migrations..."
if $PHP_BIN spark migrate --no-interaction; then
    ok "Migrations complete"
else
    warn "Migration failed — ensure the DB credentials in .env are correct, then run:"
    warn "  php spark migrate"
fi

# --------------------------------------------------------------------------
# 7. Done
# --------------------------------------------------------------------------
echo ""
ok "Deployment complete!"
echo ""
echo "  Next steps:"
echo "  1. Configure an Apache VirtualHost pointing DocumentRoot to:"
echo "       $(pwd)/public"
echo "  2. Enable mod_rewrite and set AllowOverride All for the public/ directory."
echo "  3. Reload Apache: sudo systemctl reload httpd"
echo "  4. Add a cron for the sync queue:"
echo "       */5 * * * * /usr/bin/php $(pwd)/spark abdm:process-queue >> /var/log/hms-abdm-queue.log 2>&1"
echo ""
