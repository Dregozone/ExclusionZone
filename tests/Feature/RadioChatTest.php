<?php

use App\Events\RadioMessageSent;
use App\Livewire\RadioChat;
use App\Livewire\RadioHistory;
use App\Models\RadioMessage;
use App\Models\User;
use App\Models\UserMute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('the radio shows the 20 most recent transmissions with the newest last', function () {
    $user = User::factory()->create();
    $oldest = RadioMessage::factory()->create(['body' => 'First ever transmission']);
    $oldestShown = RadioMessage::factory()->create(['body' => 'Oldest transmission still on air']);
    RadioMessage::factory()->count(18)->create();
    $newest = RadioMessage::factory()->create(['body' => 'Latest transmission']);

    $this->actingAs($user);

    Livewire::test(RadioChat::class)
        ->assertDontSee($oldest->body)
        ->assertSee($newest->user->name)
        ->assertSeeInOrder([$oldestShown->body, $newest->body]);
});

test('players can transmit a message that is broadcast on the radio channel', function () {
    Event::fake([RadioMessageSent::class]);
    $user = User::factory()->create(['name' => 'Strelok']);

    $this->actingAs($user);

    Livewire::test(RadioChat::class)
        ->set('message', '  Anyone near the Duga?  ')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('message', '')
        ->assertSee('Anyone near the Duga?')
        ->assertSee('Strelok');

    $radioMessage = RadioMessage::query()->sole();

    expect($radioMessage->user_id)->toBe($user->id)
        ->and($radioMessage->body)->toBe('Anyone near the Duga?');

    Event::assertDispatched(RadioMessageSent::class, fn (RadioMessageSent $event): bool => $event->radioMessage->is($radioMessage)
        && $event->broadcastOn()[0]->name === 'private-radio'
        && $event->broadcastWith() === ['id' => $radioMessage->id]);
});

test('transmissions must be present and within the length limit', function (string $message, string $rule) {
    $this->actingAs(User::factory()->create());

    Livewire::test(RadioChat::class)
        ->set('message', $message)
        ->call('send')
        ->assertHasErrors(['message' => $rule]);

    expect(RadioMessage::query()->count())->toBe(0);
})->with([
    'blank' => ['', 'required'],
    'too long' => [str_repeat('a', RadioMessage::MAX_LENGTH + 1), 'max'],
]);

test('muted players cannot transmit until the mute expires', function () {
    Carbon::setTestNow('2026-10-21 12:00:00');
    $user = User::factory()->create();
    UserMute::query()->create([
        'moderator_user_id' => User::factory()->asRole('moderator')->create()->id,
        'target_user_id' => $user->id,
        'starts_at' => now(),
        'ends_at' => now()->addMinutes(30),
    ]);

    $this->actingAs($user);

    Livewire::test(RadioChat::class)
        ->set('message', 'Let me speak')
        ->call('send')
        ->assertHasErrors('message')
        ->assertSee('You are muted on the radio until 12:30.');

    expect(RadioMessage::query()->count())->toBe(0);

    Carbon::setTestNow(now()->addMinutes(31));

    Livewire::test(RadioChat::class)
        ->set('message', 'Let me speak')
        ->call('send')
        ->assertHasNoErrors();

    expect(RadioMessage::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('roles without radio access cannot read or transmit', function () {
    $radioMessage = RadioMessage::factory()->create();
    $this->actingAs(User::factory()->asRole('guest')->create());

    Livewire::test(RadioChat::class)
        ->assertSee('Your current role cannot access radio chat.')
        ->assertDontSee($radioMessage->body)
        ->set('message', 'Hello?')
        ->call('send')
        ->assertHasErrors('message');

    expect(RadioMessage::query()->count())->toBe(1);
});

test('the radio refreshes when another player transmits', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(RadioChat::class)
        ->assertSee('No transmissions detected.');

    $radioMessage = RadioMessage::factory()->create(['body' => 'Blowout incoming']);

    $component->dispatch('echo-private:radio,RadioMessageSent', ['id' => $radioMessage->id])
        ->assertSee('Blowout incoming');
});

test('the radio log lists every transmission from today only', function () {
    Carbon::setTestNow('2026-10-21 09:00:00');
    RadioMessage::factory()->create(['body' => 'Yesterday chatter', 'created_at' => now()->subDay()]);
    RadioMessage::factory()->count(25)->create();
    RadioMessage::factory()->create(['body' => 'Morning check-in']);

    $this->actingAs(User::factory()->create())
        ->get(route('radio.history'))
        ->assertOk()
        ->assertSee('26 transmissions')
        ->assertSee('Morning check-in')
        ->assertDontSee('Yesterday chatter');

    Carbon::setTestNow();
});

test('the radio log refreshes when another player transmits', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(RadioHistory::class)
        ->assertSee('No transmissions logged today.');

    RadioMessage::factory()->create(['body' => 'Blowout incoming']);

    $component->dispatch('echo-private:radio,RadioMessageSent')
        ->assertSee('Blowout incoming');
});

test('the radio log requires radio access', function () {
    $this->get(route('radio.history'))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->asRole('guest')->create())
        ->get(route('radio.history'))
        ->assertForbidden();
});

test('the dashboard pda hosts the radio and links to the log', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(RadioChat::class)
        ->assertSee(route('radio.history'));
});
