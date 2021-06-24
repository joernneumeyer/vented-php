<?php

  use Neu\Vented\Events\Data;
  use Neu\Vented\Sandbox\ExampleEvent;
  use Neu\Vented\Vent;

  it('should trigger run all subscribers, if the suiting event is emitted', function() {
    $v = new Vent();
    $buffer = null;
    $v->subscribe(ExampleEvent::class, function($emitter, $event) use (&$buffer) {
      $buffer = [$emitter, $event];
    });
    $v->subscribe(ExampleEvent::class, function($emitter, $event) use (&$buffer) {
      expect($buffer)->toMatchArray([$emitter, $event]);
    });
    $v->emit(null, new ExampleEvent('Hello, World!'));
  });

  it('should register a named channel', function() {
    $v = Vent::instance()->channel('foobar');
    expect($v)->not->toBeNull();
    expect($v)->toEqual(Vent::instance()->channel('foobar'));
  });

  it('should properly process nested events', function() {
    class SomeBaseEvent485 extends Data { }
    class SomeSubEvent537 extends SomeBaseEvent485 { }

    $base = new Vent();
    $sub = new Vent();
    $base->subscribe(SomeBaseEvent485::class, $sub);
    $called = false;
    $sub->subscribe(SomeSubEvent537::class, function($emitter, SomeSubEvent537 $e) use (&$called) {
      $called = true;
      expect($emitter)->toBeNull();
      expect($e->payload())->toEqual('sub sub');
    });
    $base->emit(null, new SomeSubEvent537('sub sub'), SomeBaseEvent485::class);
    expect($called)->toBeTrue();
  });
