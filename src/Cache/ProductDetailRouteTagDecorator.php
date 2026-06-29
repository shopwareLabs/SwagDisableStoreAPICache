<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Cache;

use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Re-emits the legacy `product-detail-route-{parentId}` cache tag that the removed
 * {@see CachedProductDetailRoute} attached to HTTP cache entries.
 *
 * The real {@see \Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute}
 * only dispatches the `product-{id}` entity tag, but the legacy
 * {@see \Shopware\Core\Framework\Adapter\Cache\CacheInvalidationSubscriber} invalidates
 * HTTP entries by the decorator route name. This decorator bridges that gap so
 * invalidation keeps working without the object cache.
 */
class ProductDetailRouteTagDecorator extends AbstractProductDetailRoute
{
    public function __construct(
        private readonly AbstractProductDetailRoute $decorated,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function getDecorated(): AbstractProductDetailRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductDetailRouteResponse
    {
        $response = $this->decorated->load($productId, $request, $context, $criteria);

        $parentId = $response->getProduct()->getParentId() ?? $response->getProduct()->getId();

        $this->dispatcher->dispatch(new AddCacheTagEvent(CachedProductDetailRoute::buildName($parentId)));

        return $response;
    }
}
