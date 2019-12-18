<?php
namespace App\Application\Middleware;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class TimeLoggingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $bodyAsArr = json_decode((string) $response->getBody(),true);
        $GLOBALS['time']['untilAfterRun'] = round(microtime(true) - $GLOBALS['time']['start'],5);
        
        $bodyAsArr['time'] = $GLOBALS['time'];
    
        $response = new Response();
        $response->getBody()->write(json_encode($bodyAsArr,JSON_PRETTY_PRINT));


//        var_dump($GLOBALS['time']);
        
//        $request = $request->withParsedBody($GLOBALS['time']);
    
        return $response;
    }
}
