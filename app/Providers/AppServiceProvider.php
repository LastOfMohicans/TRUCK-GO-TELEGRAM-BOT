<?php
declare(strict_types=1);

namespace App\Providers;

use App\Clients\DaData\DaData;
use App\Clients\Graphhopper\Graphhopper;
use App\Clients\Yandex\Yandex;
use App\Contracts\GeocodingInterface;
use App\Contracts\GeocodingMock;
use App\Contracts\INNInterface;
use App\Contracts\INNMock;
use App\Contracts\ReverseGeocodingInterface;
use App\Contracts\RouteInterface;
use App\Contracts\RouteMock;
use App\Repositories\Catalog\CatalogRepository;
use App\Repositories\Catalog\CatalogRepositoryInterface;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Client\ClientRepositoryInterface;
use App\Repositories\Complaint\ComplaintRepository;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Repositories\Delivery\DeliveryRepository;
use App\Repositories\Delivery\DeliveryRepositoryInterface;
use App\Repositories\Material\MaterialRepository;
use App\Repositories\Material\MaterialRepositoryInterface;
use App\Repositories\MaterialQuestion\MaterialQuestionRepository;
use App\Repositories\MaterialQuestion\MaterialQuestionRepositoryInterface;
use App\Repositories\MaterialQuestionAnswer\MaterialQuestionAnswerRepository;
use App\Repositories\MaterialQuestionAnswer\MaterialQuestionAnswerRepositoryInterface;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderHistory\OrderHistoryRepository;
use App\Repositories\OrderHistory\OrderHistoryRepositoryInterface;
use App\Repositories\OrderQuestionAnswer\OrderQuestionAnswerRepository;
use App\Repositories\OrderQuestionAnswer\OrderQuestionAnswerRepositoryInterface;
use App\Repositories\OrderRequest\OrderRequestRepository;
use App\Repositories\OrderRequest\OrderRequestRepositoryInterface;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepository;
use App\Repositories\OrderRequestHistory\OrderRequestHistoryRepositoryInterface;
use App\Repositories\StorageMaterial\StorageMaterialRepository;
use App\Repositories\StorageMaterial\StorageMaterialRepositoryInterface;
use App\Repositories\Vendor\VendorRepository;
use App\Repositories\Vendor\VendorRepositoryInterface;
use App\Repositories\VendorStorage\VendorStorageRepository;
use App\Repositories\VendorStorage\VendorStorageRepositoryInterface;
use App\Repositories\VendorVendorStorage\VendorVendorStorageRepository;
use App\Repositories\VendorVendorStorage\VendorVendorStorageRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        if (!empty(config('graphhopper.api_key'))) {
            $this->app->singleton(Graphhopper::class, function ($app) {
                return new Graphhopper(config('graphhopper.api_key'));
            });
        }

        if (!empty(config('dadata.api_key'))) {
            $this->app->singleton(Dadata::class, function ($app) {
                return new DaData(config('dadata.api_key'), config('dadata.secret'));
            });
        }

        if (!empty(config('yandex.yandex_api_key'))) {
            $this->app->singleton(Yandex::class, function ($app) {
                return new Yandex(config('yandex_api_key'));
            });
        }

        $this->app->bind(GeocodingInterface::class, function ($app) {
            if (config('mocks.is_geocoding_mocked')) {
                return new GeocodingMock();
            }
            return $app->get(Dadata::class);
        });
        $this->app->bind(ReverseGeocodingInterface::class, function ($app) {
            if (config('mocks.is_reverse_geocoding_mocked')) {
                return new GeocodingMock();
            }
            return $app->get(DaData::class);
        });
        $this->app->bind(INNInterface::class, function ($app) {
            if (config('mocks.is_inn_mocked')) {
                return new INNMock();
            }
            return $app->get(Dadata::class);
        });
        $this->app->bind(RouteInterface::class, function ($app) {
            if (config('mocks.is_routing_mocked')) {
                return new RouteMock();
            }
            return $app->get(Graphhopper::class);
        });

        $this->app->bind(CatalogRepositoryInterface::class, function ($app) {
            return new CatalogRepository();
        });
        $this->app->bind(ComplaintRepositoryInterface::class, function ($app) {
            return new ComplaintRepository();
        });
        $this->app->bind(DeliveryRepositoryInterface::class, function ($app) {
            return new DeliveryRepository();
        });
        $this->app->bind(MaterialRepositoryInterface::class, function ($app) {
            return new MaterialRepository();
        });
        $this->app->bind(MaterialQuestionRepositoryInterface::class, function ($app) {
            return new MaterialQuestionRepository();
        });
        $this->app->bind(MaterialQuestionAnswerRepositoryInterface::class, function ($app) {
            return new MaterialQuestionAnswerRepository();
        });
        $this->app->bind(OrderHistoryRepositoryInterface::class, function ($app) {
            return new OrderHistoryRepository();
        });
        $this->app->bind(OrderRepositoryInterface::class, function ($app) {
            return new OrderRepository();
        });
        $this->app->bind(OrderQuestionAnswerRepositoryInterface::class, function ($app) {
            return new OrderQuestionAnswerRepository();
        });
        $this->app->bind(OrderRequestRepositoryInterface::class, function ($app) {
            return new OrderRequestRepository();
        });
        $this->app->bind(OrderRequestHistoryRepositoryInterface::class, function ($app) {
            return new OrderRequestHistoryRepository();
        });
        $this->app->bind(StorageMaterialRepositoryInterface::class, function ($app) {
            return new StorageMaterialRepository();
        });
        $this->app->bind(ClientRepositoryInterface::class, function ($app) {
            return new ClientRepository();
        });
        $this->app->bind(VendorRepositoryInterface::class, function ($app) {
            return new VendorRepository();
        });
        $this->app->bind(VendorStorageRepositoryInterface::class, function ($app) {
            return new VendorStorageRepository();
        });
        $this->app->bind(VendorVendorStorageRepositoryInterface::class, function ($app) {
            return new VendorVendorStorageRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
