# Deployment Example with Environment Variables

## Quick Deployment with Environment Variables

### Step 1: Set your GitHub credentials
```bash
# Set your GitHub Personal Access Token and organization
export GITHUB_TOKEN=ghp_your_actual_token_here
export GITHUB_ORG=your-organization-name
```

### Step 2: Download and run the deployment script
```bash
# Create directory
mkdir github-smart-production
cd github-smart-production

# Download the script
curl -O https://raw.githubusercontent.com/evolvus/github-smart/main/scripts/deploy-production.sh
chmod +x deploy-production.sh

# Run the script (it will automatically use your environment variables)
./deploy-production.sh
```

### Step 3: Access your application
```bash
# The application will be available at:
http://your-server-ip
```

## Alternative: One-liner deployment
```bash
# Set environment variables and deploy in one go
GITHUB_TOKEN=ghp_your_token GITHUB_ORG=your-org ./deploy-production.sh
```

## What happens automatically:
- ✅ Secure passwords are generated
- ✅ Docker containers are started
- ✅ Health checks are performed
- ✅ GitHub token is configured from environment variable
- ✅ Application is ready to use

## No manual editing required!
The deployment script now reads your GitHub credentials from environment variables, so you don't need to edit any files manually. 