<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

/*
 * Bootstrap for the integration test suite.
 *
 * Boots the Shopware test kernel and installs + activates this plugin so the real
 * container (with the compiler pass and route decorators applied) is available to
 * integration tests. Requires a configured test database (DATABASE_URL).
 */
$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('SwagDisableStoreApiCache')
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Swag\\DisableStoreApiCache\\Tests\\', __DIR__);
