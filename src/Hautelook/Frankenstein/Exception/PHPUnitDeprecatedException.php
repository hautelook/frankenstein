<?php

namespace Hautelook\Frankenstein\Exception;

use Exception;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class PHPUnitDeprecatedException extends \Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $message = 'Please use prophecy or atoum asserters instead.';

        parent::__construct($message, $code, $previous);
    }
}
