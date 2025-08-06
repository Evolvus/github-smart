# Troubleshooting Guide

## Common Issues and Solutions

### 1. "denied: denied" Error from ghcr.io

This error occurs when Docker cannot authenticate with the GitHub Container Registry. Here are the most common causes and solutions:

#### **Token Permissions**
Ensure your GitHub Personal Access Token has the correct permissions:
1. Go to https://github.com/settings/tokens
2. Create a new token or edit existing one
3. **Required scopes:**
   - `read:packages` - to pull images from GitHub Packages
   - `repo` - if the package is in a private repository

#### **Token Format**
- GitHub tokens should start with `ghp_` (Personal Access Token) or `gho_` (GitHub App token)
- Example: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

#### **Organization/Repository Access**
- Verify you have access to the GitHub organization/repository
- Check if the Docker image exists in the packages section
- Ensure the GitHub Action has run and published the image

#### **Network/Firewall Issues**
- Check your internet connection
- Ensure Docker can access external registries
- Try: `docker pull hello-world` to test basic Docker connectivity

### 2. Debugging Steps

#### **Test Token Manually**
```bash
# Test basic GitHub API access
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user

# Test packages access
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user/packages
```

#### **Test Docker Registry Access**
```bash
# Try to login manually
echo "YOUR_TOKEN" | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# Check if login was successful
docker login ghcr.io
```

#### **Check Image Existence**
1. Go to your GitHub repository
2. Click on "Packages" tab
3. Look for the `github-smart` package
4. Verify the image tag exists (e.g., `latest`, `main`, etc.)

### 3. Alternative Solutions

#### **Build Locally (Fallback)**
If pulling fails, you can build the image locally:
```bash
# Clone the repository
git clone https://github.com/YOUR_ORG/github-smart.git
cd github-smart

# Build the image
docker build -t github-smart:latest .

# Run the container
docker run -d -p 8080:8080 -e GITHUB_ORG=YOUR_ORG -e GITHUB_TOKEN=YOUR_TOKEN github-smart:latest
```

#### **Use Docker Compose**
If the deploy script fails, try using Docker Compose:
```bash
# Copy the example environment file
cp docker.env.example docker.env

# Edit the environment file with your credentials
nano docker.env

# Run with Docker Compose
docker-compose up -d
```

### 4. Common Error Messages

#### **"unauthorized: authentication required"**
- Token is invalid or expired
- Token lacks required permissions
- Organization name is incorrect

#### **"manifest unknown"**
- Image doesn't exist in the registry
- Wrong image tag specified
- GitHub Action hasn't run yet

#### **"connection refused"**
- Docker daemon not running
- Network connectivity issues
- Firewall blocking Docker

### 5. Getting Help

If you're still experiencing issues:

1. **Check the logs:**
   ```bash
   docker logs github-smart
   ```

2. **Run with verbose output:**
   ```bash
   ./deploy.sh -o YOUR_ORG -t YOUR_TOKEN 2>&1 | tee deploy.log
   ```

3. **Verify GitHub Action status:**
   - Go to your repository on GitHub
   - Click on "Actions" tab
   - Check if the Docker build workflow has completed successfully

4. **Check package visibility:**
   - Ensure the package is not private if you're trying to access it from outside the organization
   - Or ensure your token has access to private packages

### 6. Environment Variables

You can also set environment variables for debugging:
```bash
export DOCKER_BUILDKIT=1
export DOCKER_CLI_EXPERIMENTAL=enabled
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN
``` 