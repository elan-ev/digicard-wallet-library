<?php

namespace DigicardWalletLibrary;

use DigicardWalletLibrary\WalletUser;

use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client as GoogleClient;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\GenericObject;
use Google\Service\Walletobjects\GenericClass;
use Google\Service\Walletobjects\Barcode;
use Google\Service\Walletobjects\ImageModuleData;
use Google\Service\Walletobjects\LinksModuleData;
use Google\Service\Walletobjects\TextModuleData;
use Google\Service\Walletobjects\TranslatedString;
use Google\Service\Walletobjects\LocalizedString;
use Google\Service\Walletobjects\ImageUri;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\Uri;
use Google\Service\Exception as GoogleException;

/**
 * Class DigitalIdCard
 *
 * This class is responsible for creating a link to add a student card entry to Google Wallet.
 */
class GoogleCard
{
    /**
     * @var string The suffix for the class ID in Google Wallet.
     */
    private $classSuffix;

    /**
     * @var string The issuer ID for the Google Wallet object.
     */
    private $issuerId;

    /**
     * @var array The configuration array for the service account.
     */
    private $config;

    /**
     * @var string The URL for validating the student card.
     */

    /**
     * @var string The URL for validating the student card.
     */
    private $validationUrl;

    /**
     * @var GoogleClient The Google API Client instance.
     *
     * This property holds the authenticated Google API Client used for making
     * requests to Google services. It is initialized in the auth() method.
     */
    private $client;

    /**
     * @var Walletobjects The Google Wallet API service instance.
     *
     * This property holds the Walletobjects service instance, which provides
     * methods for interacting with the Google Wallet API. It is initialized
     * in the auth() method using the authenticated $client.
     */
    private $service;

    /**
     * Constructor for the DigitalIdCard class.
     *
     * @param string $classSuffix The suffix for the class ID in Google Wallet.
     * @param string $issuerId The issuer ID for the Google Wallet object.
     * @param array $config The configuration array for the service account.
     * @param string $validationUrl The URL for validating the student card.
     */
    public function __construct($classSuffix, $issuerId, array $config, $validationUrl)
    {
        $this->classSuffix    = $classSuffix;
        $this->issuerId       = $issuerId;
        $this->config         = $config;
        $this->validationUrl  = $validationUrl;

        $this->auth();
    }

    /**
     * Authenticates and initializes the Google Wallet API service.
     *
     * This method sets up the necessary credentials and client configuration
     * to interact with the Google Wallet API. It initializes the ServiceAccountCredentials,
     * configures the Google Client with the appropriate scopes and auth config,
     * and creates a new Walletobjects service instance.
     *
     * @return void
     */
    protected function auth()
    {
        $this->credentials = new ServiceAccountCredentials(
            Walletobjects::WALLET_OBJECT_ISSUER,
            $this->config
        );

        // Initialize Google Wallet API service
        $this->client = new GoogleClient();
        $this->client->setApplicationName('APPLICATION_NAME');
        $this->client->setScopes(Walletobjects::WALLET_OBJECT_ISSUER);
        $this->client->setAuthConfig($this->config);

        $this->service = new Walletobjects($this->client);
    }

    /**
     * Generates a Google Wallet link for a user's digital ID card.
     *
     * This function creates or updates a Google Wallet object for the user,
     * generates a JWT (JSON Web Token) with the necessary claims,
     * and returns a URL that can be used to save the digital ID card to Google Wallet.
     *
     * @param WalletUser $user The user object containing the necessary information for creating the wallet object.
     *
     * @return string A URL that can be used to save the digital ID card to Google Wallet.
     *
     * @throws GoogleException If there's an error communicating with the Google Wallet API.
     */
    public function getWalletLink(WalletUser $user)
    {
        $object_id = "{$this->issuerId}.{$user->getId()}";
        $object    = $this->getObject($user);

        try {
            $this->service->genericobject->get($object_id);
            $this->service->genericobject->update($object_id, $object);
        } catch (GoogleException $ex) {
            $this->service->genericobject->insert($object);
        }

        // Create the JWT as an array of key/value pairs
        $claims = [
            'iss' => $this->config['client_email'],
            'aud' => 'google',
            'origins' => ['elan-ev.de'],
            'typ' => 'savetowallet',
            'payload' => [
                'genericObjects' => [
                    $object
                ]
            ]
        ];

        $token = JWT::encode(
            $claims,
            $this->config['private_key'],
            'RS256'
        );

        return "https://pay.google.com/gp/v/save/{$token}";
    }

    /**
     * Updates an existing wallet object for a user.
     *
     * This method attempts to update an existing Google Wallet object for the given user.
     * If the object doesn't exist, it throws a NotFoundException.
     *
     * @param WalletUser $user The user object containing the necessary information for the wallet update.
     * @throws \NotFoundException If the wallet object for the user doesn't exist.
     * @throws GoogleException If there's an error communicating with the Google Wallet API.
     * @return void
     */
    public function updateWallet(WalletUser $user)
    {
        $object_id = "{$this->issuerId}.{$user->getId()}";
        $object    = $this->getObject($user);

        try {
            $this->service->genericobject->get($object_id);
            $response = $this->service->genericobject->update($object_id, $object);
        } catch (GoogleException $ex) {
            throw new \NotFoundException();
        }
    }

    /**
     * Creates a GenericObject for Google Wallet based on user data.
     *
     * This method constructs a GenericObject with various properties required for
     * a digital student ID card in Google Wallet. It includes information such as
     * the student's name, image, matriculation number, study course, semester details,
     * and a QR code for validation.
     *
     * @param WalletUser $user The user object containing the student's information.
     * @return GenericObject A fully constructed GenericObject ready for use with Google Wallet API.
     */
    private function getObject(WalletUser $user)
    {
        $studycourses = [];

        foreach ($user->getStudycourses() as $course) {
            $studycourses[] = new TextModuleData([
                'header' => $course['name'],
                'body' => 'Fachsemester: ' . $course['semester'],
                'id' => 'TEXT_MODULE_ID'
            ]);
        }

        return new GenericObject([
            'id' => "{$this->issuerId}.{$user->getId()}",
            'classId' => "{$this->issuerId}.{$this->classSuffix}",
            'state' => 'ACTIVE',
            'heroImage' => new Image([
                'sourceUri' => new ImageUri([
                    'uri' => $user->getImageUrl()
                ]),
                'contentDescription' => new LocalizedString([
                    'defaultValue' => new TranslatedString([
                        'language' => 'de-DE',
                        'value' => $user->getName()
                    ])
                ])
            ]),
            'textModulesData' => [
                new TextModuleData([
                    'header' => 'Matrikelnummer',
                    'body' => $user->getMatrikel(),
                    'id' => 'TEXT_MODULE_ID'
                ]),

                ...$studycourses,

                new TextModuleData([
                    'header' => 'Semesterzeitraum / Gültigkeit',
                    'body' => $user->getSemesterStart() .' - '. $user->getSemesterEnd(),
                    'id' => 'TEXT_MODULE_ID'
                ])
            ],
            'imageModulesData' => [
                new ImageModuleData([
                    'mainImage' => new Image([
                        'sourceUri' => new ImageUri([
                            'uri' => $user->getImageUrl()
                        ]),
                        'contentDescription' => new LocalizedString([
                            'defaultValue' => new TranslatedString([
                                'language' => 'de-DE',
                                'value' => $user->getName()
                            ])
                        ])
                    ]),
                    'id' => 'IMAGE_MODULE_ID'
                ])
            ],
            'barcode' => new Barcode([
                'type' => 'QR_CODE',
                'value' => $this->getValidationUrl($user)
            ]),
            'cardTitle' => new LocalizedString([
                'defaultValue' => new TranslatedString([
                    'language' => 'en-US',
                    'value' => 'Studierendenausweis '. $user->getInstitution()
                ])
            ]),
            'header' => new LocalizedString([
                'defaultValue' => new TranslatedString([
                    'language' => 'en-US',
                    'value' => $user->getName()
                ])
            ]),
            'hexBackgroundColor' => '#4285f4',
            'logo' => new Image([
                'sourceUri' => new ImageUri([
                    'uri' => 'https://www.uni-osnabrueck.de/favicon.ico'
                ]),
                'contentDescription' => new LocalizedString([
                    'defaultValue' => new TranslatedString([
                        'language' => 'en-US',
                        'value' => $user->getInstitution()
                    ])
                ])
            ])
        ]);
    }

    /**
     * Generates a validation URL for the given user.
     *
     * This method creates a URL that can be used to validate the user's digital ID card.
     * It uses the validation URL template stored in the class and replaces a placeholder
     * with the user's ID.
     *
     * @param WalletUser $user The user object for which to generate the validation URL.
     * @return string The generated validation URL.
     */
    public function getValidationUrl(WalletUser $user)
    {
        return sprintf($this->validationUrl, $user->getId());
    }


    /**
     * Expires a user's wallet object in Google Wallet.
     *
     * This function attempts to expire a user's wallet object by setting its state to 'EXPIRED'.
     * It first checks if the object exists, and if so, updates its state.
     *
     * @param WalletUser $user The user object whose wallet is to be expired.
     * @throws \NotFoundException If the wallet object for the user doesn't exist.
     * @throws Google\Service\Exception If there's an error communicating with the Google Wallet API.
     * @return string The ID of the expired wallet object.
     */
    public function expireWallet(WalletUser $user)
    {
        $object_id = "{$this->issuerId}.{$user->getId()}";

        // Check if the object exists
        try {
            $this->service->genericobject->get($object_id);
        } catch (Google\Service\Exception $ex) {
            throw new \NotFoundException();
        }

        // Patch the object, setting the pass as expired
        $patchBody = new GenericObject([
            'state' => 'EXPIRED'
        ]);

        $response = $this->service->genericobject->patch($object_id, $patchBody);

        return $response->id;
    }
}
