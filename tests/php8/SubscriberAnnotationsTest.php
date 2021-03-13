<?php

  use Neu\Vented\Annotations\SubscribeTo;
  use Neu\Vented\Sandbox\ExampleEvent;
  use Neu\Vented\Vent;

  it('passes', function () {
    #[SubscribeTo(event: ExampleEvent::class)]
    function someAnnotatedSubscriber($emitter, ExampleEvent $event) {
      expect($event->payload())->toEqual('Hello, World!');
    }
    $v = Vent::fromAnnotatedFunctions();
    $v->emit(null, new ExampleEvent('Hello, World!'));
  });

  it('should invoke the annotated method of the object', function() {
    class SomeOtherEvent548 {
      public function __construct(public string $payload) {
      }
    }
    class SomeTestClassFoo443 {
      public int $callCount = 0;
      #[SubscribeTo(event: ExampleEvent::class)]
      #[SubscribeTo(event: SomeOtherEvent548::class)]
      function foobar($emitter, $e) {
        ++$this->callCount;
        expect($emitter)->toBeNull();
        switch (get_class($e)) {
          case ExampleEvent::class:
            expect($e->payload())->toEqual('foobar');
            break;
          case SomeOtherEvent548::class:
            expect($e->payload)->toEqual('some_baz');
            break;
        }
      }
    }
    $v = new Vent();
    $e = new SomeTestClassFoo443();
    $v->subscribeAnnotatedObjectMethods($e);
    $v->emit(null, new ExampleEvent('foobar'));
    $v->emit(null, new SomeOtherEvent548('some_baz'));
    expect($e->callCount)->toEqual(2);
  });
