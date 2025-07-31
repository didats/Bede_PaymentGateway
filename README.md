# Bede Payment Gateway Module for Magento 2

A comprehensive payment gateway integration module for Magento 2 that provides payment processing, refund management, and transaction tracking capabilities through the Bede payment system.

## Features

- **Payment Processing**: Seamless integration with Bede payment gateway
- **Refund Management**: Complete refund workflow with status tracking
- **Transaction Logging**: Detailed logging of all payment transactions
- **Admin Interface**: User-friendly admin panel for payment management
- **Payment Search**: Advanced search functionality for payment records
- **Order Integration**: Direct integration with Magento's order system
- **Status Tracking**: Real-time payment and refund status updates

## Installation

### Method 1: Manual Installation

1. Download the module files
2. Create the following directory structure in your Magento installation:
   ```
   app/code/Bede/PaymentGateway/
   ```
3. Copy all module files to the created directory
4. Run the following commands:
   ```bash
   php bin/magento module:enable Bede_PaymentGateway
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   php bin/magento cache:clean
   ```

### Method 2: Composer Installation

```bash
composer require bede/payment-gateway
php bin/magento module:enable Bede_PaymentGateway
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:clean
```

## Configuration

### Payment Gateway Settings

1. Navigate to **Admin Panel** → **Stores** → **Configuration** → **Sales** → **Payment Methods**
2. Find **Bede Payment Gateway** section
3. Configure the following settings:
   - **Enabled**: Enable/disable the payment method
   - **Merchant ID**: Your Bede merchant identifier
   - **Secret Key**: Your Bede secret key for API authentication
   - **Base URL**: Bede payment gateway API endpoint
   - **Debug Mode**: Enable for development/testing

### Database Tables

The module creates the following database tables:

- `bede_payments`: Stores payment transaction records
- `bede_payment_logs`: Stores detailed API request/response logs

## Usage

### Frontend Payment Processing

1. Customers can select Bede Payment Gateway during checkout
2. They will be redirected to the Bede payment interface
3. After successful payment, they return to the store with order confirmation

### Admin Payment Management

#### Accessing Payment Management

Navigate to **Admin Panel** → **Bede Payment Gateway** → **Refund Management**

#### Searching Payments

Use the search form to filter payments by:
- Order ID
- Merchant Track ID
- Transaction ID
- Date range
- Payment status
- Refund status

#### Processing Refunds

1. **Standard Refund**: Click the "Refund" button for processed payments
2. **Gateway Refund Request**: Click "Request Refund" to send refund requests to the payment gateway

### API Endpoints

The module provides the following admin endpoints:

- `/admin/bedepg/refund/index` - Refund management interface
- `/admin/bedepg/refund/search` - Payment search functionality
- `/admin/bedepg/refund/request` - Process refund requests
- `/admin/bedepg/logs/index` - View payment logs

## Requirements

- Magento 2.3 or higher
- PHP 8.2
- MySQL 8 or MariaDB 11.4
- Valid Bede payment gateway account
