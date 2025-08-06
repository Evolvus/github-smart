# GitHub Smart App Deployment Guide

This guide explains how to deploy the GitHub Smart application using Docker and GitHub Packages.

## Prerequisites

1. **Docker**: Make sure Docker is installed and running on your system
   - [Docker Desktop](https://docs.docker.com/get-docker/) for Windows/Mac
   - [Docker Engine](https://docs.docker.com/engine/install/) for Linux

2. **GitHub Personal Access Token**: You need a GitHub token with the following permissions:
   - `read:packages` - to pull Docker images from GitHub Packages
   - `write:packages` - if you want to push images (for development)

## Quick Deployment

### Option 1: Using the Deployment Script

1. Download the deployment script:
   ```bash
   curl -O https://raw.githubusercontent.com/evolvus/github-smart/main/deploy.sh
   chmod +x deploy.sh
   ```

2. Run the deployment script:
   ```bash
   ./deploy.sh -o evolvus -t YOUR_GITHUB_TOKEN
   ```

### Option 2: Using Environment Variables

```bash
export GITHUB_ORG="evolvus"
export GITHUB_TOKEN="your-github-token"
./deploy.sh
```

### Option 3: Interactive Mode

```bash
./deploy.sh
# The script will prompt you for GitHub org and token
```

## Configuration Options

The deployment script supports the following options:

- `-o, --org`: GitHub organization/username
- `-t, --token`: GitHub Personal Access Token
- `-i, --image`: Docker image (default: `ghcr.io/evolvus/github-smart:latest`)
- `-p, --port`: Port to expose (default: 8080)
- `-n, --name`: Container name (default: github-smart)
- `-d, --data-dir`: Data directory (default: ./data)
- `-b, --build-local`: Build Docker image locally instead of pulling

## Examples

```bash
# Basic deployment
./deploy.sh -o evolvus -t ghp_xxxxxxxx

# Custom port and container name
./deploy.sh -o evolvus -t ghp_xxxxxxxx -p 9000 -n my-github-smart

# Custom data directory
./deploy.sh -o syneca -t ghp_xxxxxxxx -d /opt/github-smart-data

# Build image locally
./deploy.sh -o syneca -t ghp_xxxxxxxx -b
```

## What the Script Does

1. **Checks Docker**: Verifies Docker is installed and running
2. **Validates Inputs**: Ensures required parameters are provided
3. **Creates Data Directory**: Sets up persistent storage
4. **Cleans Up**: Stops and removes any existing containers
5. **Pulls/Builds Image**: Downloads the latest Docker image from GitHub Packages or builds locally
6. **Runs Container**: Starts the application with proper configuration
7. **Verifies Status**: Checks that the container is running properly

## Container Management

### View Logs
```bash
docker logs github-smart
```

### Stop Container
```bash
docker stop github-smart
```

### Remove Container
```bash
docker rm github-smart
```

### Restart Container
```bash
docker restart github-smart
```

## Accessing the Application

Once deployed, the application will be available at:
- **URL**: `http://localhost:8080` (or your custom port)
- **Default Port**: 8080

## Troubleshooting

### Docker Not Running
```bash
# Start Docker Desktop (Windows/Mac)
# Or start Docker service (Linux)
sudo systemctl start docker
```

### Permission Issues
```bash
# Add user to docker group (Linux)
sudo usermod -aG docker $USER
# Log out and log back in
```

### Container Fails to Start
```bash
# Check container logs
docker logs github-smart

# Check if port is already in use
netstat -tulpn | grep :8080
```

### GitHub Token Issues
- Ensure your token has the correct permissions
- Check if the token is expired
- Verify the organization name is correct

### GitHub Container Registry Access Issues
If you encounter "denied: denied" errors when trying to pull from GitHub Container Registry:
```bash
# Build the image locally instead
./deploy.sh -o syneca -t YOUR_TOKEN -b

# Or manually build and deploy
docker build -t ghcr.io/evolvus/github-smart:latest .
./deploy.sh -o syneca -t YOUR_TOKEN
```

## Development

For developers who want to build and push their own images:

1. **Build Image**:
   ```bash
   docker build -t ghcr.io/YOUR_ORG/github-smart:latest .
   ```

2. **Push to GitHub Packages**:
   ```bash
   echo $GITHUB_TOKEN | docker login ghcr.io -u evolvus --password-stdin
   docker push ghcr.io/evolvus/github-smart:latest
   ```

## Security Notes

- Never commit GitHub tokens to version control
- Use environment variables or secure secret management
- Regularly rotate your GitHub Personal Access Tokens
- Consider using GitHub Actions for automated deployments

## Support

If you encounter issues:

1. Check the container logs: `docker logs github-smart`
2. Verify Docker is running: `docker info`
3. Ensure your GitHub token has the correct permissions
4. Check the [GitHub Smart documentation](https://github.com/evolvus/github-smart) for more details 