<?php

/**
 * The part of WordPress's WP_Error the plugin reads. Global, as in WordPress.
 */
class WP_Error
{
  public function __construct(private string $code = '', private string $message = '') {}

  public function get_error_message(): string
  {
    return $this->message;
  }
}
