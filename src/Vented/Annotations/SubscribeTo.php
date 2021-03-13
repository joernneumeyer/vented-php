<?php


  namespace Neu\Vented\Annotations;

  use Attribute;

  /**
   * An attribute to mark a function or method as a subscriber of an event.
   *
   * Any given subscriber may listen to multiple events.
   * @package Neu\Vented\Annotations
   */
  #[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
  class SubscribeTo {
    public function __construct(public string $event, public ?string $onChannel = null) {
    }
  }
