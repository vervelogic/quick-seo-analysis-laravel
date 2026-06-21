# QSA Deployment Automation

This project should deploy through GitHub, not through repeated manual WHM/cPanel terminal work.

Target flow:

```text
Codex updates code -> GitHub main branch -> GitHub Actions -> VPS deploy script -> qsa.vervelogic.com
```

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

### 3. Install VPS Deploy Script Once

Create:

```text
/home/alphaver/deploy-qsa.sh
```

With the same content as:

```text
deploy/deploy-qsa.sh
```

Then run:

```bash
chmod +x /home/alphaver/deploy-qsa.sh
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
