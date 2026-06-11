<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full framework via Tests\TestCase. RefreshDatabase
| is applied per suite where database state matters (see the auth/resource
| feature tests) so pure HTTP checks stay fast.
|
*/

pest()->extend(TestCase::class)->in('Feature');
