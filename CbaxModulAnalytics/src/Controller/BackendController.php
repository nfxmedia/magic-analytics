<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Attribute\Route;

use Shopware\Core\Framework\Context;

use Cbax\ModulAnalytics\Components\Statistics\Connector;
use Cbax\ModulAnalytics\Components\Base;

#[Route(defaults: ['_routeScope' => ['administration']])]
class BackendController extends AbstractController
{
    public function __construct(
        private readonly Connector $connector,
        private readonly Base $base
    ) {

    }

    #[Route(path: '/api/cbax/analytics/getProductsGridData', name: 'api.cbax.analytics.getProductsGridData', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsGridDataAction(Request $request, Context $context): JsonResponse
    {
        $parameters = $request->request->all()['parameters'] ?? [];

        $result = $this->connector->getProductsGridData($parameters, $context);

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getQuickOverview', name: 'api.cbax.analytics.getQuickOverview', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getQuickOverviewAction(Request $request, Context $context): JsonResponse
    {
        //Schnellübersicht
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'QuickOverview');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getOrdersCountAll', name: 'api.cbax.analytics.getOrdersCountAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersCountAllAction(Request $request, Context $context): JsonResponse
    {
        //Erstbestellungen
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'OrdersCountAll');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesAll', name: 'api.cbax.analytics.getSalesAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAll');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesAllInvoice', name: 'api.cbax.analytics.getSalesAllInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAllInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesAllPwreturn', name: 'api.cbax.analytics.getSalesAllPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesAllPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // täglicher Umsatz
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesAllPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByMonth', name: 'api.cbax.analytics.getSalesByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonth');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByMonthInvoice', name: 'api.cbax.analytics.getSalesByMonthInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonthInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByMonthPwreturn', name: 'api.cbax.analytics.getSalesByMonthPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByMonthPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz monatlich
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByMonthPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByQuarter', name: 'api.cbax.analytics.getSalesByQuarter', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarter');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByQuarterInvoice', name: 'api.cbax.analytics.getSalesByQuarterInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarterInvoice');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByQuarterPwreturn', name: 'api.cbax.analytics.getSalesByQuarterPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByQuarterPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz Quartal
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByQuarterPwreturn');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByPayment', name: 'api.cbax.analytics.getSalesByPayment', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPaymentAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Zahlungsart
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByPayment');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByShipping', name: 'api.cbax.analytics.getSalesByShipping', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByShippingAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Versandart
        $parameters = $this->base->getBaseParameters($request);
        $result = $this->connector->getStatisticsData($parameters, $context, 'SalesByShipping');

        return new JsonResponse($result);
    }

    #[Route(path: '/api/cbax/analytics/getSalesByManufacturer', name: 'api.cbax.analytics.getSalesByManufacturer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByManufacturerAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Hersteller
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByManufacturer'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByProducts', name: 'api.cbax.analytics.getSalesByProducts', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProducts'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByProductsPwreturn', name: 'api.cbax.analytics.getSalesByProductsPwreturn', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsPwreturnAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProductsPwreturn'));
    }

    #[Route(path: '/api/cbax/analytics/getPickwareReturns', name: 'api.cbax.analytics.getPickwareReturns', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getPickwareReturnsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Produkten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'PickwareReturns'));
    }

    #[Route(path: '/api/cbax/analytics/getCountByProducts', name: 'api.cbax.analytics.getCountByProducts', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCountByProductsAction(Request $request, Context $context): JsonResponse
    {
        // Anzahl Verkäufe nach Produkt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CountByProducts'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCountry', name: 'api.cbax.analytics.getSalesByCountry', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCountryAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Liefer-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCountry'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByBillingCountry', name: 'api.cbax.analytics.getSalesByBillingCountry', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBillingCountryAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rechnungs-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBillingCountry'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByBillingCountryInvoice', name: 'api.cbax.analytics.getSalesByBillingCountryInvoice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBillingCountryInvoiceAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rechnungs-Land des Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBillingCountryInvoice'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByLanguage', name: 'api.cbax.analytics.getSalesByLanguage', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByLanguageAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Sprache
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByLanguage'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesBySaleschannels', name: 'api.cbax.analytics.getSalesBySaleschannels', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesBySaleschannelsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Saleschannel
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesBySaleschannels'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByAffiliates', name: 'api.cbax.analytics.getSalesByAffiliates', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByAffiliatesAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Partner
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByAffiliates'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCampaign', name: 'api.cbax.analytics.getSalesByCampaign', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCampaignAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kampagne
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCampaign'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCustomergroups', name: 'api.cbax.analytics.getSalesByCustomergroups', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCustomergroupsAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kundengruppen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCustomergroups'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByAccountTypes', name: 'api.cbax.analytics.getSalesByAccountTypes', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByAccountTypesAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kundenkonto Typ (Guest or not)
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByAccountTypes'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByWeekdays', name: 'api.cbax.analytics.getSalesByWeekdays', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByWeekdaysAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Wochentag
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByWeekdays'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByTime', name: 'api.cbax.analytics.getSalesByTime', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByTimeAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Stunde
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByTime'));
    }

    #[Route(path: '/api/cbax/analytics/getOrdersByStatus', name: 'api.cbax.analytics.getOrdersByStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Bestellstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByStatus'));
    }

    #[Route(path: '/api/cbax/analytics/getProductLowInstock', name: 'api.cbax.analytics.getProductLowInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductLowInstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte low instock
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductLowInstock'));
    }

    #[Route(path: '/api/cbax/analytics/getProductHighInstock', name: 'api.cbax.analytics.getProductHighInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductHighInstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte high instock
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductHighInstock'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByPromotion', name: 'api.cbax.analytics.getSalesByPromotion', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPromotionAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Gutschein
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByPromotion'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByPromotionOthers', name: 'api.cbax.analytics.getSalesByPromotionOthers', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByPromotionOthersAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Rabatt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByPromotionOthers'));
    }

    #[Route(path: '/api/cbax/analytics/getProductInactiveWithInstock', name: 'api.cbax.analytics.getProductInactiveWithInstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductInactiveWithInstockAction(Request $request, Context $context): JsonResponse
    {
        // nicht aktive Produkte mit Lagerbestand
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductInactiveWithInstock'));
    }

    #[Route(path: '/api/cbax/analytics/getProductByOrders', name: 'api.cbax.analytics.getProductByOrders', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductByOrdersAction(Request $request, Context $context): JsonResponse
    {
        // Anzahl Orders mit Produkt
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductByOrders'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCustomer', name: 'api.cbax.analytics.getSalesByCustomer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCustomerAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Kunden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCustomer'));
    }

    #[Route(path: '/api/cbax/analytics/getNewCustomersByTime', name: 'api.cbax.analytics.getNewCustomersByTime', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getNewCustomersByTimeAction(Request $request, Context $context): JsonResponse
    {
        // Neukundenanmeldungen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'NewCustomersByTime'));
    }

    #[Route(path: '/api/cbax/analytics/getCustomerAge', name: 'api.cbax.analytics.getCustomerAge', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerAgeAction(Request $request, Context $context): JsonResponse
    {
        // Kunden Altersverteilung
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerAge'));
    }

    #[Route(path: '/api/cbax/analytics/getCustomerOnline', name: 'api.cbax.analytics.getCustomerOnline', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerOnlineAction(Request $request, Context $context): JsonResponse
    {
        // Kunden online
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerOnline'));
    }

    #[Route(path: '/api/cbax/analytics/getCustomerBySalutation', name: 'api.cbax.analytics.getCustomerBySalutation', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCustomerBySalutationAction(Request $request, Context $context): JsonResponse
    {
        //Kunden nach Anrede
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CustomerBySalutation'));
    }

    #[Route(path: '/api/cbax/analytics/getProductSoonOutstock', name: 'api.cbax.analytics.getProductSoonOutstock', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductSoonOutstockAction(Request $request, Context $context): JsonResponse
    {
        // Produkte die voraussetzlich bals ausverkauft sein werden
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductSoonOutstock'));
    }

    #[Route(path: '/api/cbax/analytics/getOrdersByTransactionStatus', name: 'api.cbax.analytics.getOrdersByTransactionStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByTransactionStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Zahlungsstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByTransactionStatus'));
    }

    #[Route(path: '/api/cbax/analytics/getOrdersByDeliveryStatus', name: 'api.cbax.analytics.getOrdersByDeliveryStatus', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getOrdersByDeliveryStatusAction(Request $request, Context $context): JsonResponse
    {
        // Orders nach Lieferstatus
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'OrdersByDeliveryStatus'));
    }

    #[Route(path: '/api/cbax/analytics/getUnfinishedOrders', name: 'api.cbax.analytics.getUnfinishedOrders', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrders'));
    }

    #[Route(path: '/api/cbax/analytics/getUnfinishedOrdersByPayment', name: 'api.cbax.analytics.getUnfinishedOrdersByPayment', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersByPaymentAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders nach Zahlart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrdersByPayment'));
    }

    #[Route(path: '/api/cbax/analytics/getUnfinishedOrdersByCart', name: 'api.cbax.analytics.getUnfinishedOrdersByCart', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getUnfinishedOrdersByCartAction(Request $request, Context $context): JsonResponse
    {
        // abgebrochene Orders nach Cart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'UnfinishedOrdersByCart'));
    }

    #[Route(path: '/api/cbax/analytics/getCanceledOrdersByMonth', name: 'api.cbax.analytics.getCanceledOrdersByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCanceledOrdersByMonthAction(Request $request, Context $context): JsonResponse
    {
        // stornierte Orders monatlich
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CanceledOrdersByMonth'));
    }

    #[Route(path: '/api/cbax/analytics/getSearchTerms', name: 'api.cbax.analytics.getSearchTerms', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchTermsAction(Request $request, Context $context): JsonResponse
    {
        // Suche Begriffe
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchTerms'));
    }

    #[Route(path: '/api/cbax/analytics/getSearchActivity', name: 'api.cbax.analytics.getSearchActivity', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchActivityAction(Request $request, Context $context): JsonResponse
    {
        // Suche Anzahl
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchActivity'));
    }

    #[Route(path: '/api/cbax/analytics/getSearchTrends', name: 'api.cbax.analytics.getSearchTrends', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSearchTrendsAction(Request $request, Context $context): JsonResponse
    {
        //
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SearchTermsTrends'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByDevice', name: 'api.cbax.analytics.getSalesByDevice', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByDeviceAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Gerät
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByDevice'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByOs', name: 'api.cbax.analytics.getSalesByOs', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByOsAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach OS
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByOs'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByBrowser', name: 'api.cbax.analytics.getSalesByBrowser', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByBrowserAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Browser
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByBrowser'));
    }

    #[Route(path: '/api/cbax/analytics/getProductsProfit', name: 'api.cbax.analytics.getProductsProfit', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsProfitAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Profit
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductsProfit'));
    }

    #[Route(path: '/api/cbax/analytics/getProductsInventory', name: 'api.cbax.analytics.getProductsInventory', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductsInventoryAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Lager
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductsInventory'));
    }

    #[Route(path: '/api/cbax/analytics/getVariantsCompare', name: 'api.cbax.analytics.getVariantsCompare', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVariantsCompareAction(Request $request, Context $context): JsonResponse
    {
        // Produkte Varianten Vergleich
        $parameters = $this->base->getBaseParameters($request);
        $parameters['propertyGroupId'] = trim($request->request->all()['parameters']['propertyGroupId'] ?? '');
        $parameters['categoryId'] = trim($request->request->all()['parameters']['categoryId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'VariantsCompare'));
    }

    /**
    #[Route(path: '/api/cbax/analytics/getProductStream', name: 'api.cbax.analytics.getProductStream', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductStreamAction(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse([]);
    }
     * */

    #[Route(path: '/api/cbax/analytics/getSalesByProductsFilter', name: 'api.cbax.analytics.getSalesByProductsFilter', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByProductsFilterAction(Request $request, Context $context): JsonResponse
    {
        // Produkte mit Streamfilter
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productStreamId'] = trim($request->request->all()['parameters']['productStreamId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByProductsFilter'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCategory', name: 'api.cbax.analytics.getSalesByCategory', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCategoryAction(Request $request, Context $context): JsonResponse
    {
        // Sales nach Kategory
        $parameters = $this->base->getBaseParameters($request);
        $parameters['categoryId'] = trim($request->request->all()['parameters']['categoryId'] ?? '');

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCategory'));
    }

    #[Route(path: '/api/cbax/analytics/getCategoryCompare', name: 'api.cbax.analytics.getCategoryCompare', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCategoryCompareAction(Request $request, Context $context): JsonResponse
    {
        // Sales nach Kategory
        $parameters = $this->base->getBaseParameters($request);
        $parameters['categories'] = $request->request->all()['parameters']['categories'] ?? [];

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CategoryCompare'));
    }

    #[Route(path: '/api/cbax/analytics/getProductImpressions', name: 'api.cbax.analytics.getProductImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getProductImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Produkte
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ProductImpressions'));
    }

    #[Route(path: '/api/cbax/analytics/getVisitors', name: 'api.cbax.analytics.getVisitors', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVisitorsAction(Request $request, Context $context): JsonResponse
    {
        // Besucher pro Tag
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'Visitors'));
    }

    #[Route(path: '/api/cbax/analytics/getVisitorImpressions', name: 'api.cbax.analytics.getVisitorImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getVisitorImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Seiten
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'VisitorImpressions'));
    }

    #[Route(path: '/api/cbax/analytics/getReferer', name: 'api.cbax.analytics.getReferer', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getRefererAction(Request $request, Context $context): JsonResponse
    {
        // Besucher nach Zugriffsquellen
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'Referer'));
    }

    #[Route(path: '/api/cbax/analytics/getCategoryImpressions', name: 'api.cbax.analytics.getCategoryImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCategoryImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Kategorien
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CategoryImpressions'));
    }

    #[Route(path: '/api/cbax/analytics/getManufacturerImpressions', name: 'api.cbax.analytics.getManufacturerImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getManufacturerImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Hersteller
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ManufacturerImpressions'));
    }

    #[Route(path: '/api/cbax/analytics/getLexiconImpressions', name: 'api.cbax.analytics.getLexiconImpressions', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getLexiconImpressionsAction(Request $request, Context $context): JsonResponse
    {
        // geklickte Lexikon Links
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'LexiconImpressions'));
    }

    #[Route(path: '/api/cbax/analytics/getSingleProduct', name: 'api.cbax.analytics.getSingleProduct', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSingleProductAction(Request $request, Context $context): JsonResponse
    {
        // Statistics for a single product
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->request->all()['parameters']['productId'] ?? '';
        $parameters['compareIds'] = $request->request->all()['parameters']['compareIds'] ?? [];

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SingleProduct'));
    }

    #[Route(path: '/api/cbax/analytics/getCrossSelling', name: 'api.cbax.analytics.getCrossSelling', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getCrossSellingAction(Request $request, Context $context): JsonResponse
    {
        $parameters = $this->base->getBaseParameters($request);
        $parameters['productId'] = $request->request->all()['parameters']['productId'] ?? '';

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'CrossSelling'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByTaxrate', name: 'api.cbax.analytics.getSalesByTaxrate', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByTaxrateAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Steuerrate
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByTaxrate'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesBySalutation', name: 'api.cbax.analytics.getSalesBySalutation', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesBySalutationAction(Request $request, Context $context): JsonResponse
    {
        //Umsatz nach Anrede
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesBySalutation'));
    }

    #[Route(path: '/api/cbax/analytics/getSalesByCurrency', name: 'api.cbax.analytics.getSalesByCurrency', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getSalesByCurrencyAction(Request $request, Context $context): JsonResponse
    {
        // Umsatz nach Zahlungsart
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'SalesByCurrency'));
    }

    #[Route(path: '/api/cbax/analytics/getConversionAll', name: 'api.cbax.analytics.getConversionAll', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getConversionAllAction(Request $request, Context $context): JsonResponse
    {
        //Conversion daily
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ConversionAll'));
    }

    /**
     * @Route("/api/cbax/analytics/getConversionByMonth", name="api.cbax.analytics.getConversionByMonth",  methods={"POST"}, defaults={"auth_required"=true, "_routeScope"={"administration"}})
     */
    #[Route(path: '/api/cbax/analytics/getConversionByMonth', name: 'api.cbax.analytics.getConversionByMonth', defaults: ['auth_required' => true], methods: ['POST'])]
    public function getConversionByMonthAction(Request $request, Context $context): JsonResponse
    {
        //Conversion monthly
        $parameters = $this->base->getBaseParameters($request);

        return new JsonResponse($this->connector->getStatisticsData($parameters, $context, 'ConversionByMonth'));
    }

    #[Route(path: '/api/cbax/analytics/download', name: 'api.cbax.analytics.download', defaults: ['auth_required' => false], methods: ['GET'])]
    public function download(Request $request): JsonResponse|Response
    {
        $params = $request->query->all();

        $fileName = $params['fileName'];
        $fileSize = $params['fileSize'];

        return $this->base->getDownloadResponse($fileName, $fileSize);
    }

    //csv Export aus Order oder Produkt Tabelle
    #[Route(path: '/api/cbax/analytics/csvExport', name: 'api.cbax.analytics.csvExport', defaults: ['auth_required' => true], methods: ['POST'])]
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
