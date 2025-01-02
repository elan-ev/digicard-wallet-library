<?php

/**
 * AppleCard.php - Apple Wallet integration for Digital Student ID
 *
 * This file is part of the Digicard Wallet Library.
 *
 * @package    DigicardWalletLibrary
 * @author     Till Glöggler <gloeggler@elan-ev.de>
 * @author     Farbod Zamani <zamani@elan-ev.de>
 * @copyright  2025 ELAN e.V.
 * @license    https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DigicardWalletLibrary;

use DigicardWalletLibrary\WalletUser;
use PKPass\PKPass;

/**
 * Class AppleCard
 *
 * This class is responsible for creating and managing passes for Apple Wallet.
 */
class AppleCard
{
    /**
     * @var string The path to the Apple Wallet certificate.
     */
    private $certPath;

    /**
     * @var string The password for the Apple Wallet certificate.
     */
    private $certPassword;

    /**
     * @var string The path to the Apple Wallet WWDR certificate.
     */
    private $wwdrCertPath;

    /**
     * @var string The team identifier for the Apple Developer account.
     */
    private $teamIdentifier;

    /**
     * @var string The pass type identifier for the Apple Wallet pass.
     */
    private $passTypeIdentifier;

    /**
     * @var string The organization name for the Apple Wallet pass.
     */
    private $organizationName;

    /**
     * @var string The URL for validating the student card.
     */
    private $validationUrl;

    /**
     * @var string The MIME type for Apple Wallet passes.
     */
    const MIME_TYPE = PKPass::MIME_TYPE;

    /**
     * Constructor for the AppleCard class.
     *
     * @param string $certPath The path to the Apple Wallet certificate.
     * @param string $certPassword The password for the Apple Wallet certificate.
     * @param string $wwdrCertPath The path to the Apple Wallet WWDR certificate.
     * @param string $teamIdentifier The team identifier for the Apple Developer account.
     * @param string $passTypeIdentifier The pass type identifier for the Apple Wallet pass.
     * @param string $organizationName The organization name for the Apple Wallet pass.
     * @param string $validationUrl The URL for validating the student card.
     */
    public function __construct($certPath, $certPassword, $wwdrCertPath, $teamIdentifier, $passTypeIdentifier, $organizationName, $validationUrl)
    {
        $this->certPath = $certPath;
        $this->certPassword = $certPassword;
        $this->wwdrCertPath = $wwdrCertPath;
        $this->teamIdentifier = $teamIdentifier;
        $this->passTypeIdentifier = $passTypeIdentifier;
        $this->organizationName = $organizationName;
        $this->validationUrl = $validationUrl;
    }

    /**
     * Generates an Apple Wallet pass for a user's digital ID card.
     *
     * @param WalletUser $user The user object containing the necessary information for creating the wallet pass.
     * @return string The path to the generated .pkpass file.
     * @throws \Exception If there's an error creating the pass.
     */
    public function getWalletPass(WalletUser $user)
    {
        $pass = new PKPass();
        $pass->setCertificatePath($this->certPath);
        $pass->setCertificatePassword($this->certPassword);
        $pass->setWwdrCertificatePath($this->wwdrCertPath);

        // Setting Data.
        $this->ensureDataIsSet($pass, $user);

        // Convert user image to the files.
        try {
            $package_files = $this->generateUserImages($user->getImageUrl());
            foreach ($package_files as $filepath) {
                if (file_exists($filepath)) {
                    $filename = basename($filepath);
                    $pass->addFile($filepath, $filename);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error converting WebP to PNG: ' . $e->getMessage());
        }

        // TODO: add dynamic logo!
        // Add logo
        $logoUrl = 'https://www.uni-osnabrueck.de/favicon.ico';
        $logoContent = @file_get_contents($logoUrl);
        file_put_contents('/tmp/logo.png', $logoContent);

        $pass->addFile('/tmp/logo.png', 'logo.png');

        // TODO: Add Localization

        // Create and output the pass
        try {
            return $pass->create(false);
        } catch (\Throwable $th) {
            throw new \Exception('Error creating pass: ' . $th->getMessage());
        }

        return '';
    }

    /**
     * Sets the required data for the Apple Wallet pass.
     *
     * This method prepares and assigns the pass data structure, including
     * user information, organization details, colors, fields, and barcode.
     *
     * @param PKPass $pass (referenced) The PKPass object to set data on.
     * @param WalletUser $user The user object containing information for the pass.
     * @return void
     */
    private function ensureDataIsSet(&$pass, $user)
    {
        $generic_pass_type_attributes = [];

        // Primary Fields.
        $primary_fields = [
            [
                'key' => 'name',
                'label' => 'Name',
                'value' => $user->getName()
            ]
        ];

        $generic_pass_type_attributes['primaryFields'] = $primary_fields;

        // Secondary Fields
        $secondary_fields = [
            [
                'key' => 'matriculation',
                'label' => 'Matriculation Number',
                'value' => $user->getMatrikel()
            ]
        ];

        $generic_pass_type_attributes['secondaryFields'] = $secondary_fields;

        // Aux Fields
        $auxiliary_fields = [
            [
                'key' => 'validity',
                'label' => 'Valid',
                'value' => $user->getSemesterStart() . ' - ' . $user->getSemesterEnd()
            ]
        ];

        $generic_pass_type_attributes['auxiliaryFields'] = $auxiliary_fields;

        // Back Fileds
        $back_fields = [];

        if ($courses = $user->getStudycourses()) {
            foreach ($courses as $index => $course) {
                $back_field_object = [
                    'key' => 'courses-' . $index,
                    'label' => 'Study Course',
                    'value' => $course['name'] . ' - Fachsemester:' . $course['semester']
                ];
                $back_fields[] = $back_field_object;
            }
        }

        $generic_pass_type_attributes['backFields'] = $back_fields;

        // Gathering data
        $data = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $this->passTypeIdentifier,
            'serialNumber' => $user->getId(),
            'teamIdentifier' => $this->teamIdentifier,
            'organizationName' => $this->organizationName,
            'description' => 'Student ID for ' . $user->getName(),
            'logoText' => $user->getInstitution(),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(66, 133, 244)',
            'barcodes' => [
                [
                    'message' => $this->getValidationUrl($user),
                    'format' => 'PKBarcodeFormatQR',
                    'messageEncoding' => 'iso-8859-1'
                ]
            ]
        ];

        // Attaching generic pass type.
        $data['generic'] = $generic_pass_type_attributes;

        $pass->setData($data);
    }

    /**
     * Generates PNG images for the user's profile picture in various sizes required by Apple Wallet.
     *
     * Converts a WebP image from the given URL to PNG format and resizes it to create
     * icon, icon@2x, thumbnail, and thumbnail@2x images. The images are saved in /tmp/.
     *
     * @param string $image_url The URL of the user's WebP profile image.
     * @return array An array of file paths to the generated PNG images.
     * @throws \Exception If WebP support is unavailable or image conversion fails.
     */
    private function generateUserImages($image_url)
    {
        if (!function_exists('imagecreatefromwebp')) {
            throw new \Exception('WebP support is not available in your PHP installation.');
        }

        $image = imagecreatefromwebp($image_url);
        if ($image === false) {
            throw new \Exception('Failed to create image from WebP data.');
        }

        $base_width = 116;
        $base_height = 116;

        $image_dir_path = '/tmp/';
        $original_png = $image_dir_path . 'original.png';

        $icon_png = $image_dir_path . 'icon.png';
        $icon_width = $base_width;
        $icon_height = $base_height;

        $thumbnail_png = $image_dir_path . 'thumbnail.png';
        $thumbnail_width = $base_width;
        $thumbnail_height = $base_height;

        $icon2x_png = $image_dir_path . 'icon@2x.png';
        $icon2x_width = $base_width * 2;
        $icon2x_height = $base_height * 2;

        $thumbnail2x_png = $image_dir_path . 'thumbnail@2x.png';
        $thumbnail2x_width = $base_width * 2;
        $thumbnail2x_height = $base_height * 2;

        // Save original.
        if (file_exists($original_png)) {
            unlink($original_png);
        }
        imagepng($image, $original_png);
        imagedestroy($image);

        // Create icon.
        $this->pngResizer($original_png, $icon_png, $icon_width, $icon_height);

        // Create thumbnail
        $this->pngResizer($original_png, $thumbnail_png, $thumbnail_width, $thumbnail_height);

        // Create icon 2x
        $this->pngResizer($original_png, $icon2x_png, $icon2x_width, $icon2x_height);

        // Create thumbnail 2x
        $this->pngResizer($original_png, $thumbnail2x_png, $thumbnail2x_width, $thumbnail2x_height);

        return [$icon_png, $icon2x_png, $thumbnail_png, $thumbnail2x_png];
    }


    /**
     * Resizes a PNG image to the specified width and height and saves it to a new path.
     *
     * Loads the original PNG image, resizes it using resampling, and writes the result
     * to the specified file path. Overwrites the file if it already exists.
     *
     * @param string $original_path The path to the original PNG image.
     * @param string $new_path The path to save the resized PNG image.
     * @param int $width The target width of the resized image.
     * @param int $height The target height of the resized image.
     * @return void
     */
    private function pngResizer($original_path, $new_path, $width, $height)
    {
        $img = imagecreatefrompng($original_path);
        list($original_width, $original_height) = getimagesize($original_path);
        $tmp = imagecreatetruecolor($width, $height);

        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

        if (file_exists($new_path)) {
            unlink($new_path);
        }
        imagepng($tmp, $new_path);
    }

    /**
     * Generates a validation URL for the given user.
     *
     * @param WalletUser $user The user object for which to generate the validation URL.
     * @return string The generated validation URL.
     */
    public function getValidationUrl(WalletUser $user)
    {
        return sprintf($this->validationUrl, $user->getId());
    }

    /**
     * Updates an existing wallet pass for a user.
     *
     * @param WalletUser $user The user object containing the updated information.
     * @return string The path to the updated .pkpass file.
     * @throws \Exception If there's an error updating the pass.
     */
    public function updateWallet(WalletUser $user)
    {
        // For Apple Wallet, updating a pass is essentially creating a new one with the same pass type identifier and serial number
        return $this->getWalletPass($user);
    }

    /**
     * Expires a user's wallet pass in Apple Wallet.
     *
     * @param WalletUser $user The user object whose wallet is to be expired.
     * @return bool True if the pass was successfully expired, false otherwise.
     */
    public function expireWallet(WalletUser $user)
    {
        // In Apple Wallet, passes are typically expired by pushing an update to the pass
        // with an expiration date in the past. However, this requires a server to push updates.
        // For this implementation, we'll return true to indicate the operation was successful,
        // but in a real-world scenario, you would need to implement a push notification service.
        return true;
    }
}
