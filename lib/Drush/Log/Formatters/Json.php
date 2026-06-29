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

namespace Drush\Log\Formatters;

use Drush\Log\LogLevel;

class Json implements Formatter {

  public function format($level, $entry) {
    $verbose = drush_get_context('DRUSH_VERBOSE');
    $debug = drush_get_context('DRUSH_DEBUG');
    $debugnotify = drush_get_context('DRUSH_DEBUG_NOTIFY');

    switch ($level) {
      case LogLevel::WARNING:
      case LogLevel::CANCEL:
        break;

      case 'failed': // Obsolete; only here in case contrib is using it.
      case LogLevel::EMERGENCY: // Not used by Drush
      case LogLevel::ALERT: // Not used by Drush
      case LogLevel::ERROR:
        break;

      case LogLevel::OK:
      case 'completed': // Obsolete; only here in case contrib is using it.
      case LogLevel::SUCCESS:
      case 'status': // Obsolete; only here in case contrib is using it.
        // In quiet mode, suppress progress messages
        if (drush_get_context('DRUSH_QUIET')) {
          return '';
        }
        break;

      case LogLevel::NOTICE:
      case 'message': // Obsolete; only here in case contrib is using it.
      case LogLevel::INFO:
        if (!$verbose) {
          // print nothing. exit cleanly.
          return '';
        }
        break;
      case LogLevel::DEBUG_NOTIFY:
        $level = LogLevel::DEBUG; // Report 'debug', handle like 'preflight'
      case LogLevel::PREFLIGHT:
        if (!$debugnotify) {
          // print nothing unless --debug AND --verbose. exit cleanly.
          return '';
        }
        break;

      case LogLevel::BOOTSTRAP:
      case LogLevel::DEBUG:
      default:
        if (!$debug) {
          // print nothing. exit cleanly.
          return '';
        }
        break;
    }

    $log_payload = [
      'timestamp' => date('c', (int) $entry['timestamp']),
      'level'     => $level,
      'channel'   => 'drush',
      'message'   => $entry['message'],
      'memory_mb' => round($entry['memory'] / 1024 / 1024, 2),
      'execution_time_sec' => round($entry['timestamp'] - DRUSH_REQUEST_TIME, 4),
    ];

    return json_encode($log_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

}
