<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Api\Data;

interface TrackAdditionalInterface
{
    public const TRACK_ID = 'track_id';
    public const LABEL_API = 'label_api';

    public function getId(): int;
    public function getLabelApi(): string;
}
