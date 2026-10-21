# Changelog

## Unreleased

### Changed

- Breaking, child themes: the constructor of `FlexyBundle\Components\Organisms\Delivery\Base`
  takes a new last argument, `FlexyBundle\Service\DeliveryAddressCopy $deliveryAddressCopy`.
  A theme component that extends it and overrides `__construct()` must declare the argument
  and pass it on:

  ```php
  public function __construct(/* …existing arguments… */, DeliveryAddressCopy $deliveryAddressCopy)
  {
      parent::__construct(/* …existing arguments… */, $deliveryAddressCopy);
  }
  ```

  A child that does not override the constructor needs nothing: the service is autowired.
- Breaking, child themes: the constructor of `FlexyBundle\Components\Organisms\Invoice\Base`
  takes two new last arguments, `FlexyBundle\Service\DeliveryAddressCopy $deliveryAddressCopy`
  and `Symfony\Component\Routing\Generator\UrlGeneratorInterface $urls`. Same migration:
  declare both in the overriding constructor and pass them to `parent::__construct()`, after
  `CartFacade $cartFacade` and `GuestCheckoutGate $guestCheckoutGate`.
- In both components, `CheckoutEvents::ADD_NEW_DELIVERY_ADDRESS` is now handled by the new
  public method `addressSaved()`, which still resets the address form. A child that
  overrides `resetAddressForm()` to hook that event must override `addressSaved()` as well.
  An edited delivery address now reaches the cart; a changed place sends the buyer back to
  the delivery step.

### Added

- `Organisms\Delivery\Base::getPostalCodeWithoutCarrier()`: the postal code of the delivery
  address when no carrier serves it, so that the step can say so.
