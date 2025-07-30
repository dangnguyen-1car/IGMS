<?php

namespace App\Plugin\GarageManagementBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GarageManagementBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
