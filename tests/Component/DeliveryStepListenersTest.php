<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FlexyBundle\Tests\Component;

use FlexyBundle\Components\Organisms\Delivery\Base as DeliveryStep;
use FlexyBundle\Event\CheckoutEvents;
use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Clicking a carrier card emits an event the delivery step listens to: that listener is
 * what writes the carrier on the cart, and nothing else would.
 */
final class DeliveryStepListenersTest extends TestCase
{
    public function testTheStepWritesTheCarrierTheBuyerClicks(): void
    {
        self::assertContains(
            ['action' => 'selectDeliveryModuleOption', 'event' => CheckoutEvents::SET_DELIVERY_MODULE_OPTION],
            AsLiveComponent::liveListeners(DeliveryStep::class),
        );
    }
}
