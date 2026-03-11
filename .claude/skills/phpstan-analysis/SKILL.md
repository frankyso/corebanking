---
name: phpstan-analysis
description: "Runs PHPStan static analysis at max level using Larastan. Activates when fixing type errors, adding type hints, running static analysis, debugging PHPStan failures, or when the user mentions PHPStan, Larastan, static analysis, type safety, or type declarations."
license: MIT
metadata:
  author: custom
---

# PHPStan Max Level with Larastan

## When to Apply

Activate this skill when:

- Running or configuring PHPStan/Larastan
- Fixing type errors or adding type declarations
- Working with PHPStan baseline management
- Debugging static analysis failures
- Adding return types, parameter types, or generics

## Configuration

This project uses PHPStan at **max level** with Larastan.

### Config File: `phpstan.neon`
```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - app/
    level: max
    treatPhpDocTypesAsCertain: false
```

### Baseline
The project uses `phpstan-baseline.neon` to track known errors. New code must pass at max level without adding to the baseline.

## Running PHPStan

```bash
# Standard analysis
vendor/bin/phpstan analyse --memory-limit=512M

# Generate/update baseline (only when deliberately accepting known errors)
vendor/bin/phpstan analyse --memory-limit=512M --generate-baseline
```

## Common Error Patterns & Fixes

### 1. Missing Return Types
```php
// BAD
public function getTotal() { return $this->amount * $this->qty; }

// GOOD
public function getTotal(): float { return $this->amount * $this->qty; }
```

### 2. Null Safety in Auth Context
```php
// BAD - user() can be null
$request->user()->id

// GOOD - use auth()->id() or null-safe operator
auth()->id()
$request->user()?->id
```

### 3. Model Property Access on Mixed
```php
// BAD - PHPStan sees $query result as mixed
$account->customer->name

// GOOD - type hint the relationship return
/** @return BelongsTo<Customer, $this> */
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}
```

### 4. Array Shape Types
```php
// BAD - no value type in iterable
public function getData(): array { ... }

// GOOD - specify array shape
/** @return array{total: float, count: int, items: array<int, string>} */
public function getData(): array { ... }
```

### 5. Collection Generics
```php
// BAD
public function getUsers(): Collection { ... }

// GOOD
/** @return Collection<int, User> */
public function getUsers(): Collection { ... }
```

### 6. Fillable/Casts Type Hints on Models
```php
/** @var array<int, string> */
protected $fillable = ['name', 'email'];

/** @var array<string, string> */
protected $casts = ['is_active' => 'boolean'];
```

### 7. handleRecordCreation in Filament
When using try/catch with `$this->halt()`, PHPStan doesn't know halt() always throws:
```php
protected function handleRecordCreation(array $data): Model
{
    try {
        $record = app(MyService::class)->create($data);
    } catch (\InvalidArgumentException $e) {
        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        $this->halt();

        throw new \RuntimeException('Unreachable');
    }

    return $record;
}
```

### 8. Scope Methods
```php
// BAD - missing types
public function scopeActive($query) { ... }

// GOOD - explicit types
/** @param Builder<static> $query */
public function scopeActive(Builder $query): Builder
{
    return $query->where('status', 'active');
}
```

### 9. Enum Comparison Issues
When comparing model attributes cast to enums:
```php
// If treatPhpDocTypesAsCertain is true, PHPStan may flag enum comparisons
// Set treatPhpDocTypesAsCertain: false in phpstan.neon to avoid false positives
```

### 10. Inline Suppression (Last Resort)
```php
/** @phpstan-ignore-next-line */
$result = $someComplexExpression;
```

## Workflow

1. Write code with proper type declarations from the start
2. Run `vendor/bin/phpstan analyse --memory-limit=512M` frequently
3. Fix errors immediately - don't let them accumulate
4. Only add to baseline for third-party/framework issues you can't control
5. Regenerate baseline sparingly with `--generate-baseline`

## Common Pitfalls

1. **Don't lower the level** - Fix errors instead of reducing strictness
2. **Don't add everything to baseline** - Baseline is for legacy code, not new code
3. **Use Larastan extension** - It understands Laravel magic (facades, relationships, etc.)
4. **Set memory limit** - Large projects need `--memory-limit=512M` or higher
5. **treatPhpDocTypesAsCertain: false** - Prevents false positives with enum casts
