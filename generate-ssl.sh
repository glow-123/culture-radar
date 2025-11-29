#!/bin/bash

# Create SSL directory
mkdir -p nginx/ssl

# Generate self-signed certificate for development
if [ ! -f nginx/ssl/culture-radar.crt ] || [ ! -f nginx/ssl/culture-radar.key ]; then
    echo "Generating self-signed SSL certificate for development..."
    
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout nginx/ssl/culture-radar.key \
        -out nginx/ssl/culture-radar.crt \
        -subj "/C=FR/ST=France/L=Paris/O=Culture Radar/OU=Development/CN=culture-radar.fr/emailAddress=admin@culture-radar.fr" \
        -config <(
        echo '[req]'
        echo 'default_bits = 2048'
        echo 'prompt = no'
        echo 'distinguished_name = req_distinguished_name'
        echo 'req_extensions = v3_req'
        echo '[req_distinguished_name]'
        echo 'C = FR'
        echo 'ST = France'
        echo 'L = Paris'
        echo 'O = Culture Radar'
        echo 'OU = Development'
        echo 'CN = culture-radar.fr'
        echo 'emailAddress = admin@culture-radar.fr'
        echo '[v3_req]'
        echo 'basicConstraints = CA:FALSE'
        echo 'keyUsage = nonRepudiation, digitalSignature, keyEncipherment'
        echo 'subjectAltName = @alt_names'
        echo '[alt_names]'
        echo 'DNS.1 = culture-radar.fr'
        echo 'DNS.2 = www.culture-radar.fr'
        echo 'DNS.3 = localhost'
        echo 'IP.1 = 127.0.0.1'
    )
    
    # Set appropriate permissions
    chmod 600 nginx/ssl/culture-radar.key
    chmod 644 nginx/ssl/culture-radar.crt
    
    echo "SSL certificate generated successfully!"
    echo "Certificate: nginx/ssl/culture-radar.crt"
    echo "Private key: nginx/ssl/culture-radar.key"
    echo ""
    echo "⚠️  This is a self-signed certificate for development only."
    echo "For production, use Let's Encrypt or a commercial SSL certificate."
else
    echo "SSL certificate already exists."
fi

echo ""
echo "To use Let's Encrypt in production, run:"
echo "docker run --rm -v \$(pwd)/nginx/ssl:/etc/letsencrypt certbot/certbot certonly --webroot -w /var/www/html -d culture-radar.fr -d www.culture-radar.fr"