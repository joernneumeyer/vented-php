<?php

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
