<?php

test('world map page renders svg layers without alpine svg templates', function () {
    $this->get(route('world-map'))
        ->assertOk()
        ->assertSee('World Map')
        ->assertSee('data-map-layer="edges"', false)
        ->assertSee('data-map-layer="nodes"', false)
        ->assertDontSee('template x-for="edge in edges"', false)
        ->assertDontSee('template x-for="node in nodes"', false);
});
