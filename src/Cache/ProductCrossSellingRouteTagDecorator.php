<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Cache;

use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\ProductCrossSellingRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Re-emits the legacy `cross-selling-route-{productId}` cache tag that the removed
 * {@see CachedProductCrossSellingRoute} attached to HTTP cache entries.
 */
class ProductCrossSellingRouteTagDecorator extends AbstractProductCrossSellingRoute
{
    public function __construct(
        private readonly AbstractProductCrossSellingRoute $decorated,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function getDecorated(): AbstractProductCrossSellingRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductCrossSellingRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(CachedProductCrossSellingRoute::buildName($productId)));

        return $this->decorated->load($productId, $request, $context, $criteria);
    }
}
