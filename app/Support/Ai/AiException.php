<?php

namespace App\Support\Ai;

use RuntimeException;

/**
 * Raised when an AI generation request cannot be completed. The message is
 * safe to surface to end users; technical detail is logged separately.
 */
class AiException extends RuntimeException
{
}
