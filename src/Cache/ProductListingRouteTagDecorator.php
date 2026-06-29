<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Cache;

use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Re-emits the legacy `product-listing-route-{categoryId}` cache tag that the removed
 * {@see CachedProductListingRoute} attached to HTTP cache entries.
 */
class ProductListingRouteTagDecorator extends AbstractProductListingRoute
{
    public function __construct(
        private readonly AbstractProductListingRoute $decorated,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function getDecorated(): AbstractProductListingRoute
    {
        return $this->decorated;
    }

    public function load(string $categoryId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductListingRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(CachedProductListingRoute::buildName($categoryId)));

        return $this->decorated->load($categoryId, $request, $context, $criteria);
    }
}
