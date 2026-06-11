<?php

declare(strict_types=1);

it('exposes the framework health check', function () {
    $this->get('/up')->assertOk();
});
