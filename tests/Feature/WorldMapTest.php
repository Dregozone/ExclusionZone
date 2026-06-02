<?php

test('world map page renders geo map svg layers', function () {
    $this->get(route('world-map'))
        ->assertOk()
        ->assertSee('World Map')
        ->assertSee('x-ref="edgesLayer"', false)
        ->assertSee('x-ref="nodesLayer"', false)
        ->assertSee('x-ref="continentsLayer"', false)
        ->assertDontSee('template x-for="edge in edges"', false)
        ->assertDontSee('template x-for="node in nodes"', false);
});
