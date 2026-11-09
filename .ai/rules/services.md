---
paths:
  - app/Services/CompatibilityChecker.php
---

# Services

## Abandoned packages get their own status and are never critical
Settled product decision (after going back and forth): an abandoned/deprecated package always gets `PackageStatus::Abandoned` ("Eingestellt", neutral grey badge), regardless of constraints or age. `critical` means "an actively maintained package needs action" — an unmaintained package has nothing left to do, so it must never be `critical`. Don't fold abandoned back into critical/unclear or rank it by constraints.
