<?php

use App\FeedbackType;
use App\Livewire\Admin\ManageFeedback;
use App\Livewire\Feedback;
use App\Models\FeedbackReport;
use App\Models\Idea;
use App\Models\User;
use Livewire\Livewire;

test('guests are sent to log in from the feedback page', function () {
    $this->get(route('feedback'))->assertRedirect(route('login'));
});

test('posting an idea counts the author as its first vote', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Feedback::class)
        ->set('ideaTitle', 'Let survivors build a camp')
        ->set('ideaDescription', 'Somewhere to store loot.')
        ->call('submitIdea')
        ->assertHasNoErrors()
        ->assertSet('ideaTitle', '');

    $idea = Idea::query()->sole();

    expect($idea)
        ->user_id->toBe($user->id)
        ->title->toBe('Let survivors build a camp')
        ->and($idea->voters()->pluck('users.id')->all())->toBe([$user->id]);
});

test('an idea needs a title within the length limit', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(Feedback::class)
        ->call('submitIdea')
        ->assertHasErrors(['ideaTitle' => 'required'])
        ->set('ideaTitle', str_repeat('a', Idea::TITLE_MAX_LENGTH + 1))
        ->call('submitIdea')
        ->assertHasErrors(['ideaTitle' => 'max']);

    expect(Idea::query()->count())->toBe(0);
});

test('voting on an idea adds one vote per survivor and voting again takes it back', function () {
    $idea = Idea::factory()->create();
    $voter = User::factory()->create();

    $component = Livewire::actingAs($voter)->test(Feedback::class);

    $component->call('toggleVote', $idea->id);
    expect($idea->voters()->count())->toBe(1);

    $component->call('toggleVote', $idea->id);
    expect($idea->voters()->count())->toBe(0);
});

test('the ideas board lists the most voted ideas first', function () {
    $quiet = Idea::factory()->create(['title' => 'Quiet idea']);
    $popular = Idea::factory()->create(['title' => 'Popular idea']);
    $popular->voters()->attach(User::factory()->count(2)->create());
    $quiet->voters()->attach(User::factory()->create());

    Livewire::actingAs(User::factory()->create())
        ->test(Feedback::class)
        ->assertSeeInOrder(['Popular idea', 'Quiet idea']);
});

test('only admins can remove an idea', function () {
    $idea = Idea::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(Feedback::class)
        ->call('deleteIdea', $idea->id)
        ->assertForbidden();

    expect($idea->fresh())->not->toBeNull();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Feedback::class)
        ->call('deleteIdea', $idea->id);

    expect($idea->fresh())->toBeNull();
});

test('a survivor can privately report a bug with steps to reproduce', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Feedback::class, ['tab' => 'report'])
        ->set('reportType', 'bug')
        ->set('reportTitle', 'Travel timer stuck')
        ->set('reportDetails', "1. Travel to Kyiv\n2. Timer stays at 0:00")
        ->call('submitReport')
        ->assertHasNoErrors()
        ->assertSee('Travel timer stuck');

    expect(FeedbackReport::query()->sole())
        ->user_id->toBe($user->id)
        ->type->toBe(FeedbackType::Bug)
        ->details->toBe("1. Travel to Kyiv\n2. Timer stays at 0:00")
        ->resolved_at->toBeNull();
});

test('a report needs a known type, a title and details', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(Feedback::class)
        ->set('reportType', 'complaint')
        ->call('submitReport')
        ->assertHasErrors(['reportType', 'reportTitle' => 'required', 'reportDetails' => 'required']);

    expect(FeedbackReport::query()->count())->toBe(0);
});

test('survivors only see their own reports', function () {
    $user = User::factory()->create();
    FeedbackReport::factory()->for($user)->create(['title' => 'My own report']);
    FeedbackReport::factory()->create(['title' => 'Someone else report']);

    Livewire::actingAs($user)
        ->test(Feedback::class, ['tab' => 'report'])
        ->assertSee('My own report')
        ->assertDontSee('Someone else report')
        ->assertDontSeeHtml('<flux:');
});

test('survivors cannot flood ideas and reports', function () {
    $component = Livewire::actingAs(User::factory()->create())->test(Feedback::class);

    foreach (range(1, Feedback::SUBMISSIONS_PER_HOUR) as $attempt) {
        $component->set('ideaTitle', "Idea {$attempt}")->call('submitIdea')->assertHasNoErrors();
    }

    $component->set('reportTitle', 'One too many')
        ->set('reportDetails', 'Details')
        ->call('submitReport')
        ->assertHasErrors('reportTitle');

    expect(FeedbackReport::query()->count())->toBe(0);
});

test('only admins can open the feedback reports screen', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.feedback'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.feedback'))->assertOk();
});

test('admins see who sent each open report and its steps to reproduce', function () {
    $reporter = User::factory()->create(['name' => 'Stalker Ivan']);
    $report = FeedbackReport::factory()->for($reporter)->create([
        'title' => 'Inventory vanished',
        'details' => 'Died in Pripyat and lost everything',
    ]);
    FeedbackReport::factory()->resolved()->create(['title' => 'Old fixed bug']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageFeedback::class)
        ->assertSee(['Inventory vanished', 'Stalker Ivan'])
        ->assertDontSee('Old fixed bug')
        ->call('toggleExpanded', $report->id)
        ->assertSee(['Died in Pripyat and lost everything', $reporter->email])
        ->set('showResolved', true)
        ->assertSee('Old fixed bug');
});

test('admins can filter reports by type', function () {
    FeedbackReport::factory()->create(['title' => 'A broken thing']);
    FeedbackReport::factory()->feedback()->create(['title' => 'A nice thought']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageFeedback::class)
        ->set('type', FeedbackType::Feedback->value)
        ->assertSee('A nice thought')
        ->assertDontSee('A broken thing');
});

test('admins can resolve and reopen a report', function () {
    $report = FeedbackReport::factory()->create();

    $component = Livewire::actingAs(User::factory()->admin()->create())->test(ManageFeedback::class);

    $component->call('resolve', $report->id);
    expect($report->fresh()->isResolved())->toBeTrue();

    $component->call('reopen', $report->id);
    expect($report->fresh()->isResolved())->toBeFalse();
});

test('report and idea text is escaped when shown', function () {
    $user = User::factory()->create();
    Idea::factory()->for($user)->create(['title' => '<script>alert("idea")</script>']);
    FeedbackReport::factory()->for($user)->create(['title' => '<script>alert("report")</script>']);

    Livewire::actingAs($user)
        ->test(Feedback::class)
        ->assertDontSeeHtml('<script>alert("idea")</script>')
        ->assertSeeHtml('&lt;script&gt;');

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageFeedback::class)
        ->assertDontSeeHtml('<script>alert("report")</script>')
        ->assertSeeHtml('&lt;script&gt;');
});
