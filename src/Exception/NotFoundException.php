<?php

namespace Bitty\Container\Exception;

use Bitty\Container\Exception\ContainerException;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
