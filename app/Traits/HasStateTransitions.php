<?php

namespace App\Traits;

use App\Models\StateTransition;
use App\Models\StateTransitionLog;
use Exception;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStateTransitions
{
    public function canTransition(string $transition, string $statusField = 'status'): bool
    {
        $modelType = get_class($this);
        $stateTransition = StateTransition::getTransition($modelType, $transition, $statusField);

        if ($stateTransition && $this->{$statusField} === $stateTransition->from_state) {
            return true;
        }

        return false;
    }

    public function applyTransition(string $transition, string $statusField = 'status')
    {
        $modelType = get_class($this);
        $stateTransition = StateTransition::getTransition($modelType, $transition, $statusField);

        if ($stateTransition && $this->{$statusField} === $stateTransition->from_state) {
            $previousState = $this->{$statusField};

            // Apply the transition to the correct status field
            $this->{$statusField} = $stateTransition->to_state;
            $this->save();

            $this->logTransition($modelType, $this->id, $statusField, $transition, $previousState, $this->{$statusField});
        } else {
            throw new Exception("Cannot apply transition: {$transition} for field {$statusField}");
        }
    }

    protected function logTransition(
        string $modelType,
        int $modelId,
        string $statusField,
        string $transitionName,
        string $fromState,
        string $toState
    ) {
        StateTransitionLog::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'status_field' => $statusField,
            'transition_name' => $transitionName,
            'from_state' => $fromState,
            'to_state' => $toState,
        ]);
    }

    public static function getAvailableTransitions(string $statusField = 'status'): array
    {
        return StateTransition::getTransitionsForModelAndField(get_called_class(), $statusField)
            ->pluck('transition_name')
            ->toArray();
    }

    public static function getAvailableStates(string $statusField = 'status'): array
    {
        return StateTransition::getTransitionsForModelAndField(get_called_class(), $statusField)
            ->pluck('to_state')
            ->toArray();
    }

    public function getPossibleTransitions(string $statusField = 'status'): array
    {
        $modelType = get_class($this);
        $currentState = $this->{$statusField};

        return StateTransition::where('model_type', $modelType)
            ->where('status_field', $statusField)
            ->where('from_state', $currentState)
            ->pluck('transition_name')
            ->toArray();
    }

    public function transitionHistories(): MorphMany
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->morphMany(StateTransitionLog::class, 'model');
    }
}
