<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache;

use Shopware\Core\Framework\Plugin;
use Swag\DisableStoreApiCache\DependencyInjection\DisableStoreApiCacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagDisableStoreApiCache extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DisableStoreApiCacheCompilerPass());
    }
}
