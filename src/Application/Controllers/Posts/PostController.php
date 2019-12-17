<?php

namespace App\Controllers\Posts;

use App\Application\Controllers\Controller;
use App\Domain\Post\PostRepositoryInterface;
use App\Domain\Post\PostService;
use App\Domain\Post\PostValidation;
use App\Domain\User\UserService;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Handlers\Strategies\RequestHandler;
use Firebase\JWT\JWT;

class PostController extends Controller {

    protected $postService;
    protected $postValidation;
    protected $userService;

    public function __construct(LoggerInterface $logger, PostService $postService, PostValidation $postValidation, UserService $userService) {
        parent::__construct($logger);
        $this->postService = $postService;
        $this->postValidation = $postValidation;
        $this->userService = $userService;
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $post = $this->postService->findPost($id);

        // Get user information connected to post
        $user = $this->userService->findUser($post['user_id']);

        // Add user name info to post
        $postWithUser = $post;
        $postWithUser['user_name'] = $user['name'];
        return $this->respondWithJson($response, $postWithUser);
    }

    public function list(Request $request, Response $response, array $args) {
        $allPosts = $this->postService->findAllPosts();
        // Add user name info to post
        $postsWithUser = [];
        foreach ($allPosts as $post){
            // Get user information connected to post
            $user = $this->userService->findUser($post['user_id']);
            $post['user_name'] = $user['name'];
            $postsWithUser[] = $post;
        }

        return $this->respondWithJson($response, $postsWithUser);

    }
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
//        var_dump($request->getParsedBody());
    
        $data = $request->getParsedBody();

        $postData = [
            'message' => htmlspecialchars($data['message']),
            'user_id' => 1 // todo get authenticated user
        ];

        $validationResult = $this->postValidation->validatePostCreation($postData);
        if ($validationResult->fails()) {
            $responseData = [
                'success' => false,
                'validation' => $validationResult->toArray(),
            ];

            return $this->respondWithJson($response, $responseData, 422);
        }
//        var_dump($data);
        $updated = $this->postService->updatePost($id,$postData['message']);
        if ($updated) {
            return $this->respondWithJson($response, ['success' => true]);
        }
        return $this->respondWithJson($response, ['success' => false]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {

        $postId = $args['id'];


        $deleted = $this->postService->deletePost($postId);
        if ($deleted) {
            return $this->respondWithJson($response, ['success' => true]);
        }
        return $this->respondWithJson($response, ['success' => false]);
    }

    public function create(RequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        if(null !== $data) {
            $postData = [
                'message' => $data['message'],
                'user_id' => 1 // todo get authenticated user
            ];

            $validationResult = $this->postValidation->validatePostCreation($postData);
            if ($validationResult->fails()) {
                $responseData = [
                    'success' => false,
                    'validation' => $validationResult->toArray(),
                ];

                return $this->respondWithJson($response, $responseData, 422);
            }
            $insertId = $this->postService->createPost($postData);

            if (null !== $insertId) {
                return $this->respondWithJson($response, ['success' => true]);
            }
            return $this->respondWithJson($response, ['success' => false, 'message' => 'Post could not be inserted']);
        }
        return $this->respondWithJson($response, ['success' => false, 'message' => 'Request body empty']);
    }


}
