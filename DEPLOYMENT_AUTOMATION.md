# QSA Deployment Automation

This project should deploy through GitHub, not through repeated manual WHM/cPanel terminal work.

Target flow:

```text
Codex updates code -> GitHub main branch -> GitHub Actions -> VPS deploy script -> qsa.vervelogic.com
```

## Current Blockers

Codex currently has two environment limitations:

1. Terminal Git cannot reach GitHub from this Codex session.
   - Error seen: `Could not resolve host: github.com`
   - This is a network/DNS restriction in the Codex environment.

2. The Codex GitHub connector can read the repository but cannot write.
   - Error seen: `403 Resource not accessible by integration`
   - This means the GitHub integration needs write access to the repository.

Until one of these is fixed, Codex can prepare commits locally but cannot reliably push them to GitHub.

## Permanent Solution

Use GitHub as the deployment source of truth.

### 1. Give Codex GitHub Write Access

In GitHub, make sure the Codex/GitHub integration has access to:

```text
vervelogic/quick-seo-analysis-laravel
```

Required permissions:

```text
Contents: Read and write
Workflows: Read and write
Metadata: Read
```

If the repository is under the `vervelogic` organization, the organization owner may need to approve the integration.

### 2. Add GitHub Actions Secrets

In GitHub:

```text
Repo -> Settings -> Secrets and variables -> Actions -> New repository secret
```

Add:

```text
VPS_HOST=72.61.240.98
VPS_PORT=3681
VPS_USER=alphaver
VPS_SSH_KEY=<private SSH key for alphaver deploy access>
```

Do not commit or share `VPS_SSH_KEY` in chat or code.

Recommended: create a dedicated deploy key on your Mac or VPS:

```bash
ssh-keygen -t ed25519 -C "qsa-github-actions-deploy" -f qsa_github_actions_deploy
```

Add the public key to:

```text
/home/alphaver/.ssh/authorized_keys
```

Add the private key content to GitHub secret:

```text
VPS_SSH_KEY
```

### 3. Install VPS Deploy Script Once

Create:

```text
/home/alphaver/deploy-qsa.sh
```

With:

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/alphaver/public_html/quick-seo-analysis"

cd "$APP_DIR"

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci || npm install
npm run build
php artisan optimize:clear
php artisan filament:assets
php artisan route:clear
php artisan view:clear
php artisan config:clear

if chown -R alphaver:alphaver "$APP_DIR" 2>/dev/null; then
    echo "Ownership refreshed for $APP_DIR"
else
    echo "Skipping ownership refresh; run it once as root if permissions need repair."
fi

find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Then run:

```bash
chmod +x /home/alphaver/deploy-qsa.sh
```

### 4. Add GitHub Actions Workflow

The repo should contain:

```text
.github/workflows/deploy.yml
```

With:

```yaml
name: Deploy QSA

on:
  push:
    branches:
      - main
  workflow_dispatch:

jobs:
  deploy:
    name: Deploy to WHM VPS
    runs-on: ubuntu-latest

    steps:
      - name: Run VPS deploy script
        uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.VPS_HOST }}
          port: ${{ secrets.VPS_PORT }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script_stop: true
          script: |
            /home/alphaver/deploy-qsa.sh
```

## Future Working Pattern

After the setup is complete:

1. Codex edits the Laravel project.
2. Codex commits changes.
3. Codex pushes to `main`.
4. GitHub Actions connects to VPS.
5. VPS runs `/home/alphaver/deploy-qsa.sh`.
6. Site updates automatically.

No WHM terminal work should be needed for normal code deployments.

## If Codex Still Cannot Push

If GitHub write access is still blocked, use this fallback from a local machine that can reach GitHub:

```bash
cd /Users/Abhishek/Documents/Codex/2026-06-20/you-are-building-a-new-laravel/work/qsa-deploy-work-20260621085054
git push origin main
```

This is only a fallback. The permanent fix is giving Codex GitHub write access.
