<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class StateTransition extends Model
{
    protected $fillable = [
        'model_type',
        'status_field',
        'transition_name',
        'from_state',
        'to_state',
    ];

    // Get transitions for a specific model type and status field
    public static function getTransitionsForModelAndField(string $modelType, string $statusField)
    {
        return self::where('model_type', $modelType)
                    ->where('status_field', $statusField)
                    ->get();
    }

    // Get transition by name and status field
    public static function getTransition(string $modelType, string $transitionName, string $statusField)
    {
        return self::where('model_type', $modelType)
                    ->where('transition_name', $transitionName)
                    ->where('status_field', $statusField)
                    ->first();
    }

    /**
     * Register multiple state transitions for a model based on an array structure.
     *
     * @param string $modelType   The model class (e.g., Document::class).
     * @param array $transitions  The transitions array with 'from' and 'to' states.
     *                            Example structure:
     *                            [
     *                                'approve' => [
     *                                    'from' => ['pending', 'submitted'],
     *                                    'to' => 'approved',
     *                                ],
     *                                'reject' => [
     *                                    'from' => ['pending', 'submitted'],
     *                                    'to' => 'rejected',
     *                                ],
     *                                'verify' => [
     *                                    'from' => ['submitted'],
     *                                    'to' => 'verified',
     *                                ],
     *                            ]
     * @param string $statusField The status field to update (e.g., 'verification_status').
     * @return void
     */
    public static function register(string $modelType, array $transitions, string $statusField = 'status') {
        foreach ($transitions as $transitionName => $transitionData) {
            $fromStates = (array) $transitionData['from'];  // Ensure it's an array
            $toState = $transitionData['to'];

            foreach ($fromStates as $fromState) {
                // try {
                    self::create([
                        'model_type'      => $modelType,
                        'transition_name' => $transitionName,
                        'from_state'      => $fromState,
                        'to_state'        => $toState,
                        'status_field'    => $statusField,
                    ]);
                // } catch (\Exception $e) {
                //     Log::error('Error creating state transition: ' . $e->getMessage());
                // }
            }
        }
    }
}
