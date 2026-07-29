# Security policy

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability. Use the repository
host’s private security-advisory feature and include:

- affected commit or release;
- reproducible steps;
- impact and required preconditions;
- a minimal proof of concept with secrets and personal data removed;
- any suggested mitigation.

Maintainers should acknowledge a complete report within seven days. Disclosure
timing is coordinated after a fix is available.

## Supported versions

Until the first stable release, only the latest commit on the default branch is
supported. After 1.0, the latest minor release receives security fixes.

## Security baseline

Deployments must use supported PHP, Laravel, MySQL, and Composer versions; HTTPS;
a unique application key; a verified Telegram webhook secret; least-privilege
database credentials; and non-debug production configuration. Run
`composer audit --locked` before deployment and monitor upstream advisories.
