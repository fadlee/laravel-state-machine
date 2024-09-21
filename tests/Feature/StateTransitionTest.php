<?php

use App\Models\Document;
use App\Models\StateTransition;
use App\Models\StateTransitionLog;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Seed initial state transitions for the Document model

    // Verification status transitions
    StateTransition::register(Document::class, [
        'submit' => [
            'from' => ['pending'],
            'to' => 'submitted',
        ],
        'reject' => [
            'from' => ['pending'],
            'to' => 'rejected',
        ],
        'verify' => [
            'from' => ['submitted'],
            'to' => 'verified',
        ],
    ], 'verification_status');

    // Main status transitions
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
});

it('can apply main status transition and log history', function () {

    /** @var Document $document */
    $document = Document::create([
        'title' => 'Document 1',
        'verification_status' => 'pending',
        'status' => 'draft',
    ]);

    expect($document->status)->toBe('draft');

    $document->applyTransition('publish', 'status');

    // Reload the document
    $document->refresh();

    expect($document->status)->toBe('published');

    // Check if the transition history was recorded
    $history = StateTransitionLog::where('model_id', $document->id)
                ->where('status_field', 'status')
                ->first();

    expect($history)->not()->toBeNull();
    expect($history->from_state)->toBe('draft');
    expect($history->to_state)->toBe('published');
    expect($history->transition_name)->toBe('publish');
    expect($history->model_type)->toBe(Document::class);
});

// TODO: fix this test
it('can apply verification status transition and log history', function () {
    expect(Document::getAvailableTransitions())
        ->toHaveCount(2);

    expect(Document::getAvailableTransitions('verification_status'))
        ->toHaveCount(3);

    /** @var Document $document */
    $document = Document::create([
        'title' => 'Document 2',
        'verification_status' => 'pending',
        'status' => 'draft',
    ]);

    // Assert the document is in the initial 'pending' verification state
    expect($document->verification_status)->toBe('pending');

    expect($document->getPossibleTransitions('verification_status'))
        ->toHaveCount(2);

    $document->applyTransition('submit', 'verification_status');

    // Reload the document
    $document->refresh();

    // Assert that the main status has been updated
    expect($document->verification_status)->toBe('submitted');

    $document->applyTransition('verify', 'verification_status');

    // Reload the document
    $document->refresh();

    // Assert that the main status has been updated
    expect($document->verification_status)->toBe('verified');

    // Check if the transition history was recorded
    $history = StateTransitionLog::where('model_id', $document->id)
                ->where('status_field', 'verification_status')
                ->first();

    expect($history)->not()->toBeNull();
    expect($history->from_state)->toBe('pending');
    expect($history->to_state)->toBe('submitted');
    expect($history->transition_name)->toBe('submit');
    expect($history->model_type)->toBe(Document::class);
});

it('can register multiple state transitions for a model', function () {
    // Sample transition data for the test.
    $transitions = [
        'approve' => [
            'from' => ['pending', 'submitted'],
            'to' => 'approved',
        ],
        'reject' => [
            'from' => ['pending', 'submitted'],
            'to' => 'rejected',
        ],
        'verify' => [
            'from' => ['pending', 'submitted'],
            'to' => 'verified',
        ],
    ];

    // empty the state_transitions table
    StateTransition::truncate();

    // Register transitions for the 'verification_status' field in Document.
    StateTransition::register(Document::class, $transitions, 'verification_status');

    // Assert that the correct number of transitions were registered.
    expect(StateTransition::count())->toBe(6);  // 3 transitions * 2 'from' states each

    // Assert that specific transitions are correctly registered in the database.
    foreach (['pending', 'submitted'] as $fromState) {
        // Approve transitions
        $this->assertDatabaseHas('state_transitions', [
            'model_type'      => Document::class,
            'transition_name' => 'approve',
            'from_state'      => $fromState,
            'to_state'        => 'approved',
            'status_field'    => 'verification_status',
        ]);

        // Reject transitions
        $this->assertDatabaseHas('state_transitions', [
            'model_type'      => Document::class,
            'transition_name' => 'reject',
            'from_state'      => $fromState,
            'to_state'        => 'rejected',
            'status_field'    => 'verification_status',
        ]);

        // Verify transitions
        $this->assertDatabaseHas('state_transitions', [
            'model_type'      => Document::class,
            'transition_name' => 'verify',
            'from_state'      => $fromState,
            'to_state'        => 'verified',
            'status_field'    => 'verification_status',
        ]);
    }
});

it('records and retrieves transition histories correctly', function () {
    // Create a new document
    $document = Document::create([
        'title' => 'Test Document',
        'verification_status' => 'pending',
        'status' => 'draft',
    ]);

    // Apply transitions
    $document->applyTransition('submit', 'verification_status');
    $document->applyTransition('verify', 'verification_status');
    $document->applyTransition('publish', 'status');

    // Retrieve transition histories
    $transitionLogs = $document->transitionLogs;

    dd($transitionLogs->toArray());

    // Assert that we have the correct number of transition histories
    expect($transitionLogs)->toHaveCount(3);

    // Assert that the transition histories are correct and in the right order
    expect($transitionLogs[0]->transition_name)->toBe('submit');
    expect($transitionLogs[0]->from_state)->toBe('pending');
    expect($transitionLogs[0]->to_state)->toBe('submitted');
    expect($transitionLogs[0]->status_field)->toBe('verification_status');

    expect($transitionLogs[1]->transition_name)->toBe('verify');
    expect($transitionLogs[1]->from_state)->toBe('submitted');
    expect($transitionLogs[1]->to_state)->toBe('verified');
    expect($transitionLogs[1]->status_field)->toBe('verification_status');

    expect($transitionLogs[2]->transition_name)->toBe('publish');
    expect($transitionLogs[2]->from_state)->toBe('draft');
    expect($transitionLogs[2]->to_state)->toBe('published');
    expect($transitionLogs[2]->status_field)->toBe('status');

    // Assert that all transition histories belong to the correct document
    $transitionLogs->each(function ($history) use ($document) {
        expect($history->model_id)->toBe($document->id);
        expect($history->model_type)->toBe(Document::class);
    });
});
