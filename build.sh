#!/bin/bash
# Install PHP extensions for PostgreSQL
apt-get update && apt-get install -y php-pgsql

# Create necessary directories
mkdir -p logs tmp

# Set proper permissions
chmod 755 css js includes config pages api
chmod 644 *.php

echo "Build completed successfully"