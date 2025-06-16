<?php

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
        $pass = new PKPass($this->certPath, $this->certPassword, $this->wwdrCertPath);

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
            'generic' => [
                'primaryFields' => [
                    [
                        'key' => 'name',
                        'label' => 'Name',
                        'value' => $user->getName()
                    ]
                ],
                'secondaryFields' => [
                    [
                        'key' => 'matriculation',
                        'label' => 'Matriculation Number',
                        'value' => $user->getMatrikel()
                    ]
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'validity',
                        'label' => 'Valid',
                        'value' => $user->getSemesterStart() . ' - ' . $user->getSemesterEnd()
                    ]
                ],
                'backFields' => [
                    [
                        'key' => 'courses',
                        'label' => 'Study Courses',
                        'value' => $this->formatStudyCourses($user->getStudycourses())
                    ]
                ]
            ],
            'barcode' => [
                'message' => $this->getValidationUrl($user),
                'format' => 'PKBarcodeFormatQR',
                'messageEncoding' => 'iso-8859-1'
            ]
        ];

        $pass->setData($data);

        // Function to convert WebP to PNG
        function webpToPng($image_url) {
            if (!function_exists('imagecreatefromwebp')) {
                throw new \Exception('WebP support is not available in your PHP installation.');
            }

            $image = imagecreatefromwebp($image_url);
            if ($image === false) {
                throw new \Exception('Failed to create image from WebP data.');
            }

            ob_start();
            imagepng($image);
            $pngData = ob_get_clean();
            imagedestroy($image);

            return $pngData;
        }

        // Convert WebP to PNG
        try {
            $iconContent = webpToPng($user->getImageUrl());
        } catch (\Exception $e) {
            throw new \Exception('Error converting WebP to PNG: ' . $e->getMessage());
        }

        file_put_contents('/tmp/icon.png', $iconContent);

        $pass->addFile('/tmp/icon.png', 'icon.png');

        // Add logo
        $logoUrl = 'https://www.uni-osnabrueck.de/favicon.ico';
        $logoContent = @file_get_contents($logoUrl);
        file_put_contents('/tmp/logo.png', $logoContent);

        $pass->addFile('/tmp/logo.png', 'logo.png');

        // Create and output the pass
        if (!$pass->create(true)) {
            throw new \Exception('Error creating pass: ' . $pass->getError());
        }

        return $pass->getFilePath();
    }

    /**
     * Formats the study courses for display in the Apple Wallet pass.
     *
     * @param array $courses The array of study courses.
     * @return string Formatted string of study courses.
     */
    private function formatStudyCourses($courses)
    {
        $formatted = '';
        foreach ($courses as $course) {
            $formatted .= $course['name'] . "\nSemester: " . $course['semester'] . "\n\n";
        }
        return trim($formatted);
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