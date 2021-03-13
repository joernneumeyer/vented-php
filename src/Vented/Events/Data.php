<?php

  namespace Neu\Vented\Events;

  abstract class Data {
    private $_payload;

    public function __construct($payload) {
      $this->_payload = $payload;
    }

    public function payload() {
      return $this->_payload;
    }
  }
