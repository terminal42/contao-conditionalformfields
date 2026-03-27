<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // Optional integrations
    ->ignoreErrorsOnPackage('terminal42/contao-mp_forms', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    // Only required for PHP < 8.4
    ->ignoreErrorsOnPackage('symfony/polyfill-php84', [ErrorType::UNUSED_DEPENDENCY])

    // class in DCA file
    ->ignoreUnknownClasses([\tl_form_field::class])
;
