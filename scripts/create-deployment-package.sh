#!/bin/bash

# Create Deployment Package Script
# This script creates a standalone deployment package for production

set -e

PACKAGE_NAME="github-smart-deployment"
PACKAGE_VERSION="2.0.0"

echo "📦 Creating GitHub Smart Deployment Package"
echo "=========================================="

# Create package directory
PACKAGE_DIR="${PACKAGE_NAME}-${PACKAGE_VERSION}"
rm -rf "$PACKAGE_DIR"
mkdir -p "$PACKAGE_DIR"

echo "📁 Creating package structure..."

# Copy essential files
cp docker-compose.yml "$PACKAGE_DIR/"
cp docker.env.example "$PACKAGE_DIR/"
cp create_tables.sql "$PACKAGE_DIR/"
cp scripts/deploy-production.sh "$PACKAGE_DIR/"
chmod +x "$PACKAGE_DIR/deploy-production.sh"

# Create README for the package
cat > "$PACKAGE_DIR/README.md" << 'EOF'
# GitHub Smart - Production Deployment Package

This package contains everything needed to deploy GitHub Smart in production.

## 🚀 Quick Start

1. **Setup environment**
   ```bash
   cp docker.env.example docker.env
   nano docker.env
   ```

2. **Deploy**
   ```bash
   ./deploy-production.sh deploy
   ```

3. **Access application**
   - Web: http://localhost:8081
   - Database: localhost:3306

## 📋 Configuration

Edit `docker.env` with your settings:

- `GITHUB_TOKEN`: Your GitHub Personal Access Token
- `GITHUB_ORG`: Your GitHub organization name
- `MYSQL_ROOT_PASSWORD`: Secure MySQL root password
- `MYSQL_PASSWORD`: Secure MySQL user password
- `APP_KEY`: Random 32-character string

## 🛠️ Management

```bash
# Check status
./deploy-production.sh status

# View logs
./deploy-production.sh logs

# Stop application
./deploy-production.sh stop

# Restart application
./deploy-production.sh restart
```

## 📚 Documentation

For detailed documentation, visit:
https://github.com/your-org/github-smart

## 🔒 Security

- Change all default passwords
- Use strong, unique passwords
- Keep your GitHub token secure
- Regularly update the application
EOF

# Create a simple setup script
cat > "$PACKAGE_DIR/setup.sh" << 'EOF'
#!/bin/bash

echo "🚀 GitHub Smart Setup"
echo "===================="

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop."
    exit 1
fi

# Check if docker.env exists
if [ ! -f "docker.env" ]; then
    echo "📝 Creating docker.env from template..."
    cp docker.env.example docker.env
    echo "✅ docker.env created. Please edit it with your settings."
    echo "   Required settings:"
    echo "   - GITHUB_TOKEN: Your GitHub Personal Access Token"
    echo "   - GITHUB_ORG: Your GitHub organization name"
    echo "   - MYSQL_ROOT_PASSWORD: Secure MySQL root password"
    echo "   - MYSQL_PASSWORD: Secure MySQL user password"
    echo "   - APP_KEY: Random 32-character string"
    echo ""
    echo "After editing docker.env, run: ./deploy-production.sh deploy"
else
    echo "✅ docker.env already exists"
fi
EOF

chmod +x "$PACKAGE_DIR/setup.sh"

# Create a version file
echo "$PACKAGE_VERSION" > "$PACKAGE_DIR/VERSION"

# Create package archive
echo "📦 Creating package archive..."
tar -czf "${PACKAGE_DIR}.tar.gz" "$PACKAGE_DIR"

# Create checksum
sha256sum "${PACKAGE_DIR}.tar.gz" > "${PACKAGE_DIR}.tar.gz.sha256"

echo "✅ Package created successfully!"
echo ""
echo "📦 Package files:"
echo "   - ${PACKAGE_DIR}.tar.gz"
echo "   - ${PACKAGE_DIR}.tar.gz.sha256"
echo ""
echo "📋 Package contents:"
echo "   - docker-compose.yml"
echo "   - docker.env.example"
echo "   - create_tables.sql"
echo "   - deploy-production.sh"
echo "   - setup.sh"
echo "   - README.md"
echo ""
echo "🚀 Users can now:"
echo "   1. Download ${PACKAGE_DIR}.tar.gz"
echo "   2. Extract: tar -xzf ${PACKAGE_DIR}.tar.gz"
echo "   3. Run: cd $PACKAGE_DIR && ./setup.sh"
echo "   4. Edit docker.env with their settings"
echo "   5. Deploy: ./deploy-production.sh deploy" 