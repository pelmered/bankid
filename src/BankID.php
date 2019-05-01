<?php

namespace Modules\BankID;

use App\Models\User;
use App\Traits\BankIDSignable;
use Modules\BankID\BankidToken;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Request;

class BankID
{
    const ENVIRONMENT_PRODUCTION = 'prod';
    const ENVIRONMENT_TEST       = 'test';

    const HOSTS = [
        self::ENVIRONMENT_PRODUCTION => 'appapi2.bankid.com',
        self::ENVIRONMENT_TEST => 'appapi2.test.bankid.com',
    ];

    const INVALID_PARAMETERS      = "INVALID_PARAMETERS";
    const ALREADY_IN_PROGRESS     = "ALREADY_IN_PROGRESS";
    const INTERNAL_ERROR          = "INTERNAL_ERROR";
    const OUTSTANDING_TRANSACTION = "OUTSTANDING_TRANSACTION";
    const NO_CLIENT               = "NO_CLIENT";
    const STARTED                 = "STARTED";
    const USER_SIGN               = "USER_SIGN";
    const COMPLETE                = "COMPLETE";
    const USER_CANCEL             = "USER_CANCEL";
    const CANCEL                  = "CANCELLED";
    const EXPIRED_TRANSACTION     = "EXPIRED_TRANSACTION";

    private $httpClient;

    /**
     * BankID constructor.
     *
     * @param string $environment
     * @param string $certificate
     * @param string $rootCertificate
     */
    public function __construct($environment = null)
    {
        if (!$environment) {
            $environment = config('bankid.environment');
        }

        if ($environment === self::ENVIRONMENT_PRODUCTION) {
            # set real prod certs. Now it's only a copy of test certs
            $certificate     = storage_path('certificates/bankid/prod/prod.pem');
            $rootCertificate = storage_path('certificates/bankid/prod/prod_cacert.pem');
        } else {
            $environment     = self::ENVIRONMENT_TEST;
            $certificate     = __DIR__.'/../certificates/test.pem';
            $rootCertificate = __DIR__.'/../certificates/test_cacert.pem';
        }

        $httpOptions = [
            'base_uri' => 'https://'.self::HOSTS[$environment].'/rp/v5/',
            'cert' => [$certificate, 'EcDqSkmHiFfS4hRra2'],
            'verify' => $rootCertificate,
            //'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        $this->httpClient = new Client($httpOptions);
    }

    public function isTestAccount(User $user) : bool
    {
        $testAccounts = config('bankid.test_logins');

        return !empty($testAccounts[$user->uuid]);
    }

    /**
     * @param $password
     *
     * @return bool|User
     */
    public function checkTestAccounts($password, User $user = null)
    {
        $testAccounts = config('bankid.test_logins');

        if ($user) {
            return (!empty($testAccounts[$user->uuid]) && $testAccounts[$user->uuid] === $password);
        }

        $fakeUserUuid = array_search($password, $testAccounts);

        if ($fakeUserUuid && $fakeUser = User::find($fakeUserUuid)) {
            return $fakeUser;
        }

        return false;
    }

    /**
     * Authenticate a user using their personal number.
     *
     * @param $personalNumber
     *
     * @param $ip
     *
     * @return BankIDResponse
     */
    public function authenticate($personalNumber, bool $allowFingerprint = true)
    {
        try {
            $httpResponse = $this->httpClient->post('auth', [
                RequestOptions::JSON => [
                    'personalNumber' => $personalNumber,
                    'endUserIp' => Request::ip(),
                    'requirement' => [
                        'allowFingerprint' => $allowFingerprint
                    ]
                ],
            ]);
        } catch (RequestException $e) {
            return self::requestExceptionToBankIDResponse($e);
        }

        $httpResponseBody = json_decode($httpResponse->getBody(), true);

        return new BankIDResponse(BankIDResponse::STATUS_PENDING, $httpResponseBody);
    }

    /**
     * Sign a user using their personal number.
     *
     * @param $personalNumber
     *
     * @param $ip
     *
     * @return BankIDResponse
     */
    public function sign($personalNumber, string $userVisibleData, bool $allowFingerprint = true)
    {
        try {
            $httpResponse = $this->httpClient->post('sign', [
                RequestOptions::JSON => [
                    'personalNumber'    => $personalNumber,
                    'endUserIp'         => Request::ip(),
                    'userVisibleData'   => base64_encode($userVisibleData),
                    //'userNonVisibleData' => base64_encode($userVisibleData),
                    'requirement'       => [
                        'allowFingerprint' => $allowFingerprint
                    ],
                ],
            ]);
        } catch (RequestException $e) {
            return self::requestExceptionToBankIDResponse($e);
        }

        $httpResponseBody = json_decode($httpResponse->getBody(), true);

        return new BankIDResponse(BankIDResponse::STATUS_PENDING, $httpResponseBody);
    }

    public function createSignatureToken(string $orderRef, array $body, $signable = null)
    {
        if (empty($body['completionData']) || empty($body['completionData']['user'])) {
            throw new \UnexpectedValueException('Unexpected response from BankID');
        }

        $token                 = new BankidToken();
        $token->order_ref      = $orderRef;
        $token->action         = 'sign';
        $token->signed_by_pnr  = $body['completionData']['user']['personalNumber'];
        $token->signed_by_name = $body['completionData']['user']['name'];

        if ($signable) {
            $signable->signatures()->save($token);
        } else {
            $token->save();
        }

        return $token;
    }

    /**
     * Collect an ongoing user request.
     *
     * @param $orderReference
     *
     * @return BankIDResponse
     */
    public function collect($orderReference)
    {
        try {
            $httpResponse = $this->httpClient->post('collect', [
                RequestOptions::JSON => [
                    'orderRef' => $orderReference,
                ],
            ]);
        } catch (RequestException $e) {
            return self::requestExceptionToBankIDResponse($e);
        }

        $httpResponseBody = json_decode($httpResponse->getBody(), true);

        return new BankIDResponse($httpResponseBody['status'], $httpResponseBody);
    }

    /**
     * Cancel an ongoing order per the users request.
     *
     * @param $orderReference
     *
     * @return BankIDResponse
     */
    public function cancel($orderReference)
    {
        try {
            $httpResponse = $this->httpClient->post('cancel', [
                RequestOptions::JSON => [
                    'orderRef' => $orderReference,
                ],
            ]);
        } catch (RequestException $e) {
            return self::requestExceptionToBankIDResponse($e);
        }


        $httpResponseBody = json_decode($httpResponse->getBody(), true);

        return new BankIDResponse(BankIDResponse::STATUS_OK, $httpResponseBody);
    }

    /**
     * Transform GuzzleHttp request exception into a BankIDResponse.
     *
     * @param RequestException $e
     *
     * @return BankIDResponse
     */
    private static function requestExceptionToBankIDResponse(RequestException $e)
    {
        $httpResponseBody = json_decode($e->getResponse()->getBody(), true);

        return new BankIDResponse(BankIDResponse::STATUS_FAILED, $httpResponseBody);
    }
}
