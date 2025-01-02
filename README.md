# Digicard Wallet Library

A PHP library for integrating digital student IDs with Google Wallet and Apple Wallet.

## Description

The Digicard Wallet Library provides a simple and unified interface for creating and managing digital student ID cards in both Google Wallet and Apple Wallet. This library handles the complexity of working with both wallet platforms, allowing educational institutions to easily provide their students with digital ID cards.

## Features

- **Google Wallet Integration**: Create, update, and expire digital student IDs in Google Wallet
- **Apple Wallet Integration**: Generate PKPass files for Apple Wallet
- **Unified User Model**: Simple `WalletUser` class to represent student information
- **Customizable**: Support for multiple study courses, semester information, and institution branding

## Installation

Install via Composer:

```bash
composer require elan-ev/digicard-wallet-library
```

## Usage

### Google Wallet

```php
use DigicardWalletLibrary\GoogleCard;
use DigicardWalletLibrary\WalletUser;

// Initialize Google Wallet integration
$googleCard = new GoogleCard(
    $classSuffix,
    $issuerId,
    $serviceAccountConfig,
    $validationUrl
);

// Create a wallet user
$user = new WalletUser(/* user data */);

// Generate wallet link
$walletLink = $googleCard->getWalletLink($user);

// Update existing wallet
$googleCard->updateWallet($user);

// Expire wallet
$googleCard->expireWallet($user);
```

### Apple Wallet

```php
use DigicardWalletLibrary\AppleCard;
use DigicardWalletLibrary\WalletUser;

// Initialize Apple Wallet integration
$appleCard = new AppleCard(
    $certPath,
    $certPassword,
    $wwdrCertPath,
    $teamIdentifier,
    $passTypeIdentifier,
    $organizationName,
    $validationUrl
);

// Create a wallet user
$user = new WalletUser(/* user data */);

// Generate PKPass file
$pkpassData = $appleCard->getWalletPass($user);

// Update existing pass
$updatedPass = $appleCard->updateWallet($user);

// Expire pass
$appleCard->expireWallet($user);
```

## Requirements

- PHP 8.1 or higher
- GD extension with WebP support (for Apple Wallet image processing)
- Valid Google Wallet API credentials (for Google Wallet)
- Valid Apple Developer certificates (for Apple Wallet)

## License

![AGPL v3](https://www.gnu.org/graphics/agplv3-155x51.png)

This project is licensed under the GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later).

See the [LICENSE](LICENSE) file for details or visit [https://www.gnu.org/licenses/agpl-3.0.html](https://www.gnu.org/licenses/agpl-3.0.html)

## Authors

- **Till Glöggler** - [gloeggler@elan-ev.de](mailto:gloeggler@elan-ev.de)
- **Farbod Zamani** - [zamani@elan-ev.de](mailto:zamani@elan-ev.de)

## About ELAN e.V.

This library is developed and maintained by [elan e.V.](https://elan-ev.de) - a non-profit organization dedicated to advancing digital education and e-learning solutions.

## Copyright

Copyright © 2025 ELAN e.V.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

## Support

For issues, questions, or contributions, please contact elan e.V. or visit [https://elan-ev.de](https://elan-ev.de)