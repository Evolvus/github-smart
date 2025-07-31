#!/bin/bash

echo "🚀 GitHub Smart Environment Setup"
echo "================================"
echo ""

# Check if environment files exist
if [ ! -f ".env.example" ]; then
    echo "❌ Error: .env.example not found!"
    exit 1
fi

if [ ! -f "docker.env.example" ]; then
    echo "❌ Error: docker.env.example not found!"
    exit 1
fi

# Setup traditional environment file
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "ℹ️  .env file already exists"
fi

# Setup Docker environment file
if [ ! -f "docker.env" ]; then
    echo "📝 Creating docker.env file from docker.env.example..."
    cp docker.env.example docker.env
    echo "✅ docker.env file created"
else
    echo "ℹ️  docker.env file already exists"
fi

echo ""
echo "🔧 Next Steps:"
echo "1. Edit .env file: nano .env"
echo "2. Edit docker.env file: nano docker.env"
echo "3. Update GITHUB_TOKEN with your actual token"
echo "4. Update GITHUB_ORG if needed"
echo ""
echo "🐳 For Docker setup:"
echo "   ./start-docker.sh"
echo ""
echo "🖥️  For traditional setup:"
echo "   php -S localhost:8000 -t public/"
echo ""
echo "📚 For more information, see README.md" 