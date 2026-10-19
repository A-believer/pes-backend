#!/usr/bin/env bash
# ==============================================================================
# Production Deployment Script for pes-backend (Laravel)
# ==============================================================================
set -e

echo "🚀 Starting PES Backend production deployment..."

# Ensure we are in project root
cd "$(dirname "$0")"

# 1. Install production dependencies
echo "📦 Installing Composer dependencies (no-dev, optimized)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Run database migrations safely
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 3. Ensure public storage symlink exists
echo "🔗 Ensuring public storage link..."
php artisan storage:link || true

# 4. Cache configuration, routes, and views for maximum production performance
echo "⚡ Caching configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart queue worker (if running supervisor or daemon)
echo "🔄 Restarting queue worker..."
php artisan queue:restart || true

echo "✅ PES Backend deployed successfully!"
