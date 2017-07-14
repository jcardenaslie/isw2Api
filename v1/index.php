
<?php
 
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;
 
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}


/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();
 
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user = $db->getUserId($api_key);
            if ($user != NULL)
                $user_id = $user["id"];
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        $response['Content-Type'] = 'application/json';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function authenticateUser(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();
 
        // get the api key
        $userid = $headers['Authorization'];

        global $user_id;
        // get user primary key id
        $user = $db->getUserId($userid);
        if ($user != NULL){
            $user_id = $user["id"];
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}
/**
 * User Registration
 * url - /register
 * method - POST
 * params - user, nombre, pass, correo
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('user', 'name', 'pass', 'email'));
 
            $response = array();
 
            // reading post params
            $user = $app->request->post('user');
            $name = $app->request->post('name');
            $password = $app->request->post('pass');
            $email = $app->request->post('email');

            // validating email address
            validateEmail($email);
 
            $db = new DbHandler();
            $res = $db->createUser($user, $name, $password, $email);
 
            /////////////////
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                echoRespnse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoRespnse(200, $response);
            }
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'pass'));
 
            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('pass');
            $response = array();
 
            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);
 
                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['nombre'];
                    $response['email'] = $user['correo'];
                    //$response['apiKey'] = $user['api_key'];
                    //$response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }
 
            echoRespnse(200, $response);
        });

/**
 * Mostar solo un lugar turistico
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/lugar_turistico/:name', function($lt_name) {
            //global $user_id;
            $response = array();
            $db = new DbHandler();
            // fetch task
            $result = $db->getLT($lt_name);
 
            if ($result != NULL) {
                $response["error"] = false;
                $response["id_lugar"] = $result["id_lugar"];
                $response["nombre"] = $result["nombre"];
                $response["comuna"] = $result["comuna"];
                //$response["descripcion"] = $result["descripcion"];
                $response["selloQ"] = $result["selloQ"];
                $response["rut_empresario"] = $result["rut_empresario"];
                $response["lat"] = $result["lat"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Listing all lugares turisticos
 * method GET
 * url /tasks          
 */
$app->get('/lugares_turisticos', function() {
            //global $user_id;
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getAllLTs();
 
            $response["error"] = false;
            $response["tasks"] = array();
 
            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                //$tmp["id_lugar"] = $task["id_lugar"];
                $tmp["nombre"] = $task["nombre"];
                $tmp["comuna"] = $task["comuna"];
                $tmp["descripcion"] = $task["descripcion"];
                $tmp["selloQ"] = $task["selloQ"];
                $tmp["rut_empresario"] = $task["rut_empresario"];
                $tmp["lat"] = $task["lat"];
                array_push($response["tasks"], $tmp);
            }
 
            echoRespnse(200, $response);
        });
/**
 * Listing all lugares turisticos por categoria
 * method GET
 * url /tasks          
 */
$app->post('/lugares_turisticos_por_categoria', function() use ($app){
            //global $user_id;
            verifyRequiredParams(array('categoria'));
            
            $categoria = $app->request->post('categoria');
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getLugarTuristicoByCategoria($categoria);
 
            $response["error"] = false;
            $response["lugares_turisticos"] = array();
 
            // looping through result and preparing tasks array
            while ($lugares_turisticos = $result->fetch_assoc()) {
                $tmp = array();
                // $tmp["puntuacion"] = $lugares_turisticos["puntuacion"];
                $tmp["id_lugar"] = $lugares_turisticos["id_lugar"];
                $tmp["nombre"] = $lugares_turisticos["nombre"];
                $tmp["comuna"] = $lugares_turisticos["comuna"];
                $tmp["descripcion"] = $lugares_turisticos["descripcion"];
                $tmp["selloQ"] = $lugares_turisticos["selloQ"];
                $tmp["rut_empresario"] = $lugares_turisticos["rut_empresario"];
                $tmp["lat"] = $lugares_turisticos["lat"];
                $tmp["lon"] = $lugares_turisticos["lon"];
                // $tmp["num_c"] = $lugares_turisticos["num_c"];
                array_push($response["lugares_turisticos"], $tmp);
            }
 
            echoRespnse(200, $response);
        });

/**
 * Creacion Itinerario
 * url - /register
 * method - POST
 * params - user, nombre, pass, correo
 */
$app->post('/itinerario_post', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('userid', 'nombre_itinerario','descripcion'));
 
            $response = array();
 
            // reading post params
            $user = $app->request->post('userid');
            $name = $app->request->post('nombre_itinerario');
            $descrp = $app->request->post('descripcion');
            // validating email address
            //validateEmail($email);
 
            $db = new DbHandler();
            $res = $db->createItinerario($user, $name, $descrp);
 
            /////////////////
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Creacion de itinerario exitosa";
                echoRespnse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Creacion de itinerario fallida";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoRespnse(200, $response);
            }
        });

/**
 * Recuperar itinerarios
 * url - /register
 * method - POST
 * params - user
 */
$app->post('/itinerarios_get', function() use ($app){
            //global $user_id;
            verifyRequiredParams(array('user_id'));
            
            $user_id = $app->request->post('user_id');
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getUserItinerarios($user_id);
 
            $response["error"] = false;
            $response["itinerarios"] = array();
 
            // looping through result and preparing tasks array
            while ($itinerarios = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $itinerarios["id"];
                $tmp["nombre"] = $itinerarios["nombre"];
                $tmp["descripcion"] = $itinerarios["descripcion"];
                $tmp["fecha"] = $itinerarios["fecha"];
                array_push($response["itinerarios"], $tmp);
            }
 
            echoRespnse(200, $response);
        });

/**
 * Recuperar itinerarios
 * url - /register
 * method - POST
 * params - user
 */
$app->post('/get_categorias', function() use ($app){
            //global $user_id;
            
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getCategorias();
 
            $response["error"] = false;
            $response["categorias"] = array();
 
            // looping through result and preparing tasks array
            while ($categorias = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["nombre_categoria"] = $categorias["nombre_categoria"];
                array_push($response["categorias"], $tmp);
            }
 
            echoRespnse(200, $response);
        });

/**
 * Recuperar itinerarios
 * url - /register
 * method - POST
 * params - user
 */
$app->post('/comentarios_lugar_get', function() use ($app){
            //global $user_id;

            verifyRequiredParams(array('id_lugar'));
            
            $id_lugar = $app->request->post('id_lugar');
            
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getComentariosLugar($id_lugar);
 
            $response["error"] = false;
            $response["comentario"] = array();
 
            // looping through result and preparing tasks array
            while ($comentario = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $comentario["id"];
                $tmp["puntuacion"] = $comentario["puntuacion"];
                $tmp["comentario"] = $comentario["comentario"];
                $tmp["nombre_usuario"] = $comentario["nombre_usuario"];
                array_push($response["comentario"], $tmp);
            }
 
            echoRespnse(200, $response);
        });


/**
// /**
//  * Creating new task in db
//  * method POST
//  * params - name
//  * url - /tasks/
//  */
$app->post('/comentario_post', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('user_id', 'lugar_id','calificacion', 'comentario'));
 
            $response = array();
 
            // reading post params
            $user = $app->request->post('user_id');
            $lugar = $app->request->post('lugar_id');
            $comentario = $app->request->post('calificacion');
            $calificacion = $app->request->post('comentario');
            
 
            $db = new DbHandler();
            $res = $db->createCalificacion($user, $lugar, $comentario, $calificacion);
 
            /////////////////
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Comentario realizado exitosamente";
                echoRespnse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Comentario realizado fallida";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Hubo un problema con la base de datos";
                echoRespnse(200, $response);
            }
        });

/**
// /**
//  * Creating new task in db
//  * method POST
//  * params - name
//  * url - /tasks/
//  */
$app->post('/lugares_turisticos_por_itinerario', function() use ($app){
            //global $user_id;
            verifyRequiredParams(array('id_iti'));
            
            $id_iti = $app->request->post('id_iti');
            $response = array();
            $db = new DbHandler();
 
            // fetching all user tasks
            $result = $db->getLugarTuristicoByItinerario($id_iti);
 
            $response["error"] = false;
            $response["lugares_turisticos"] = array();
 
            // looping through result and preparing tasks array
            while ($lugares_turisticos = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id_lugar"] = $lugares_turisticos["id_lugar"];
                $tmp["nombre"] = $lugares_turisticos["nombre"];
                $tmp["comuna"] = $lugares_turisticos["comuna"];
                $tmp["descripcion"] = $lugares_turisticos["descripcion"];
                $tmp["selloQ"] = $lugares_turisticos["selloQ"];
                $tmp["rut_empresario"] = $lugares_turisticos["rut_empresario"];
                $tmp["lat"] = $lugares_turisticos["lat"];
                $tmp["lon"] = $lugares_turisticos["lon"];
                array_push($response["lugares_turisticos"], $tmp);
            }
 
            echoRespnse(200, $response);
        });

/**
// /**
//  * Creating new task in db
//  * method POST
//  * params - name
//  * url - /tasks/
//  */
$app->post('/agregar_lugar_itinerario', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('lugar_id','id_itinerario'));
 
            $response = array();
 
            // reading post params
            $id_itinerario = $app->request->post('id_itinerario');
            $id_lugar = $app->request->post('lugar_id');
            
            $db = new DbHandler();
            $res = $db->agregar_lugar_itinerario($id_itinerario, $id_lugar);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Lugar agregado exitosamente";
                echoRespnse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Agregado itinerario fallido";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoRespnse(200, $response);
            }

        });



 
$app->run();
?>