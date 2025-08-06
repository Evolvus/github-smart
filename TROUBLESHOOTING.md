# Troubleshooting Guide

## Common Issues and Solutions

### 1. "denied: denied" Error from ghcr.io

This error occurs when Docker cannot access the GitHub Container Registry. Since the package is public, this is typically a network or connectivity issue.

#### **Common Causes**
1. **Network Connectivity**: Check your internet connection
2. **Firewall Settings**: Ensure Docker can access external registries
3. **Docker Configuration**: Verify Docker is running properly
4. **Registry Access**: The registry might be temporarily unavailable

#### **Token Format**
- GitHub tokens should start with `ghp_` (Personal Access Token) or `gho_` (GitHub App token)
- Example: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
- **Note**: The token is used by the application to access GitHub API data, not for pulling the Docker image

#### **Organization/Repository Access**
- Verify the Docker image exists in the packages section
- Ensure the GitHub Action has run and published the image
- Check if the package is set to public visibility

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
# Test basic Docker connectivity
docker pull hello-world

# Test GitHub Container Registry access
docker pull ghcr.io/YOUR_ORG/github-smart:latest
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
- This error should not occur for public packages
- Check if the package is actually public
- Verify the image exists in the registry

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

### 6. Token Usage Clarification

**Important**: The GitHub Personal Access Token is used by the application to:
- Fetch issues and data from GitHub repositories
- Access GitHub API endpoints
- Retrieve repository information

**The token is NOT used for:**
- Pulling the Docker image (public package)
- Authenticating with GitHub Container Registry

**Required Token Permissions:**
- `repo` - to access repository data and issues
- `read:org` - if accessing organization repositories
- `read:user` - to access user information

### 7. Environment Variables

You can also set environment variables for debugging:
```bash
export DOCKER_BUILDKIT=1
export DOCKER_CLI_EXPERIMENTAL=enabled
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN
``` 