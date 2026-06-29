<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Cache;

use Shopware\Core\Content\Product\SalesChannel\Review\AbstractProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Re-emits the legacy `product-review-route-{productId}` cache tag that the removed
 * {@see CachedProductReviewRoute} attached to HTTP cache entries.
 */
class ProductReviewRouteTagDecorator extends AbstractProductReviewRoute
{
    public function __construct(
        private readonly AbstractProductReviewRoute $decorated,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function getDecorated(): AbstractProductReviewRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductReviewRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(CachedProductReviewRoute::buildName($productId)));

        return $this->decorated->load($productId, $request, $context, $criteria);
    }
}
