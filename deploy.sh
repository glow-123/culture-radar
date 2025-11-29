#!/bin/bash

set -e

echo "🚀 Culture Radar Deployment Script"
echo "=================================="

# Check if .env file exists
if [ ! -f .env ]; then
    echo "⚠️  Creating .env file from template..."
    cp .env.example .env
    echo "📝 Please edit .env file with your production values before continuing."
    echo "   Especially change all passwords and set your domain name."
    read -p "Press Enter when you've updated the .env file..."
fi

# Generate SSL certificates if they don't exist
echo "🔒 Setting up SSL certificates..."
./generate-ssl.sh

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p logs/nginx
mkdir -p uploads
mkdir -p models

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 uploads
chmod -R 755 logs
chmod 600 nginx/ssl/*.key 2>/dev/null || true
chmod 644 nginx/ssl/*.crt 2>/dev/null || true

# Build and start services
echo "🐳 Building and starting Docker services..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Run database setup
echo "💾 Setting up database..."
docker-compose exec app php setup-database.php

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "🌐 Your site should be accessible at:"
echo "   HTTP:  http://localhost (redirects to HTTPS)"
echo "   HTTPS: https://localhost"
echo ""
echo "📊 Services running:"
echo "   - Web application with SSL"
echo "   - MySQL database"
echo "   - Redis cache"
echo "   - Nginx reverse proxy"
echo "   - AI training scheduler"
echo ""
echo "🔧 To view logs:"
echo "   docker-compose logs -f"
echo ""
echo "🛑 To stop services:"
echo "   docker-compose down"
echo ""
echo "⚠️  Note: If using self-signed certificates, browsers will show a security warning."
echo "   For production, replace with Let's Encrypt or commercial SSL certificates."