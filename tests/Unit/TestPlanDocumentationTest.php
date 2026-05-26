<?php

test('test plan documents all required roles and tracking sections', function () {
    $contents = file_get_contents(dirname(__DIR__, 2).'/docs/testPlan.md');

    expect($contents)->not->toBeFalse()
        ->and($contents)->toContain('## 2. Live Progress (Passing vs Total)')
        ->and($contents)->toContain('## 3. Role and User-Story Coverage Matrix')
        ->and($contents)->toContain('## 4. Requirement Traceability Matrix');

    foreach (['guest', 'user', 'premium', 'moderator', 'admin'] as $role) {
        expect($contents)->toContain("| {$role} |");
    }
});
