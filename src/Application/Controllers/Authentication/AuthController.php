<?php

namespace App\Application\Controllers\Authentication;


use App\Application\Controllers\Controller;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\JwtService;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\User\User;
use App\Domain\User\UserService;
use App\Domain\User\UserValidation;
use App\Domain\Utility\ArrayReader;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;

use const http\Client\Curl\Features\LARGEFILE;

/**
 * Class AuthController
 */
class AuthController extends Controller
{
    protected UserService $userService;
    protected JwtService $jwtService;
    protected AuthService $authService;

    public function __construct(LoggerInterface $logger, UserService $userService,
        JwtService $jwtService, AuthService $authService)
    {
        parent::__construct($logger);
        $this->userService = $userService;
        $this->jwtService = $jwtService;
        $this->authService = $authService;
    }

    public function register(Request $request, Response $response): Response
    {
        // If a html form name changes, these changes have to be done in the Entities constructor
        // too since these names will be the keys from the ArrayReader
        $userData = $request->getParsedBody();

        // Use Entity instead of DTO for simplicity https://github.com/samuelgfeller/slim-api-example/issues/2#issuecomment-597245455
        $user = new User(new ArrayReader($userData));
        // Password gets hashed in service createUser($user) but is needed plain to build up login request body
        $plainPass = $user->getPassword();
        try {
            $insertId = $this->userService->createUser($user);
        } catch (ValidationException $exception) {
            return $this->respondValidationError($exception->getValidationResult(), $response);
        }

        // Log user in
        if (null !== $insertId) {
            $this->logger->info('User "' . $userData['email'] . '" created');

            // Add email and password like it is expected in the login function
            $request = $request->withParsedBody(['email' => $userData['email'], 'password' => $plainPass]);
            // Call login function to authenticate the user
            // todo check if that is good practice or bad
            $loginResponse = $this->login($request, $response);

            $loginContent = json_decode($loginResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);

            // Clear response body after body content is saved
            $response = new \Slim\Psr7\Response();

            $responseContent = $loginContent;

            // maybe there is already a message so it has to be transformed as array
            $responseContent['message'] = [$loginContent['message']];
            $responseContent['message'][] = 'User created and logged in';

            return $this->respondWithJson($response, $responseContent);
        }
        return $this->respondWithJson($response, ['status' => 'error', 'message' => 'User could not be registered']);
    }

    public function login(Request $request, Response $response): Response
    {
        // todo add check if already logged in

        $userData = $request->getParsedBody();

        $user = new User(new ArrayReader($userData));

//        echo $userData['wrong']; // notice
//        $variable->method(); // Error
        try {
            // Throws error if not
            $this->authService->GetUserIdIfAllowedToLogin($user);

            $token = $this->jwtService->createToken(['uid' => $user->getEmail()]);

            $lifetime = $this->jwtService->getLifetime();

            // Transform the result into a OAuh 2.0 Access Token Response
            // https://www.oauth.com/oauth2-servers/access-tokens/access-token-response/
            $result = [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $lifetime,
            ];

            $this->logger->info('Successful login from user "' . $user->getEmail() . '"');
            return $this->respondWithJson(
                $response,
                ['token' => json_encode($result), 'status' => 'success', 'message' => 'Logged in'],
                201
            );
        } catch (ValidationException $exception) {
            // Validation error is logged in AppValidation.php
            return $this->respondValidationError($exception->getValidationResult(), $response);
        } catch (InvalidCredentialsException $e) {
            // Log error
            $this->logger->notice('InvalidCredentialsException thrown with message: "'.$e->getMessage().'" user "'. $e->getUserEmail().'"');

            // Respond to client
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            return $this->respondWithJson($response, $responseData, 401);
        }
    }
}
