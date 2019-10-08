<?php
namespace Classes\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Application\lib\Validation;
use Application\lib\Error;
use Application\app\AccountManager;
use Application\app\TokenManager;

require_once __DIR__.'/../lib/Validation.php';
require_once __DIR__.'/../lib/Error.php';
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/AccountManager.php';
require_once __DIR__.'/../app/TokenManager.php';

class AccountController extends Controller {
    public function sign_up(Request $request, Response $response) {
        $param = array_escape($request->getParsedBody());

        $user_id = isset($param['user_id']) ? $param['user_id'] : null;
        $user_name = isset($param['user_name']) ? $param['user_name'] : null;
        $email = isset($param['email']) ? $param['email'] : null;
        $password = isset($param['password']) ? $param['password'] : null;
        $gender = isset($param['gender']) ? $param['gender'] : null;
        $birthday = isset($param['birthday']) ? $param['birthday'] : null;

        $error = [];

        if (is_null($user_id) || is_null($user_name) || is_null($email) || is_null($password) || is_null($gender) || is_null($birthday)) {
            $result = [
                'status' => 400,
                'message' => [
                    Error::$REQUIRED_PARAM
                ],
                'data' => null
            ];
        } else {
            $valid_user_id = Validation::fire($user_id, Validation::$USER_ID);
            $valid_user_name = Validation::fire($user_name, Validation::$USER_NAME);
            $valid_email = Validation::fire($email, Validation::$EMAIL);
            $valid_password = Validation::fire($password, Validation::$PASSWORD);
            $valid_gender = Validation::fire($gender, Validation::$GENDER);
            $valid_birthday = Validation::fire($birthday, Validation::$BIRTHDAY);

            if (
                !$valid_user_id ||
                !$valid_user_name ||
                !$valid_email ||
                !$valid_password ||
                !$valid_gender ||
                !$valid_birthday
            ) {
                if (!$valid_user_id) $error[] = Error::$VALIDATION_USER_ID;
                if (!$valid_user_name) $error[] = Error::$VALIDATION_USER_NAME;
                if (!$valid_email) $error[] = Error::$VALIDATION_EMAIL;
                if (!$valid_password) $error[] = Error::$VALIDATION_PASSWORD;
                if (!$valid_gender) $error[] = Error::$VALIDATION_GENDER;
                if (!$valid_birthday) $error[] = Error::$VALIDATION_BIRTHDAY;

                $result = [
                    'status' => 400,
                    'message' => $error,
                    'data' => null
                ];
            } else {
                $check_user_id = AccountManager::already_user_id($_POST['user_id']);
                $check_email = AccountManager::already_email($_POST['email']);

                if (!$check_user_id || !$check_email) {
                    if (!$check_user_id) $error[] = Error::$ALREADY_USER_ID;
                    if (!$check_email) $error[] = Error::$ALREADY_EMAIL;

                    $result = [
                        'status' => 400,
                        'message' => $error,
                        'data' => null
                    ];
                } else {
                    $id = AccountManager::sign_up($user_id, $user_name, $email, $password, $gender, $birthday);
                    $token = TokenManager::add_token($id);

                    $result = [
                        'status' => 200,
                        'message' => null,
                        'data' => [
                            'token' => $token
                        ]
                    ];
                }
            }
        }

        return $response->withJson($result);
    }

    public function sign_in(Request $request, Response $response) {
        $param = array_escape($request->getParsedBody());

        $user_id = isset($param['user_id']) ? $param['user_id'] : null;
        $password = isset($param['password']) ? $param['password'] : null;

        $error = [];

        if (is_null($user_id) || is_null($password)) {
            $result = [
                'status' => 400,
                'message' => [
                    Error::$REQUIRED_PARAM
                ],
                'data' => null
            ];
        } else {
            $valid_user_id = Validation::fire($user_id, Validation::$USER_ID_OR_EMAIL);
            $valid_password = Validation::fire($password, Validation::$PASSWORD);

            if (!$valid_user_id || !$valid_password) {
                if (!$valid_user_id) $error[] = Error::$VALIDATION_USER_ID;
                if (!$valid_password) $error[] = Error::$VALIDATION_PASSWORD;

                $result = [
                    'status' => 400,
                    'message' => $error,
                    'data' => null
                ];
            } else {
                $id = AccountManager::sign_in($user_id, $password);

                if (!$id) {
                    $result = [
                        'status' => 400,
                        'message' => [
                            Error::$UNKNOWN_USER
                        ],
                        'data' => null
                    ];
                } else {
                    $token = TokenManager::add_token($id);

                    $result = [
                        'status' => 200,
                        'message' => null,
                        'data' => [
                            'token' => $token
                        ]
                    ];
                }
            }
        }

        return $response->withJson($result);
    }

    public function password_reset(Request $request, Response $response) {
        $param = array_escape($request->getParsedBody());

        $user_id = isset($param['user_id']) ? $param['user_id'] : null;

        if (is_null($user_id)) {
            $result = [
                'status' => 400,
                'message' => [
                    Error::$REQUIRED_PARAM
                ],
                'data' => null
            ];
        } else {
            $valid_user_id = Validation::fire($user_id, Validation::$USER_ID_OR_EMAIL);

            if (!$valid_user_id) {
                if (!$valid_user_id) $error[] = Error::$VALIDATION_USER_ID;

                $result = [
                    'status' => 400,
                    'message' => [
                        Error::$VALIDATION_USER_ID
                    ],
                    'data' => null
                ];
            } else {
                $id = AccountManager::already_user_id_or_email($user_id);

                if (!$id) {
                    $result = [
                        'status' => 400,
                        'message' => [
                            Error::$UNKNOWN_USER
                        ],
                        'data' => null
                    ];
                } else {
                    $send_flg = AccountManager::password_reset($id);

                    if ($send_flg) {
                        $result = [
                            'status' => 200,
                            'message' => null,
                            'data' => null
                        ];
                    } else {
                        $result = [
                            'status' => 400,
                            'message' => [
                                Error::$MAIL_SEND
                            ],
                            'data' => null
                        ];
                    }
                }
            }
        }

        return $response->withJson($result);
    }
}