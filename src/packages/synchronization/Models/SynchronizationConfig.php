<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 16.03.2018
 * Time: 09:18
 */

namespace Arrow\Shop\Models\Esotiq\Synchronization;


use Arrow\Shop\Models\Esotiq\Synchronization\Synchronizers\Connectors\Parlours;
use Arrow\Shop\Models\Esotiq\Synchronization\Synchronizers\OrdersSynchronizer;
use Arrow\Shop\Models\Esotiq\Synchronization\Synchronizers\WarehouseSynchronizer;
use Arrow\Shop\Models\Persistent\Warehouse;

class SynchronizationConfig
{

    protected $config;

    public function __construct()
    {
        $this->config = [
            new SynchronizationAction("Magazyny", "warehouses", [], [
                new SynchronizationAction("Pobierz magazyn lokalny", "warehouse_local", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_ESOTIQ]]
                ]),
                new SynchronizationAction("Pobierz magazyn centralny", "warehouse_external", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_CENTRAL]]
                ]),
                new SynchronizationAction("Magazyny salonów", "warehouse_parlours", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_PARLOUR]]
                ]),
                new SynchronizationAction("Pobierz magazyn outlet", "warehouse_outlet", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_OUTLET]]
                ]),
                new SynchronizationAction("Pobierz magazyn UA", "warehouse_ua", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_UKRAINE]]
                ]),
                new SynchronizationAction("Pobierz magazyn DE", "warehouse_de", [
                    [WarehouseSynchronizer::class, "importFrom", [Warehouse::STORE_GERMANY]]
                ]),
            ]),
            new SynchronizationAction("Zamówienia", "warehouses", [], [
                new SynchronizationAction("Wysyłka zamówień GATE PL", "orders_send_pl", [
                    [OrdersSynchronizer::class, "uploadOrdersInfo", [Warehouse::STORE_ESOTIQ]]
                ]),
                new SynchronizationAction("Odbiór GATE PL", "orders_get_pl", [
                    [OrdersSynchronizer::class, "downloadOrdersInfo", [Warehouse::STORE_ESOTIQ]]
                ]),

                new SynchronizationAction("Wysyłka zamówień do salonów", "orders_send_parlour", [
                    [OrdersSynchronizer::class, "uploadOrdersInfo", [Warehouse::STORE_PARLOUR]]
                ]),
                new SynchronizationAction("Odbiór zamówień z salonów (aktualizacja)", "orders_get_parlour", [
                    [OrdersSynchronizer::class, "downloadOrdersInfo", [Warehouse::STORE_PARLOUR]]
                ]),
                new SynchronizationAction("Wysyłka zamówień UA", "orders_send_ua", [
                    [OrdersSynchronizer::class, "uploadOrdersInfo", [Warehouse::STORE_UKRAINE]]
                ]),

                new SynchronizationAction("Wysyłka zamówień DE", "orders_send_de", [
                    [OrdersSynchronizer::class, "uploadOrdersInfo", [Warehouse::STORE_GERMANY]]
                ]),
                new SynchronizationAction("Odbiór zamówień DE", "orders_get_de", [
                    [OrdersSynchronizer::class, "downloadOrdersInfo", [Warehouse::STORE_GERMANY]]
                ]),

                new SynchronizationAction("Wysyłka korekt zaównień Outlet", "orders_send_outlet", [
                    [OrdersSynchronizer::class, "uploadOrdersInfo", [Warehouse::STORE_OUTLET]]
                ]),


            ]),
            new SynchronizationAction("Mailing", "mails", [], [
                new SynchronizationAction("Kolejka wysyłki maili", "mails_queue", [
                    []
                ]),
                new SynchronizationAction("Powiadomienia o dostępności towarów", "mails_available_info", [
                    []
                ]),
                new SynchronizationAction("Przypomnienie o płatności", "mails_payment_request", [
                    []
                ]),
                new SynchronizationAction("Raporty ze sklepu dla dyrekcji", "mails_shop_report", [
                    []
                ]),

            ]),
        ];

    }


    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $actionName
     * @return SynchronizationAction
     */
    public function getActionByName($actionName)
    {
        foreach ($this->config as $main) {
            if ($main->actionName == $actionName) {
                return $main;
            }
            foreach ($main->subTasks as $sub) {
                if ($sub->actionName == $actionName) {
                    return $sub;
                }
            }
        }

    }

    public function getFlatActionNameLabel()
    {
        foreach ($this->config as $main) {
            if ($main->actionName == $actionName) {
                return $main;
            }
            foreach ($main->subTasks as $sub) {
                if ($sub->actionName == $actionName) {
                    return $sub;
                }
            }
        }


    }

}
