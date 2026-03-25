<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // Optional integrations
    ->ignoreErrorsOnPackage('terminal42/contao-mp_forms', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    // Old version of mp-forms
    ->ignoreUnknownClasses([\MPFormsSessionManager::class])
;
