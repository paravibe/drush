<?php

namespace Drush\Log\Formatters;

interface Formatter {

  public function format($level, $entry);

}
