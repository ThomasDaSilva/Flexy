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

namespace FlexyBundle\Tests\Integration;

use FlexyBundle\Service\OrderReturnRequestService;
use Thelia\Api\Resource\OrderReturn as OrderReturnResource;
use Thelia\Domain\OrderReturn\Exception\ReturnNotAllowedException;
use Thelia\Domain\OrderReturn\Service\ReturnEligibilityChecker;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;
use Thelia\Model\Order;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderReturn;
use Thelia\Model\OrderStatus;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

/**
 * What the customer wishes to receive in exchange is kept apart from the comment that explains the return, in the
 * same write as the return itself: a listener of the creation reads it already. Runs on an installed shop
 * (bin/test-prepare).
 */
final class OrderReturnExpectedExchangeTest extends IntegrationTestCase
{
    private FixtureFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->createFixtureFactory();
        ConfigQuery::write(ReturnEligibilityChecker::ENABLED_CONFIG_KEY, '1');
    }

    protected function tearDown(): void
    {
        ConfigQuery::resetCache();

        parent::tearDown();
    }

    public function testTheWishIsKeptApartFromTheComment(): void
    {
        [$customer, $order, $orderProduct] = $this->paidOrder();

        $return = $this->service()->open($order, $customer, [(int) $orderProduct->getId() => 1.0], null, OrderReturn::RESOLUTION_EXCHANGE, 'Too small.', '  The same one in size L.  ');

        self::assertSame('Too small.', $return->getCustomerComment());
        self::assertSame('The same one in size L.', $return->getExpectedExchange(), 'Trimmed, as the comment is.');
    }

    public function testABlankWishIsNoWish(): void
    {
        [$customer, $order, $orderProduct] = $this->paidOrder();

        $return = $this->service()->open($order, $customer, [(int) $orderProduct->getId() => 1.0], null, OrderReturn::RESOLUTION_REFUND, 'Too small.', '   ');

        self::assertNull($return->getExpectedExchange());
    }

    public function testAWishLongerThanAReturnKeeps(): void
    {
        [$customer, $order, $orderProduct] = $this->paidOrder();

        $this->expectException(ReturnNotAllowedException::class);
        $this->expectExceptionMessage('The wished exchange is too long.');

        $this->service()->open($order, $customer, [(int) $orderProduct->getId() => 1.0], null, OrderReturn::RESOLUTION_EXCHANGE, null, str_repeat('L', OrderReturnResource::MAX_EXPECTED_EXCHANGE_LENGTH + 1));
    }

    public function testAWishIsKeptOnAnExchangeOnly(): void
    {
        [$customer, $order, $orderProduct] = $this->paidOrder();

        $return = $this->service()->open($order, $customer, [(int) $orderProduct->getId() => 1.0], null, OrderReturn::RESOLUTION_REFUND, 'Too small.', str_repeat('L', OrderReturnResource::MAX_EXPECTED_EXCHANGE_LENGTH + 1));

        self::assertNull($return->getExpectedExchange(), 'A refund is not something sent back in exchange, however long the wish left in the form.');
    }

    private function service(): OrderReturnRequestService
    {
        $service = $this->getService(OrderReturnRequestService::class);
        self::assertInstanceOf(OrderReturnRequestService::class, $service);

        return $service;
    }

    /**
     * @return array{Customer, Order, OrderProduct}
     */
    private function paidOrder(): array
    {
        $customer = $this->factory->customer($this->factory->customerTitle());
        $product = $this->factory->product($this->factory->category(), $this->factory->taxRule(), $this->factory->currency());
        $pse = $this->factory->productSaleElement($product, ['quantity' => 10]);
        $order = $this->factory->order($customer, ['statusCode' => OrderStatus::CODE_PAID]);

        $orderProduct = (new OrderProduct())
            ->setOrderId((int) $order->getId())
            ->setProductRef((string) $product->getRef())
            ->setProductSaleElementsRef((string) $pse->getRef())
            ->setProductSaleElementsId((int) $pse->getId())
            ->setTitle('A returnable product')
            ->setQuantity(2.0)
            ->setPrice('10.000000')
            ->setPromoPrice('10.000000')
            ->setWasNew(1)
            ->setWasInPromo(0)
            ->setVirtual(0);
        $orderProduct->save($this->getPropelConnection());

        return [$customer, $order, $orderProduct];
    }
}
