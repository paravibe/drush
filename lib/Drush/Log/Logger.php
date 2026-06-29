<?php

/**
 * @file
 * Contains \Drush\Log\Logger.
 *
 * This is the actual Logger for Drush that is responsible
 * for logging messages.
 *
 * This logger is designed such that it can be provided to
 * other libraries that log to a Psr\Log\LoggerInterface.
 * As such, it takes responsibility for passing log messages
 * to backend invoke, as necessary (c.f. drush_backend_packet()).
 *
 * Drush supports all of the required log levels from Psr\Log\LogLevel,
 * and also defines its own. See Drush\Log\LogLevel.
 *
 * Those who may wish to change the way logging works in Drush
 * should therefore NOT attempt to replace this logger with their
 * own LoggerInterface, as it will not work.  It would be okay
 * to extend Drush\Log\Logger, or perhaps we could provide a way
 * to set an output I/O object here, in case output redirection
 * was the only thing that needed to be swapped out.
 */

namespace Drush\Log;

use Drush\Log\Formatters\Formatter;
use Drush\Log\Formatters\Json;
use Drush\Log\Formatters\Text;
use Drush\Log\LogLevel;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

  private Formatter $formatter;

  public function __construct($format) {
    $formatter = new Text();

    if ($format === 'json') {
      $formatter = new Json();
    }

    $this->formatter = $formatter;
  }

  public function log($level, $message, array $context = []): void {
    // Convert to old $entry array for b/c calls
    $entry = $context;
    $entry['type'] = $level;
    $entry['message'] = $message;
    if (!isset($entry['memory'])) {
      $entry['memory'] = memory_get_usage();
    }
    if (!isset($entry['timestamp'])) {
      $entry['timestamp'] = microtime(TRUE);
    }

    // Drush\Log\Logger should take over all of the responsibilities
    // of drush_log, including caching the log messages and sending
    // log messages along to backend invoke.
    // TODO: move these implementations inside this class.
    $log =& drush_get_context('DRUSH_LOG', []);
    $log[] = $entry;
    if ($level != LogLevel::DEBUG_NOTIFY) {
      drush_backend_packet('log', $entry);
    }

    // When running in backend mode, log messages are not displayed, as they will
    // be returned in the JSON encoded associative array.
    if (drush_get_context('DRUSH_BACKEND')) {
      return;
    }

    $message = $this->formatter->format($level, $entry);

    if (!empty($message)) {
      drush_print($message, 0, STDERR);
    }
  }

}
