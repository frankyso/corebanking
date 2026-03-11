---
name: filament-development
description: "Develops Filament v5 admin panels, resources, pages, widgets, and forms. Activates when creating or editing Filament resources, pages, widgets, relation managers, actions, tables, forms, infolists, navigation, or policies; or when the user mentions Filament, admin panel, resource, CRUD, dashboard widget, or Filament component."
license: MIT
metadata:
  author: custom
---

# Filament v5 Development

## When to Apply

Activate this skill when:

- Creating or editing Filament resources, pages, or widgets
- Working with Filament forms, tables, infolists, or actions
- Configuring navigation, panels, or policies
- Building dashboard widgets (stats, charts, tables)
- Debugging Filament rendering or authorization issues

## Documentation

Use `search-docs` with `packages: ["filament/filament"]` for version-specific docs.

## Critical Namespace Changes in Filament v5

Filament v5 unified the schema system. These are the correct namespaces:

### Layout Components (Shared)
```php
// CORRECT - Filament v5
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;

// WRONG - Do NOT use these (Filament v3/v4)
// use Filament\Forms\Components\Section;
// use Filament\Forms\Components\Fieldset;
```

### Form Components
```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
```

### Infolist Components
```php
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
```

### Get/Set Utilities
```php
// CORRECT - Filament v5
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// WRONG - Do NOT use these (Filament v3/v4)
// use Filament\Forms\Get;
// use Filament\Forms\Set;
```

### Schema
```php
use Filament\Schemas\Schema;
```

### Table Components
```php
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
```

### Actions
```php
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
```

### Navigation
```php
use Filament\Navigation\NavigationGroup;
```

## Resource Structure

### Basic Resource
```php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ExampleResource extends Resource
{
    protected static ?string $model = Example::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|UnitEnum|null $navigationGroup = 'Group Name';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Label';
    protected static ?string $pluralModelLabel = 'Labels';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Form fields
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        // Used by ViewRecord pages instead of disabled forms
        return $schema->components([
            // TextEntry fields
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
```

### Table Actions (v5 syntax)
```php
// Record-level actions
->recordActions([
    ViewAction::make(),
    EditAction::make(),
    DeleteAction::make(),
])

// Toolbar/bulk actions
->toolbarActions([
    BulkActionGroup::make([
        DeleteBulkAction::make(),
    ]),
])
```

## Widget Types

### Stats Overview Widget
```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('Label', 'Value')
                ->description('Description')
                ->icon('heroicon-o-users')
                ->color('success'),
        ];
    }
}
```

### Chart Widget
```php
use Filament\Widgets\ChartWidget;

class MyChartWidget extends ChartWidget
{
    // NOTE: $heading is NOT static in v5
    protected ?string $heading = 'Chart Title';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        return [
            'datasets' => [['label' => 'Data', 'data' => [1, 2, 3]]],
            'labels' => ['A', 'B', 'C'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // line, bar, pie, doughnut, polarArea, radar
    }
}
```

## Authorization with Model Policies

Filament v5 automatically observes Laravel model policies:

```php
// Policy methods that Filament checks:
viewAny()    -> controls list page access & navigation visibility
view()       -> controls view page access
create()     -> controls create page access & button visibility
update()     -> controls edit page access & button visibility
delete()     -> controls delete action visibility
deleteAny()  -> controls bulk delete visibility
```

Custom pages need manual `canAccess()`:
```php
public static function canAccess(): bool
{
    return auth()->user()?->can('permission.name') ?? false;
}
```

## Navigation Group Registration

In `AdminPanelProvider.php`:
```php
->navigationGroups([
    NavigationGroup::make('Group Name')
        ->icon('heroicon-o-home'),
])
```

## Common Pitfalls

1. **Section** - Use `Filament\Schemas\Components\Section`, NOT `Filament\Forms\Components\Section`
2. **Get/Set** - Use `Filament\Schemas\Components\Utilities\Get`, NOT `Filament\Forms\Get`
3. **ChartWidget::$heading** - Is NOT static in v5, use `protected ?string $heading`
4. **Table actions** - Use `->recordActions()` and `->toolbarActions()` instead of old `->actions()` and `->bulkActions()`
5. **Infolist for View pages** - Define `infolist()` method on resource for read-only view pages instead of disabled forms
6. **NavigationIcon type** - Use `string|BackedEnum|null` type hint
7. **NavigationGroup type** - Use `string|UnitEnum|null` type hint
