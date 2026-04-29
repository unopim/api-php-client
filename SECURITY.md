# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 1.x     | ✅        |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security problems.**

Email **support@webkul.com** with:

- A description of the issue and impact
- Steps to reproduce
- Affected version(s)
- Any suggested fix or mitigation

You will receive an acknowledgement within **3 business days** and a status update within **10 business days**. Coordinated disclosure is appreciated; please give us reasonable time to release a patch before public disclosure.

## Scope

In scope:
- Authentication / token handling
- HTTP transport (TLS validation, header injection)
- Input validation in DTOs and request builders

Out of scope:
- Vulnerabilities in dependencies (report to the upstream project)
- Issues in the UnoPim server itself (report on the main UnoPim repository)
