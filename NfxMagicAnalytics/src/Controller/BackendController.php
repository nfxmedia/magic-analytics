<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Attribute\Route;

use Shopware\Core\Framework\Context;

use Nfx\MagicAnalytics\Components\Statistics\Connector;
use Nfx\MagicAnalytics\Components\Base;

#[Route(defaults: ['_routeScope' => ['administration']])]
class BackendController extends AbstractController
{
    public function __construct(
        private readonly Connector $connector,
        private readonly Base $base
    ) {

    }

    #[Route(path: '/api/nfx/analytics/getProductsGridData', name: 'api.nfx.analytics.getProductsGridData', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsGridDataAction(Request $request, Context $context): JsonResponse
    {
        $parameters = $request->request->all()['parameters'] ?? [];

        $result = $this->connector->getProductsGridData($parameters, $context);

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getQuickOverview', name: 'api.nfx.analytics.getQuickOverview', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getQuickOverviewAction(Request $request, Context $context): JsonResponse
    {
        //Schnellübersicht
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'QuickOverview');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getOrdersCountAll', name: 'api.nfx.analytics.getOrdersCountAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersCountAllAction(Request $request, Context $context): JsonResponse
    {
        //Erstbestellungen
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'OrdersCountAll');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesAll', name: 'api.nfx.analytics.getSalesAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAll');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesAllInvoice', name: 'api.nfx.analytics.getSalesAllInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAllInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesAllPwreturn', name: 'api.nfx.analytics.getSalesAllPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAllPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByMonth', name: 'api.nfx.analytics.getSalesByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonth');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByMonthInvoice', name: 'api.nfx.analytics.getSalesByMonthInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonthInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByMonthPwreturn', name: 'api.nfx.analytics.getSalesByMonthPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonthPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByQuarter', name: 'api.nfx.analytics.getSalesByQuarter', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarter');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByQuarterInvoice', name: 'api.nfx.analytics.getSalesByQuarterInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarterInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByQuarterPwreturn', name: 'api.nfx.analytics.getSalesByQuarterPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarterPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByPayment', name: 'api.nfx.analytics.getSalesByPayment', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPaymentAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Zahlungsart
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByPayment');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByShipping', name: 'api.nfx.analytics.getSalesByShipping', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByShippingAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Versandart
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByShipping');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/nfx/analytics/getSalesByManufacturer', name: 'api.nfx.analytics.getSalesByManufacturer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByManufacturerAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Hersteller
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByManufacturer'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByProducts', name: 'api.nfx.analytics.getSalesByProducts', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProducts'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByProductsPwreturn', name: 'api.nfx.analytics.getSalesByProductsPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProductsPwreturn'));
    }

    #[Route(path: '/api/nfx/analytics/getPickwareReturns', name: 'api.nfx.analytics.getPickwareReturns', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getPickwareReturnsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'PickwareReturns'));
    }

    #[Route(path: '/api/nfx/analytics/getCountByProducts', name: 'api.nfx.analytics.getCountByProducts', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCountByProductsAction(Request $request, Context $context): JsonResponse
    {
        // Anzahl Verkäufe nach Produkt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CountByProducts'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCountry', name: 'api.nfx.analytics.getSalesByCountry', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCountryAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Liefer-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCountry'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByBillingCountry', name: 'api.nfx.analytics.getSalesByBillingCountry', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBillingCountryAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rechnungs-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBillingCountry'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByBillingCountryInvoice', name: 'api.nfx.analytics.getSalesByBillingCountryInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBillingCountryInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rechnungs-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBillingCountryInvoice'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByLanguage', name: 'api.nfx.analytics.getSalesByLanguage', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByLanguageAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Sprache
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByLanguage'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesBySaleschannels', name: 'api.nfx.analytics.getSalesBySaleschannels', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesBySaleschannelsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Saleschannel
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesBySaleschannels'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByAffiliates', name: 'api.nfx.analytics.getSalesByAffiliates', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByAffiliatesAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Partner
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByAffiliates'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCampaign', name: 'api.nfx.analytics.getSalesByCampaign', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCampaignAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kampagne
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCampaign'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCustomergroups', name: 'api.nfx.analytics.getSalesByCustomergroups', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCustomergroupsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kundengruppen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCustomergroups'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByAccountTypes', name: 'api.nfx.analytics.getSalesByAccountTypes', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByAccountTypesAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kundenkonto Typ (Guest or not)
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByAccountTypes'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByWeekdays', name: 'api.nfx.analytics.getSalesByWeekdays', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByWeekdaysAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Wochentag
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByWeekdays'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByTime', name: 'api.nfx.analytics.getSalesByTime', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByTimeAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Stunde
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByTime'));
    }

    #[Route(path: '/api/nfx/analytics/getOrdersByStatus', name: 'api.nfx.analytics.getOrdersByStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Bestellstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByStatus'));
    }

    #[Route(path: '/api/nfx/analytics/getProductLowInstock', name: 'api.nfx.analytics.getProductLowInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductLowInstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte low instock
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductLowInstock'));
    }

    #[Route(path: '/api/nfx/analytics/getProductHighInstock', name: 'api.nfx.analytics.getProductHighInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductHighInstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte high instock
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductHighInstock'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByPromotion', name: 'api.nfx.analytics.getSalesByPromotion', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPromotionAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Gutschein
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByPromotion'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByPromotionOthers', name: 'api.nfx.analytics.getSalesByPromotionOthers', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPromotionOthersAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rabatt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByPromotionOthers'));
    }

    #[Route(path: '/api/nfx/analytics/getProductInactiveWithInstock', name: 'api.nfx.analytics.getProductInactiveWithInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductInactiveWithInstockAction(Request $request, Context $context): JsonResponse
    {
        // nicht aktive Produkte mit Lagerbestand
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductInactiveWithInstock'));
    }

    #[Route(path: '/api/nfx/analytics/getProductByOrders', name: 'api.nfx.analytics.getProductByOrders', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductByOrdersAction(Request $request, Context $context): JsonResponse
    {
        // Anzahl Orders mit Produkt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductByOrders'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCustomer', name: 'api.nfx.analytics.getSalesByCustomer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCustomerAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCustomer'));
    }

    #[Route(path: '/api/nfx/analytics/getNewCustomersByTime', name: 'api.nfx.analytics.getNewCustomersByTime', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getNewCustomersByTimeAction(Request $request, Context $context): JsonResponse
    {
        // Neukundenanmeldungen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'NewCustomersByTime'));
    }

    #[Route(path: '/api/nfx/analytics/getCustomerAge', name: 'api.nfx.analytics.getCustomerAge', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerAgeAction(Request $request, Context $context): JsonResponse
    {
        // Kunden Altersverteilung
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerAge'));
    }

    #[Route(path: '/api/nfx/analytics/getCustomerOnline', name: 'api.nfx.analytics.getCustomerOnline', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerOnlineAction(Request $request, Context $context): JsonResponse
    {
        // Kunden online
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerOnline'));
    }

    #[Route(path: '/api/nfx/analytics/getCustomerBySalutation', name: 'api.nfx.analytics.getCustomerBySalutation', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerBySalutationAction(Request $request, Context $context): JsonResponse
    {
        //Kunden nach Anrede
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerBySalutation'));
    }

    #[Route(path: '/api/nfx/analytics/getProductSoonOutstock', name: 'api.nfx.analytics.getProductSoonOutstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductSoonOutstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte die voraussetzlich bals ausverkauft sein werden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductSoonOutstock'));
    }

    #[Route(path: '/api/nfx/analytics/getOrdersByTransactionStatus', name: 'api.nfx.analytics.getOrdersByTransactionStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByTransactionStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Zahlungsstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByTransactionStatus'));
    }

    #[Route(path: '/api/nfx/analytics/getOrdersByDeliveryStatus', name: 'api.nfx.analytics.getOrdersByDeliveryStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByDeliveryStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Lieferstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByDeliveryStatus'));
    }

    #[Route(path: '/api/nfx/analytics/getUnfinishedOrders', name: 'api.nfx.analytics.getUnfinishedOrders', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrders'));
    }

    #[Route(path: '/api/nfx/analytics/getUnfinishedOrdersByPayment', name: 'api.nfx.analytics.getUnfinishedOrdersByPayment', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersByPaymentAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders nach Zahlart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrdersByPayment'));
    }

    #[Route(path: '/api/nfx/analytics/getUnfinishedOrdersByCart', name: 'api.nfx.analytics.getUnfinishedOrdersByCart', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersByCartAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders nach Cart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrdersByCart'));
    }

    #[Route(path: '/api/nfx/analytics/getCanceledOrdersByMonth', name: 'api.nfx.analytics.getCanceledOrdersByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCanceledOrdersByMonthAction(Request $request, Context $context): JsonResponse
    {
        // stornierte Orders monatlich
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CanceledOrdersByMonth'));
    }

    #[Route(path: '/api/nfx/analytics/getSearchTerms', name: 'api.nfx.analytics.getSearchTerms', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchTermsAction(Request $request, Context $context): JsonResponse
    {
        // Suche Begriffe
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchTerms'));
    }

    #[Route(path: '/api/nfx/analytics/getSearchActivity', name: 'api.nfx.analytics.getSearchActivity', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchActivityAction(Request $request, Context $context): JsonResponse
    {
        // Suche Anzahl
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchActivity'));
    }

    #[Route(path: '/api/nfx/analytics/getSearchTrends', name: 'api.nfx.analytics.getSearchTrends', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchTrendsAction(Request $request, Context $context): JsonResponse
    {
        //
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchTermsTrends'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByDevice', name: 'api.nfx.analytics.getSalesByDevice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByDeviceAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Gerät
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByDevice'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByOs', name: 'api.nfx.analytics.getSalesByOs', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByOsAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach OS
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByOs'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByBrowser', name: 'api.nfx.analytics.getSalesByBrowser', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBrowserAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Browser
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBrowser'));
    }

    #[Route(path: '/api/nfx/analytics/getProductsProfit', name: 'api.nfx.analytics.getProductsProfit', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsProfitAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Profit
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductsProfit'));
    }

    #[Route(path: '/api/nfx/analytics/getProductsInventory', name: 'api.nfx.analytics.getProductsInventory', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsInventoryAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Lager
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductsInventory'));
    }

    #[Route(path: '/api/nfx/analytics/getVariantsCompare', name: 'api.nfx.analytics.getVariantsCompare', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVariantsCompareAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Varianten Vergleich
        $parameters = $this->base->getBaseParameters($request);
        $parameters['propertyGroupId'] = trim($request->request->all()['parameters']['propertyGroupId'] ?? '');
        $parameters['categoryId'] = trim($request->request->all()['parameters']['categoryId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'VariantsCompare'));
    }

    /**
    #[Route(path: '/api/nfx/analytics/getProductStream', name: 'api.nfx.analytics.getProductStream', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductStreamAction(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse([]);
    }
     * */

    #[Route(path: '/api/nfx/analytics/getSalesByProductsFilter', name: 'api.nfx.analytics.getSalesByProductsFilter', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsFilterAction(Request $request, Context $context): JsonResponse
    {
        // Produkte mit Streamfilter
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productStreamId'] = trim($request->request->all()['parameters']['productStreamId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProductsFilter'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCategory', name: 'api.nfx.analytics.getSalesByCategory', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCategoryAction(Request $request, Context $context): JsonResponse
    {
        // Sales nach Kategory
        $parameters = $this->base->getBaseParameters($request);
        $parameters['categoryId'] = trim($request->request->all()['parameters']['categoryId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCategory'));
    }

    #[Route(path: '/api/nfx/analytics/getCategoryCompare', name: 'api.nfx.analytics.getCategoryCompare', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCategoryCompareAction(Request $request, Context $context): JsonResponse
    {
        // Sales nach Kategory
        $parameters = $this->base->getBaseParameters($request);
        $parameters['categories'] = $request->request->all()['parameters']['categories'] ?? [];

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CategoryCompare'));
    }

    #[Route(path: '/api/nfx/analytics/getProductImpressions', name: 'api.nfx.analytics.getProductImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Produkte
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductImpressions'));
    }

    #[Route(path: '/api/nfx/analytics/getVisitors', name: 'api.nfx.analytics.getVisitors', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVisitorsAction(Request $request, Context $context): JsonResponse
    {
        // Besucher pro Tag
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'Visitors'));
    }

    #[Route(path: '/api/nfx/analytics/getVisitorImpressions', name: 'api.nfx.analytics.getVisitorImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVisitorImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Seiten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'VisitorImpressions'));
    }

    #[Route(path: '/api/nfx/analytics/getReferer', name: 'api.nfx.analytics.getReferer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getRefererAction(Request $request, Context $context): JsonResponse
    {
        // Besucher nach Zugriffsquellen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'Referer'));
    }

    #[Route(path: '/api/nfx/analytics/getCategoryImpressions', name: 'api.nfx.analytics.getCategoryImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCategoryImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Kategorien
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CategoryImpressions'));
    }

    #[Route(path: '/api/nfx/analytics/getManufacturerImpressions', name: 'api.nfx.analytics.getManufacturerImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getManufacturerImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Hersteller
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ManufacturerImpressions'));
    }

    #[Route(path: '/api/nfx/analytics/getLexiconImpressions', name: 'api.nfx.analytics.getLexiconImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getLexiconImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Lexikon Links
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'LexiconImpressions'));
    }

    #[Route(path: '/api/nfx/analytics/getSingleProduct', name: 'api.nfx.analytics.getSingleProduct', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSingleProductAction(Request $request, Context $context): JsonResponse
    {
        // Statistics for a single product
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->request->all()['parameters']['productId'] ?? '';
        $parameters['compareIds'] = $request->request->all()['parameters']['compareIds'] ?? [];

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SingleProduct'));
    }

    #[Route(path: '/api/nfx/analytics/getCrossSelling', name: 'api.nfx.analytics.getCrossSelling', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCrossSellingAction(Request $request, Context $context): JsonResponse
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->request->all()['parameters']['productId'] ?? '';

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CrossSelling'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByTaxrate', name: 'api.nfx.analytics.getSalesByTaxrate', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByTaxrateAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Steuerrate
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByTaxrate'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesBySalutation', name: 'api.nfx.analytics.getSalesBySalutation', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesBySalutationAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Anrede
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesBySalutation'));
    }

    #[Route(path: '/api/nfx/analytics/getSalesByCurrency', name: 'api.nfx.analytics.getSalesByCurrency', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCurrencyAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Zahlungsart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCurrency'));
    }

    #[Route(path: '/api/nfx/analytics/getConversionAll', name: 'api.nfx.analytics.getConversionAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getConversionAllAction(Request $request, Context $context): JsonResponse
    {
        //Conversion daily
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ConversionAll'));
    }

    /**
     * @Route("/api/nfx/analytics/getConversionByMonth", name="api.nfx.analytics.getConversionByMonth",  methods={"POST"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    #[Route(path: '/api/nfx/analytics/getConversionByMonth', name: 'api.nfx.analytics.getConversionByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getConversionByMonthAction(Request $request, Context $context): JsonResponse
    {
        //Conversion monthly
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ConversionByMonth'));
    }

    #[Route(path: '/api/nfx/analytics/download', name: 'api.nfx.analytics.download', defaults: ['auth_required' => false], methods: ['GET'])]
    public function download(Request $request): JsonResponse|Response
    {
        $params = $request->query->all();

        $fileName = $params['fileName'];
        $fileSize = $params['fileSize'];

        return $this->base->getDownloadResponse($fileName, $fileSize);
    }

    //csv Export aus Order oder Produkt Tabelle
    #[Route(path: '/api/nfx/analytics/csvExport', name: 'api.nfx.analytics.csvExport', defaults: ['auth_required' => true], methods: ['POST'])]
    public function csvExport(Request $request): JsonResponse
    {
        $data = $request->request->all()['data'] ?? [];

        if (empty($data)) {
            return new JsonResponse(['success' => false, 'message' => 'no data']);
        }

        $labels = array_keys($data[0]);
        $labels = implode(';', $labels);

        return new JsonResponse(["success" => true, "fileSize" => $this->base->exportCSV($data, $labels)]);
    }
}
