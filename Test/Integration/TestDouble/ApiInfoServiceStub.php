<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ApiInfoInterface;
use DeutschePost\Sdk\Internetmarke\Model\ApiInfo;

class ApiInfoServiceStub implements ApiInfoServiceInterface
{
    private ApiInfoInterface $info;

    public function __construct(?ApiInfoInterface $info = null)
    {
        $this->info = $info ?? new ApiInfo();
    }

    public function getInfo(): ApiInfoInterface
    {
        return $this->info;
    }

    public function isHealthy(): bool
    {
        return true;
    }
}
