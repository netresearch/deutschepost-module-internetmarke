<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

use DeutschePost\Sdk\OneClickForApp\Api\Data\OrderInterface;
use DeutschePost\Sdk\OneClickForApp\Service\OrderService\Order;
use DeutschePost\Sdk\OneClickForApp\Service\OrderService\Voucher;

class OrderProvider
{
    /**
     * @return string
     */
    private static function getLabelPdf(): string
    {
        return <<<'B64'
JVBERi0xLjUKJbXtrvsKMyAwIG9iago8PCAvTGVuZ3RoIDQgMCBSCiAgIC9GaWx0ZXIgL0ZsYXRl
RGVjb2RlCj4+CnN0cmVhbQp4nCvkCuQCAAKSANcKZW5kc3RyZWFtCmVuZG9iago0IDAgb2JqCiAg
IDEyCmVuZG9iagoyIDAgb2JqCjw8Cj4+CmVuZG9iago1IDAgb2JqCjw8IC9UeXBlIC9QYWdlCiAg
IC9QYXJlbnQgMSAwIFIKICAgL01lZGlhQm94IFsgMCAwIDEwNCAxNDcgXQogICAvQ29udGVudHMg
MyAwIFIKICAgL0dyb3VwIDw8CiAgICAgIC9UeXBlIC9Hcm91cAogICAgICAvUyAvVHJhbnNwYXJl
bmN5CiAgICAgIC9DUyAvRGV2aWNlUkdCCiAgID4+CiAgIC9SZXNvdXJjZXMgMiAwIFIKPj4KZW5k
b2JqCjEgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzCiAgIC9LaWRzIFsgNSAwIFIgXQogICAvQ291bnQg
MQo+PgplbmRvYmoKNiAwIG9iago8PCAvQ3JlYXRvciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWly
b2dyYXBoaWNzLm9yZykpCiAgIC9Qcm9kdWNlciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWlyb2dy
YXBoaWNzLm9yZykpCj4+CmVuZG9iago3IDAgb2JqCjw8IC9UeXBlIC9DYXRhbG9nCiAgIC9QYWdl
cyAxIDAgUgo+PgplbmRvYmoKeHJlZgowIDgKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMzQ2
IDAwMDAwIG4gCjAwMDAwMDAxMjUgMDAwMDAgbiAKMDAwMDAwMDAxNSAwMDAwMCBuIAowMDAwMDAw
MTA0IDAwMDAwIG4gCjAwMDAwMDAxNDYgMDAwMDAgbiAKMDAwMDAwMDQxMSAwMDAwMCBuIAowMDAw
MDAwNTM2IDAwMDAwIG4gCnRyYWlsZXIKPDwgL1NpemUgOAogICAvUm9vdCA3IDAgUgogICAvSW5m
byA2IDAgUgo+PgpzdGFydHhyZWYKNTg4CiUlRU9GCg==
B64;
    }

    /**
     * Obtain SDK response object.
     *
     * @param string $voucherId
     * @return OrderInterface
     */
    public static function getSingleVoucherOrder(string $voucherId): OrderInterface
    {
        $pdf = self::getLabelPdf();

        return new Order(
            '1234',
            1234,
            $pdf,
            [
                new Voucher($voucherId, $voucherId, $pdf),
            ],
            null
        );
    }

    /**
     * Obtain SDK response object.
     *
     * @param string[] $voucherIds
     * @return OrderInterface
     */
    public static function getMultiVoucherOrder(array $voucherIds): OrderInterface
    {
        $pdf = self::getLabelPdf();

        $vouchers = array_map(
            static function (string $voucherId) use ($pdf) {
                return new Voucher($voucherId, $voucherId, $pdf);
            },
            $voucherIds
        );

        return new Order('1234', 1234, $pdf, $vouchers, null);
    }
}
