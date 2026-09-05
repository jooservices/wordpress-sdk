# Release and rollback

## Release checklist

1. Confirm the worktree, Composer lockfile, changelog date, and version links.
2. Run `make validate`, `make ci`, `make clean-consumer`, and
   `make integration`; also run locked dependency, workflow, shell, secret,
   debug, and artifact audits.
3. Merge the feature PR into protected `develop` only after every required
   check is green.
4. Create `release/<version>` from current `develop`. Limit it to release
   metadata/docs and open its PR into protected `master`.
5. After all required checks are green, merge the release PR. Create an
   annotated `v<version>` tag on the resulting `master` commit and push it.
6. Verify the tag workflow succeeded and the GitHub Release and SBOM exist.
7. Verify the peeled tag commit is reachable from `master`; verify Packagist
   and a clean install of the exact version when package access permits.
8. Merge `master` back into `develop` through a green PR.

## Rollback

Do not move or overwrite a published tag. If the release is defective, stop
further distribution, document the impact, and prepare either a patch release
from `master` or a revert PR according to compatibility and security risk.
Consumers roll back by restoring their previous `composer.json` constraint
and lockfile, then running their complete application test/deployment gates.

If a GitHub Release was published with incorrect assets but the tag and source
are correct, replace only the release asset through an audited maintainer
operation. If source or tag provenance is wrong, publish a new corrected
version; never silently retag an existing version.
