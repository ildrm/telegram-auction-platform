## Summary

Describe the user-visible outcome and the business invariant being changed.

## Verification

- [ ] Authorization is enforced.
- [ ] Inputs and business rules are validated.
- [ ] Multi-record writes are transactional.
- [ ] Important mutations are audited.
- [ ] User-facing messages are localized.
- [ ] Tests cover success and critical failure paths.
- [ ] `composer test` passes.
- [ ] `vendor/bin/pint --test` passes.
- [ ] Documentation is updated.

## Migration and operations

Describe schema changes, rollback behavior, queue/scheduler changes, and any
deployment ordering requirements.
