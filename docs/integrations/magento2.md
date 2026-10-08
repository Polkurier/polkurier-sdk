Magento 2 / Adobe Commerce
==========================

Polkurier has its own module for Magento, so you don't need to build an integration on the SDK. The
module uses the SDK for every call to Polkurier.

What the module does
--------------------

`polkurier/magento2-shipping` (module `Polkurier_Shipping`) ships Magento orders with Polkurier:

- Polkurier's carriers at checkout, at your own prices and under your own names.
- A map for customers to pick a parcel locker or pickup point.
- Shipping from Magento's own shipment page, with the Polkurier label stored like any carrier's, or
  from a shipment screen with every option on one page.
- Parcels packed into your pack templates automatically, by product size and weight, and orders
  shipped in parts.
- Labels and protocols, courier pickups and delivery tracking.
- Cash on delivery tracking and payout requests.
- Sender addresses, bank accounts and pack templates kept in step with your Polkurier account.
- A REST API and events for integrating other systems.

Requirements
------------

Magento Open Source or Adobe Commerce 2.4.7 or later, and PHP 8.3, 8.4 or 8.5. The SDK is installed
with the module.

Installation
------------

```bash
composer require polkurier/magento2-shipping
bin/magento module:enable Polkurier_Shipping
bin/magento setup:upgrade
```

Then enter your customer ID and API token under **Stores > Configuration > Sales > Delivery
Methods > Polkurier**.

### Multi-address checkout

For Magento's multi-address checkout (**Check Out with Multiple Addresses**), also install the
bridge module `polkurier/magento2-multishipping`. It adds pickup point selection to each address in
that checkout. It is only needed with that checkout, which many stores turn off.

```bash
composer require polkurier/magento2-multishipping
bin/magento module:enable Polkurier_Multishipping
bin/magento setup:upgrade
```

It needs `Magento_Multishipping` enabled and `polkurier/magento2-shipping` installed.

Documentation
-------------

Each package has its guides in its `docs/` directory:

- The **user guide** covers installation, configuration and everyday use: carriers and prices,
  checkout, shipping an order, managing shipments, cash on delivery and troubleshooting.
- The **developer guide** covers how the module is built and where to hook into it: the REST API,
  events and the supported extension points.

Customising it
--------------

The module's developer guide lists the supported extension points, such as plugins on its service
contracts, and the events it dispatches.

For a Polkurier call the module doesn't make yet, the developer guide describes using the SDK
client the module already configures for the account. The SDK's own pages, starting with
[Services](../services.md), describe each call.
