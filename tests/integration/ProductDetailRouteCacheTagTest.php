<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Shopware\Core\Content\Test\Product\ProductBuilder;
use Shopware\Core\Framework\Adapter\Cache\CacheTagCollector;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Core\Test\TestDefaults;
use Swag\DisableStoreApiCache\Cache\ProductDetailRouteTagDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * End-to-end proof that calling the product detail route registers the legacy
 * route-name cache tag with the CacheTagCollector, even though the object-cache
 * decorator has been removed.
 *
 * @internal
 */
class ProductDetailRouteCacheTagTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    public function testDetailRouteRegistersLegacyRouteTag(): void
    {
        $tax = $this->context->getTaxRules()->first();
        static::assertNotNull($tax);

        // Reuse the tax that already exists in the sales channel context so the price
        // can be calculated when the route loads the product.
        $ids = new IdsCollection();
        $ids->set('reuse-tax', $tax->getId());

        $product = (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->tax('reuse-tax', (int) $tax->getTaxRate())
            ->visibility(TestDefaults::SALES_CHANNEL)
            ->build();

        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('product.repository');
        $repository->create([$product], Context::createDefaultContext());

        $productId = $ids->get('p1');

        // The collector keys tags by the current request URI, so the same request
        // must be on the stack while the route runs and when we read the tags back.
        $request = new Request();
        $request->server->set('REQUEST_URI', '/store-api/product/' . $productId);

        /** @var RequestStack $requestStack */
        $requestStack = static::getContainer()->get('request_stack');
        $requestStack->push($request);

        try {
            $route = static::getContainer()->get(ProductDetailRoute::class);

            // Sanity check: the plugin's tag decorator is in front of the real route.
            static::assertInstanceOf(ProductDetailRouteTagDecorator::class, $route);

            $route->load($productId, $request, $this->context, new Criteria());

            $tags = static::getContainer()->get(CacheTagCollector::class)->get($request);
        } finally {
            $requestStack->pop();
        }

        static::assertContains(
            CachedProductDetailRoute::buildName($productId),
            $tags,
            'The legacy product-detail-route tag must be collected so HTTP cache invalidation keeps matching'
        );
    }
}
