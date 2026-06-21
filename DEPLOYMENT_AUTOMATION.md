# QSA Deployment Automation

This project should deploy through GitHub, not through repeated manual WHM/cPanel terminal work.

Target permanent flow:

```text
Codex updates code -> GitHub main branch -> GitHub Actions -> self-hosted VPS runner -> /home/alphaver/deploy-qsa.sh -> qsa.vervelogic.com
```

## Recommended Permanent Setup

Use a GitHub self-hosted runner installed on the VPS. This avoids GitHub cloud runners needing to SSH into the server on every deploy.

### Why This Is Better

- No repeated WHM terminal deployment work.
- No dependency on GitHub cloud runner SSH access to the VPS.
- Deploy commands run locally on the same server that hosts QSA.
- Codex only needs to push to GitHub main.

## One-Time WHM/VPS Setup

Run these once from WHM terminal as `root`.

### 1. Open The GitHub Runner Page

In GitHub:

```text
vervelogic/quick-seo-analysis-laravel -> Settings -> Actions -> Runners -> New self-hosted runner
```

Choose:

```text
Linux
x64
```

GitHub will show a temporary registration token. Use that token in the commands below where `RUNNER_TOKEN_FROM_GITHUB` appears.

### 2. Install The Runner As alphaver

```bash
mkdir -p /home/alphaver/actions-runner/qsa
chown -R alphaver:alphaver /home/alphaver/actions-runner

sudo -u alphaver bash <<'EOF'
set -e
cd /home/alphaver/actions-runner/qsa
curl -o actions-runner-linux-x64.tar.gz -L https://github.com/actions/runner/releases/latest/download/actions-runner-linux-x64-2.325.0.tar.gz || true
if [ ! -s actions-runner-linux-x64.tar.gz ]; then
  echo "Download failed. Use the exact download command shown by GitHub's runner setup page."
  exit 1
fi
tar xzf actions-runner-linux-x64.tar.gz
./config.sh \
  --url https://github.com/vervelogic/quick-seo-analysis-laravel \
  --token RUNNER_TOKEN_FROM_GITHUB \
  --name qsa-whm-vps \
  --labels qsa,production,whm \
  --work _work \
  --unattended
EOF
```

If the GitHub page shows a newer runner download URL, use GitHub's exact download line instead of the `curl` line above.

### 3. Install Runner Service

```bash
cd /home/alphaver/actions-runner/qsa
./svc.sh install alphaver
./svc.sh start
./svc.sh status
```

Expected status:

```text
active (running)
```

### 4. Install Or Refresh Deploy Script

```bash
cd /home/alphaver/public_html/quick-seo-analysis
git fetch origin main
git reset --hard origin/main
cp deploy/deploy-qsa.sh /home/alphaver/deploy-qsa.sh
chmod +x /home/alphaver/deploy-qsa.sh
chown alphaver:alphaver /home/alphaver/deploy-qsa.sh
```

### 5. Test Deployment Once

```bash
sudo -u alphaver /home/alphaver/deploy-qsa.sh
```

Then confirm:

```bash
cd /home/alphaver/public_html/quick-seo-analysis
git rev-parse HEAD
curl -I https://qsa.vervelogic.com
```

## Normal Future Working Pattern

After the setup is complete:

1. Codex edits the Laravel project.
2. Codex commits changes.
3. Codex pushes to `main`.
4. GitHub Actions runs on the VPS self-hosted runner labeled `qsa`.
5. VPS runs `/home/alphaver/deploy-qsa.sh` locally.
6. Site updates automatically.

No WHM terminal work should be needed for normal code deployments.

## Manual SSH Fallback

The workflow still includes a manual SSH fallback for emergency use only.

In GitHub:

```text
Actions -> Deploy QSA -> Run workflow -> deploy_mode: ssh-fallback
```

Required repository secrets for fallback only:

```text
VPS_HOST
VPS_PORT
VPS_USER
VPS_SSH_KEY
```

Do not commit or share `VPS_SSH_KEY` in chat or code.

## Troubleshooting

### Workflow Is Queued Forever

The self-hosted runner is not online or does not have the `qsa` label.

Check on VPS:

```bash
cd /home/alphaver/actions-runner/qsa
./svc.sh status
```

### Deploy Script Permission Denied

```bash
chmod +x /home/alphaver/deploy-qsa.sh
chown alphaver:alphaver /home/alphaver/deploy-qsa.sh
```

### Git Pull Or Reset Fails

Make sure the live checkout belongs to `alphaver`:

```bash
chown -R alphaver:alphaver /home/alphaver/public_html/quick-seo-analysis
```

### Composer Command Fails

The deploy script uses cPanel PHP explicitly:

```text
/opt/cpanel/ea-php83/root/usr/bin/php
/usr/local/bin/composer
```

If PHP version changes, update `deploy/deploy-qsa.sh` and `/home/alphaver/deploy-qsa.sh`.
