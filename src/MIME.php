<?php

/*
 * This file is part of the Fetch package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\radu_imap;

/**
 * This library is a wrapper around the Imap library functions included in php.
 *
 * @package Drupal\radu_imap
 * @author  Robert Hafner <tedivm@tedivm.com>
 * @author  Sergey Linnik <linniksa@gmail.com>
 */
final class MIME {
  /**
   * @param string $text
   * @param string $targetCharset
   *
   * @return string
   */
  public static function decode($text, $targetCharset = 'utf-8') {
    if (NULL === $text) {
      return NULL;
    }

    $result = '';

    foreach (imap_mime_header_decode($text) as $word) {
      $ch = 'default' === $word->charset ? 'ascii' : $word->charset;

      $result .= iconv($ch, $targetCharset, $word->text);
    }

    return $result;
  }
}
