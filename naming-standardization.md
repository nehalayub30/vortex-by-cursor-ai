# VORTEX Naming Standardization

This document identifies the naming conventions that should be used across the codebase to ensure consistency.

## Naming Prefix Standard

All classes should use the `VORTEX_` prefix (instead of variations like `Vortex_` or `THORIUS_`). 

## Class Naming Patterns

| Component Type | Naming Pattern | Example |
|----------------|----------------|---------|
| Admin Classes | `VORTEX_Admin_*` | `VORTEX_Admin_Settings` |
| Frontend Classes | `VORTEX_Frontend_*` | `VORTEX_Frontend_Renderer` |
| API Classes | `VORTEX_API_*` | `VORTEX_API_Client` |
| Blockchain Classes | `VORTEX_Blockchain_*` | `VORTEX_Blockchain_Manager` |
| AI Agent Classes | `VORTEX_AI_Agent_*` | `VORTEX_AI_Agent_Base` |
| DAO Classes | `VORTEX_DAO_*` | `VORTEX_DAO_Manager` |

## Classes to Rename

The following classes should be renamed to conform to the standardized naming convention:

* `Vortex_Thorius_*` → `VORTEX_AI_Agent_*`
* `THORIUS_*` → `VORTEX_AI_Agent_*`
* `Vortex_Huraii_*` → `VORTEX_AI_*`
* `HURAII_*` → `VORTEX_AI_*`

## File Naming Convention

PHP class files should be named according to their class name with the following format:
`class-{prefix}-{name}.php` where:
- `{prefix}` is the lowercase component prefix (e.g., `vortex`)
- `{name}` is the lowercase, hyphenated version of the class name

Example: The class `VORTEX_Admin_Settings` should be in a file named `class-vortex-admin-settings.php` 