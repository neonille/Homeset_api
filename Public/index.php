<?php

//Catch warnings as errors
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
});

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/../Services/DB.php';
require  __DIR__ . '/../Services/JWTGEN.php';
require  __DIR__ . '/../Models/CasePostModel.php';

$app = AppFactory::create();
$app->setBasePath('/homeset/api');
$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secret" => getenv("secret"),
    "ignore" => ["/homeset/api/login"],
    "before" => function ($request, $arguments) {
        $token = $request->getAttribute("token");
        return $request->withAttribute("id", $token['id']);
    }
]));
$app->add(
    new \Slim\Middleware\Session([
      'name' => 'session',
      'autorefresh' => true,
      'lifetime' => '1 hour',
    ])
  );

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// CORS

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:4200')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Routes

$app->post('/login', function (Request $request, Response $response, $args) {
    $session = new \SlimSession\Helper();
    DB::LOAD_DATA(__DIR__ . '/../Data/users.json', "users",$session);
    DB::LOAD_DATA(__DIR__ . '/../Data/cases.json', "cases", $session);
    DB::LOAD_DATA(__DIR__ . '/../Data/complex.json', "complex", $session);
    $postArr  = $request->getParsedBody();
    if (!isset($postArr["firstname"]) || !isset($postArr["password"])){
        $response->getBody()->write("MISSING_LOGIN_CREDENTIALS");
        return $response->withStatus(400);
    }
    $id = DB::GET_USER_ID(strtolower($postArr["firstname"]), $session);
    if(is_null($id)){
        $response->getBody()->write("USER_NOT_FOUND");
        return $response->withStatus(400);
    }  else {
        $token = JWTGEN::GENERATE($id);
        $response->getBody()->write($token);
        return $response->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/me', function (Request $request, Response $response, array $args) {
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');

    $resp = json_encode(DB::GET_USER($id, $session));
    $response->getBody()->write($resp);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/cases', function (Request $request, Response $response, array $args) {
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');
    $user = DB::GET_USER($id, $session);

    $resp = json_encode(DB::GET_CASES($user, $session));
    $response->getBody()->write($resp);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/case/{caseId}', function (Request $request, Response $response, array $args) {
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');
    $user = DB::GET_USER($id, $session);

    $case = DB::GET_CASE($args['caseId'], $session);
    if($case->issuer != $id){
        $response->getBody()->write("You are not the issuer of this case");
        return $response->withStatus(400);
    }
    DB::DELETE_CASE($args['caseId'], $session);
    return $response->withStatus(200);
});


$app->get('/case/{caseId}', function (Request $request, Response $response, array $args) {
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');
    $user = DB::GET_USER($id,$session);
    $case = DB::GET_CASE($args['caseId'], $session);
    if($case->issuer != $user->id){
        $response->getBody()->write("NOT_ALLOWED_TO_SEE_CASE");
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    } else {
        $response->getBody()->write(json_encode($case));
        return $response->withHeader('Content-Type', 'application/json');
    }
});

$app->patch('/me', function(Request $request, Response $response, array $args){
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');
    $user = DB::GET_USER($id,$session);

    $userUpdates  = $request->getParsedBody();
    DB::UPDATE_USER($id,$userUpdates, $session);
    return $response->withStatus(200);
});

$app->post('/case',function(Request $request, Response $response, array $args){
    $session = new \SlimSession\Helper();
    $id = $request->getAttribute('id');
    $user = DB::GET_USER($id,$session);
    $newCase  = $request->getParsedBody();
    try {
        $case = new CasePostModel($newCase);
    } catch (Exception $th) {
        $response->getBody()->write("NOT_PROPER_MODEL");
        return $response->withStatus(401);
    }
    DB::INSERT_NEW_CASE($case, $user->id, $session);
    $response->getBody()->write(json_encode($case));
        return $response->withHeader('Content-Type', 'application/json');

});






$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});


$app->run();