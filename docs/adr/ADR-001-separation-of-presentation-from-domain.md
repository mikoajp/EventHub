# ADR-001: Separation of Presentation from Domain

Status: Accepted
Date: 2025-10-24

Context
- Entities contained presentation/serialization concerns, leaking domain to API.

Decision
- Use Request/Response DTOs and Presenters in Presentation layer.
- Prohibit direct serialization of Entities.
- Enforce dependency rule: Presentation -> Application -> Domain; Infrastructure -> Application/Domain.

Consequences
+ Clear separation of concerns and testability.
- More classes and mapping code.

Implementation
- Add Presenters and Response DTOs for Users/Events first.
- Update controllers/ApiPlatform state providers/processors to use DTOs.
