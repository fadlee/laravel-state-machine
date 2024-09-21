# State Machine System for Laravel

This project implements a flexible and robust state transition system for Laravel models. It allows you to define, apply, and log state transitions for different status fields in your models.

## Features

- Define multiple state transitions for different status fields
- Apply state transitions to models
- Log state transition history
- Check for valid transitions
- Retrieve available transitions and states for a model
- Easy integration with existing Laravel models

## Installation

1. Clone this repository to your local machine:
   ```
   git clone https://github.com/fadlee/laravel-state-machine.git
   ```

2. Install the dependencies:
   ```
   composer install
   ```

3. Run the migrations:
   ```
   php artisan migrate
   ```

## Usage

### 1. Set up your model

Add the `HasStateTransitions` trait to your model:

```php
use App\Traits\HasStateTransitions;

class Document extends Model
{
    use HasStateTransitions;

    // ...
}
```

### 2. Define state transitions

Use the `StateTransition::register()` method to define state transitions for your model:

```php
use App\Models\Document;
use App\Models\StateTransition;

StateTransition::register(Document::class, [
    'submit' => [
        'from' => ['pending'],
        'to' => 'submitted',
    ],
    'verify' => [
        'from' => ['submitted'],
        'to' => 'verified',
    ],
], 'verification_status');

StateTransition::register(Document::class, [
    'publish' => [
        'from' => ['draft'],
        'to' => 'published',
    ],
    'archive' => [
        'from' => ['published'],
        'to' => 'archived',
    ],
], 'status');
```

### 3. Apply transitions

You can now apply transitions to your model instances:

```php
$document = Document::find(1);
$document->applyTransition('submit', 'verification_status');
$document->applyTransition('publish', 'status');
```

### 4. Check for valid transitions

```php
if ($document->canTransition('verify', 'verification_status')) {
    // Apply the transition
}
```

### 5. Get available transitions and states

```php
$availableTransitions = Document::getAvailableTransitions('status');
$availableStates = Document::getAvailableStates('status');
$possibleTransitions = $document->getPossibleTransitions('status');
```

### 6. Access transition history

```php
$transitionLogs = $document->transitionLogs
```

## Testing

Run the test suite with:

```
php artisan test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).