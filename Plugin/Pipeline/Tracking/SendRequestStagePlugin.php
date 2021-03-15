<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Pipeline\Tracking;

use DeutschePost\Internetmarke\Model\Tracking\TrackingConfiguration;
use Dhl\UnifiedTracking\Model\Pipeline\Stage\SendRequestStage;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;

class SendRequestStagePlugin
{
    /**
     * @param SendRequestStage $subject
     * @param TrackRequestInterface[] $requests
     *
     * @return null
     */
    public function beforeExecute(SendRequestStage $subject, array $requests)
    {
        foreach ($requests as $request) {
            $trackExtensionAttributes = $request->getSalesTrack()->getExtensionAttributes();
            if ($trackExtensionAttributes->getDpdhlOrderId()) {
                $request->getSalesTrack()->setCarrierCode(TrackingConfiguration::CARRIER_CODE);
            }
        }

        return null;
    }
}
