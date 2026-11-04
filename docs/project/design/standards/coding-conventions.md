# Engineering Coding Conventions & Implementation Standards
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-COD-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** PSR-12 Extended Coding Style, PHP 8+ Modern Best Practices  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. PHP Language & Environment Standards

1. **Target Runtime:** PHP `>= 8.0` (Verified production environment: PHP 8.5.12).
2. **Strict Typing:** All newly created PHP service, helper, validator, and repository files must declare strict types as the very first statement:
   ```php
   <?php
   declare(strict_types=1);
   ```
3. **Explicit Type Declarations:** Every function and class method must declare explicit parameter types and return types. Union types (`string|null`, `int|float`) and `void` must be utilized where applicable:
   ```php
   public function updateOdometer(int $vehicleId, int $newMileage): bool
   {
       // Implementation
   }
   ```
4. **Visibility Modifiers:** All properties and methods must declare explicit visibility (`public`, `protected`, `private`).
5. **Constructor Property Promotion:** Use PHP 8 constructor promotion for simple value objects and services:
   ```php
   public function __construct(
       private readonly PDO $db,
       private readonly MailHelper $mailer
   ) {}
   ```

---

## 2. Formatting & Syntax Guidelines (PSR-12)

1. **Indentation:** 4 spaces (never tabs).
2. **Line Length:** Soft limit of 120 characters; lines should not exceed 160 characters.
3. **Brace Placement:**
   - Classes and methods: Opening brace on a new line.
   - Control structures (`if`, `for`, `foreach`, `while`): Opening brace on the same line.
   ```php
   class VehicleService
   {
       public function archiveVehicle(int $vehicleId): void
       {
           if ($vehicleId <= 0) {
               throw new InvalidArgumentException("Vehicle ID must be positive.");
           }
       }
   }
   ```
4. **Control Structures:** Always use curly braces for control structures, even for single-line bodies:
   ```php
   // FORBIDDEN:
   if ($error) return false;

   // MANDATORY:
   if ($error) {
       return false;
   }
   ```

---

## 3. PHPDoc & Code Documentation Standards

All public methods must include standard PHPDoc docblocks specifying parameter descriptions, exception types, and return values:
```php
/**
 * Calculates dynamic maintenance status based on dual threshold criteria.
 *
 * @param DateTimeInterface $targetDate Scheduled maintenance due date.
 * @param int $targetMileage Scheduled maintenance due odometer reading.
 * @param int $currentMileage Current vehicle odometer reading.
 * @return string Dynamic status: OVERDUE, DUE, UPCOMING, or GOOD.
 * @throws InvalidArgumentException If odometer reading is negative.
 */
public function calculateStatus(DateTimeInterface $targetDate, int $targetMileage, int $currentMileage): string
```

---

## 4. Frontend Standards (HTML / CSS / JavaScript)

1. **HTML5:**
   - Use semantic elements (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`).
   - All forms must include explicit `<label for="{id}">` associations.
2. **CSS:**
   - Never use inline `style="..."` attributes on production elements.
   - Use design token variables (`var(--color-brand-navy)`, `var(--space-4)`).
   - Class names use lowercase hyphenated words (`.vehicle-card`, `.status-badge-overdue`).
3. **Vanilla JavaScript:**
   - ES6+ syntax (`const`, `let`, arrow functions, template literals, `async/await`).
   - Wrap scripts in IIFEs or ES modules to avoid polluting global `window` scope.
   - Event listeners bound via `addEventListener()` (never inline `onclick="..."`).

