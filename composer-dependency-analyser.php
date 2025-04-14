<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('psr/http-client-implementation', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('psr/http-factory-implementation', [ErrorType::UNUSED_DEPENDENCY])
;
