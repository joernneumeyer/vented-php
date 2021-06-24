<?php

  namespace Neu\Vented;

  use Closure;
  use Neu\Vented\Annotations\SubscribeTo;
  use Neu\Vented\Events\VentDestruction;
  use Neu\Vented\Signals\CancelOperation;
  use ReflectionException;
  use ReflectionFunction;
  use ReflectionObject;
  use TypeError;

  /**
   * An event bus.
   * @package Neu\Vented
   */
  class Vent {
    /** @var callable[] */
    private $subscribers = [];
    /** @var Vent[] */
    private $_channels = [];
    /** @var null|Vent  */
    private static $_instance = null;
    public const GLOBAL_CHANNEL_NAME = '__NEU_VENT_GLOBAL';

    public function __destruct() {
      $this->close();
    }

    public function __invoke(?object $fromSource, $event) {
      return $this->emit($fromSource, $event);
    }

    /**
     * Emit an event to all subscribers which are subscribed to the current Vent.
     * @param object|null $fromSource The object emitting the event.
     * @param object $event The event to be emitted.
     * @param string|null $asPolymorphicEvent A parent type of the event as which it should be emitted instead.
     */
    public function emit(?object $fromSource, $event, ?string $asPolymorphicEvent = null) {
      if ($asPolymorphicEvent && !is_a($event, $asPolymorphicEvent)) {
        $eventType = get_class($event);
        throw new TypeError("Tried to emit polymorphic event, but {$eventType} is not an instance of {$asPolymorphicEvent}!");
      }
      $subs = $this->subscribers[$asPolymorphicEvent ?? get_class($event)] ?? [];
      foreach ($subs as $sub) {
        try {
          $sub($fromSource, $event);
        } catch (CancelOperation $e) {
          return null;
        }
      }
    }

    /**
     * Subscribes the given handler to incoming events of the specified event type.
     * The event must be the FQN of a class.
     * @param string $toEvent The event to subscribe to.
     * @param callable $withHandler A handler to process the event, if it occurs.
     */
    public function subscribe(string $toEvent, callable $withHandler) {
      if (!isset($this->subscribers[$toEvent])) {
        $this->subscribers[$toEvent] = [];
      }
      $this->subscribers[$toEvent][] = $withHandler;
    }

    /**
     * Retrieves (or creates) the Vent which is associated with the given channel name.
     * @param string $name The name of the channel.
     * @return mixed|Vent
     */
    public function channel(string $name) {
      if (!isset($this->_channels[$name])) {
        $this->_channels[$name] = new Vent();
      }
      return $this->_channels[$name];
    }

    /**
     * Scans an object for all methods, which have the {@see SubscribeTo} attribute on them.
     * These methods will then be subscribed to their appropriate Vent.
     * @param object $obj The object to scan.
     */
    public function subscribeAnnotatedObjectMethods(object $obj) {
      $ref = new ReflectionObject($obj);
      $methodRefs = $ref->getMethods();
      foreach ($methodRefs as $methodRef) {
        $attr = $methodRef->getAttributes(SubscribeTo::class);
        foreach ($attr as $subRef) {
          /** @var SubscribeTo $sub */
          $sub = $subRef->newInstance();
          $this->subscribe($sub->event, $methodRef->getClosure($obj));
        }
      }
    }

    /**
     * Subscribes a function to events, based on its {@see SubscribeTo} attributes.
     * @param callable $func The function to subscribe.
     * @throws ReflectionException
     */
    public function subscribeAnnotatedFunction(callable $func) {
      $ref = new ReflectionFunction($func);
      $attr = $ref->getAttributes(SubscribeTo::class);
      foreach ($attr as $subRef) {
        /** @var SubscribeTo $sub */
        $sub = $subRef->newInstance();
        $this->subscribe($sub->event, $func);
      }
    }

    /**
     * Cancel all subscriptions to the current Vent and all its sub-Vents.
     */
    public function close(): void {
      $this->emit($this, new VentDestruction());
      foreach ($this->_channels as $channel) {
        $channel->close();
      }
      $this->_channels = [];
      $this->subscribers = [];
    }

    /**
     * @return Vent
     */
    public static function instance(): Vent {
      if (self::$_instance === null) {
        self::$_instance = new Vent();
      }
      return self::$_instance;
    }

    /**
     * Scans all declared function for {@see SubscribeTo} attributes.
     * All function which have this attribute will be subscribed to a new Vent.
     * @return Vent
     * @throws ReflectionException
     */
    public static function fromAnnotatedFunctions(): Vent {
      $funcs = get_defined_functions()['user'];
      $v = new Vent();
      foreach ($funcs as $func) {
        $v->subscribeAnnotatedFunction($func);
      }
      return $v;
    }
  }
