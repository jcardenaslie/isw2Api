<?php
 
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 */
class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    /* ------------- `users` table method ------------------ */
 
    /**
     * Creating new user
     * @param String $user User id 
     * @param String $name User name
     * @param String $password User login password
     * @param String $email User login email
     */
    public function createUser($user, $name, $password, $email) {
        require_once 'PassHash.php';
        $response = array();
 
        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
 
            // Generating API key
            $api_key = $this->generateApiKey();
 
            // insert query
            // $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt = $this->conn->prepare("INSERT INTO usuario(user, nombre, pass, correo) values(?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user, $name, $password, $email);
 
            $result = $stmt->execute();
 
            $stmt->close();
 
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
 
        return $response;
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {

        $stmt = $this->conn->prepare("SELECT user from usuario WHERE correo = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
 
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        // $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt = $this->conn->prepare("SELECT pass FROM usuario WHERE correo = ?");

        $stmt->bind_param("s", $email);
 
        $stmt->execute();
        $pass_returned;
        //$stmt->bind_result($password_hash);
        $stmt->bind_result($pass_returned);
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password
 
            $stmt->fetch();
 
            $stmt->close();

            if ($pass_returned ==$password) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();
 
            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        // $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = ?");
        $stmt = $this->conn->prepare("SELECT user, nombre, correo FROM usuario WHERE correo = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

        /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($userid) {
        $stmt = $this->conn->prepare("SELECT user FROM usuarios WHERE user = ?");
        $stmt->bind_param("s", $userid);
        if ($stmt->execute()) {
            $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
 
 
    // /**
    //  * Fetching user api key
    //  * @param String $user_id user id primary key in user table
    //  */
    // public function getApiKeyById($user_id) {
    //     $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
    //     $stmt->bind_param("i", $user_id);
    //     if ($stmt->execute()) {
    //         $api_key = $stmt->get_result()->fetch_assoc();
    //         $stmt->close();
    //         return $api_key;
    //     } else {
    //         return NULL;
    //     }
    // }
 
    // /**
    //  * Fetching user id by api key
    //  * @param String $api_key user api key
    //  */
    // public function getUserId($api_key) {
    //     $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
    //     $stmt->bind_param("s", $api_key);
    //     if ($stmt->execute()) {
    //         $user_id = $stmt->get_result()->fetch_assoc();
    //         $stmt->close();
    //         return $user_id;
    //     } else {
    //         return NULL;
    //     }
    // }
 
    // /**
    //  * Validating user api key
    //  * If the api key is there in db, it is a valid key
    //  * @param String $api_key user api key
    //  * @return boolean
    //  */
    // public function isValidApiKey($api_key) {
    //     $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
    //     $stmt->bind_param("s", $api_key);
    //     $stmt->execute();
    //     $stmt->store_result();
    //     $num_rows = $stmt->num_rows;
    //     $stmt->close();
    //     return $num_rows > 0;
    // }
 

    // /* ------------- `tasks` table method ------------------ */

    /**
     * Fetching single task
     * @param String nombre lugar turistico
     */
    public function getLT($lt_name) {
        $stmt = $this->conn->prepare("SELECT * FROM lugar WHERE nombre = ?");
        $stmt->bind_param("s", $lt_name);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
            // $task = $stmt->get_result()->fetch_assoc();
            // //var_dump($stmt->get_result()->fetch_assoc());
            // $stmt->close();
            // return $task;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String nombre lugar turistico
     */
    public function getLugarTuristicoByCategoria($categoria) {
        $stmt = $this->conn->prepare(
            "SELECT * 
            FROM lugar JOIN posee ON lugar.id_lugar = posee.id_lugar 
            WHERE categoria = ?");
        // $stmt = $this->conn->prepare(
        //     "SELECT * FROM posee WHERE categoria = ?");
        $stmt->bind_param("s", $categoria);
        
        $stmt->execute();
        $task = $stmt->get_result();
        $stmt->close();
        return $task;

    }

    public function getLugarTuristicoByCategoria2($categoria) {
        $stmt = $this->conn->prepare(
            "SELECT 
            AVG(puntuacion) as puntuacion,
            nombre,
            lugar.id_lugar as id_lugar,
            lugar.comuna as comuna,
            descripcion,
            selloQ,
            rut_empresario,
            lat,
            lon,
            num_c 
            FROM posee,
            calificacion,
            calificacion_sobre,
            lugar 
            WHERE calificacion.id=calificacion_sobre.id_calificacion and lugar.id_lugar=calificacion_sobre.id_lugar and posee.id_lugar=lugar.id_lugar and posee.categoria=? group by lugar.id_lugar");
        // $stmt = $this->conn->prepare(
        //     "SELECT * FROM posee WHERE categoria = ?");
        $stmt->bind_param("s", $categoria);
        
        $stmt->execute();
        $task = $stmt->get_result();
        $stmt->close();
        return $task;

    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllLTs() {
        $stmt = $this->conn->prepare("SELECT * FROM lugar LIMIT 10");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }


    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getCategorias() {
        $stmt = $this->conn->prepare("SELECT * FROM categoria");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    

    //     /**
    //  * Function to assign a task to user
    //  * @param String $user_id id of the user
    //  * @param String $task_id id of the task
    //  */
    // public function createUserTask($user_id, $task_id) {
    //     $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
    //     $stmt->bind_param("ii", $user_id, $task_id);
    //     $result = $stmt->execute();
    //     $stmt->close();
    //     return $result;
    // }

    // /**
    //  * Fetching single task
    //  * @param String $task_id id of the task
    //  */
    // public function getTask($task_id, $user_id) {
    //     $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
    //     $stmt->bind_param("ii", $task_id, $user_id);
    //     if ($stmt->execute()) {
    //         $task = $stmt->get_result()->fetch_assoc();
    //         $stmt->close();
    //         return $task;
    //     } else {
    //         return NULL;
    //     }
    // }

 
    // /**
    //  * Fetching all user tasks
    //  * @param String $user_id id of the user
    //  */
    // public function getAllUserTasks($user_id) {
    //     $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
    //     $stmt->bind_param("i", $user_id);
    //     $stmt->execute();
    //     $tasks = $stmt->get_result();
    //     $stmt->close();
    //     return $tasks;
    // }
 
    // /**
    //  * Updating task
    //  * @param String $task_id id of the task
    //  * @param String $task task text
    //  * @param String $status task status
    //  */
    // public function updateTask($user_id, $task_id, $task, $status) {
    //     $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
    //     $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
    //     $stmt->execute();
    //     $num_affected_rows = $stmt->affected_rows;
    //     $stmt->close();
    //     return $num_affected_rows > 0;
    // }
 
    // /**
    //  * Deleting a task
    //  * @param String $task_id id of the task to delete
    //  */
    // public function deleteTask($user_id, $task_id) {
    //     $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
    //     $stmt->bind_param("ii", $task_id, $user_id);
    //     $stmt->execute();
    //     $num_affected_rows = $stmt->affected_rows;
    //     $stmt->close();
    //     return $num_affected_rows > 0;
    // }
 
    // /* ------------- `user_tasks` table method ------------------ */
    public function createItinerario($userid, $nombre_itinerario, $descrp) {

        $response = array();
            $stmt = $this->conn->prepare("INSERT INTO 
                itinerario(nombre,creador,fecha,descripcion) values(?,?, CURRENT_DATE,?)");
            $stmt->bind_param("sss", $nombre_itinerario, $userid, $descrp);
 
            $result = $stmt->execute();
            
            //$result = getUserItinerarios($userid);

            $stmt->close();
 
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                //return $result
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
    }

    public function getItinerario($iti_creador) {
        $stmt = $this->conn->prepare("SELECT * FROM itinerario WHERE creador = ?");
        $stmt->bind_param("s",$iti_creador);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
            // $task = $stmt->get_result()->fetch_assoc();
            // //var_dump($stmt->get_result()->fetch_assoc());
            // $stmt->close();
            // return $task;
        } else {
            return NULL;
        }
    }

    public function getUserItinerarios($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM itinerario WHERE creador = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $task = $stmt->get_result();
        $stmt->close();
        return $task;

    }

    public function createCalificacion($user, $lugar, $comentario, $calificacion){

        $response = array();
        $stmt = $this->conn->prepare("INSERT INTO calificacion(puntuacion,comentario,fecha) 
                 VALUES(?,?,CURRENT_TIMESTAMP)");
        $stmt->bind_param("is", $calificacion, $comentario);
 
        $result = $stmt->execute();

        $stmt = $this->conn->prepare("
            INSERT INTO calificacion_sobre(nombre_usuario, id_lugar) 
            VALUES(?,?)");

        $stmt->bind_param("ss", $user, $lugar);
 
        $result2 = $stmt->execute();

            
        $stmt->close();

        //$result2 = createCalificacionSobreLugar($user, $lugar);
 
        if ($result && $result2) {
            return USER_CREATED_SUCCESSFULLY;
        } else {
            return USER_CREATE_FAILED;
        }
    }

    public function createCalificacionSobreLugar($user, $lugar){

        $response = array();
        $stmt = $this->conn->prepare("
            INSERT INTO calificacion_sobre(nombre_usuario, id_lugar) 
            VALUES(?,?)");

        $stmt->bind_param("ss", $user, $lugar);
 
        $result = $stmt->execute();
            
        $stmt->close();
 
        if ($result) {
            return USER_CREATED_SUCCESSFULLY;
        } else {
            return USER_CREATE_FAILED;
        }
    }


    public function getLugarTuristicoByItinerario($id_iti) {
        $stmt = $this->conn->prepare("SELECT * FROM itinerario_incluye_lugar,lugar WHERE 
            lugar.id_lugar=itinerario_incluye_lugar.lugar and id=?");
        // $stmt = $this->conn->prepare(
        //     "SELECT * FROM posee WHERE categoria = ?");
        $stmt->bind_param("s", $id_iti);
        
        $stmt->execute();
        $task = $stmt->get_result();
        $stmt->close();
        return $task;

    }

    public function agregar_lugar_itinerario($id_itinerario, $id_lugar) {

        $response = array();
        $stmt = $this->conn->prepare("INSERT INTO 
                itinerario_incluye_lugar(id,lugar) values(?,?)");
        $stmt->bind_param("is", $id_itinerario, $id_lugar);
 
        $result = $stmt->execute();
            
        $stmt->close();
        if ($result) {
            return USER_CREATED_SUCCESSFULLY;
        } else {

            return USER_CREATE_FAILED;
        }
    }

    public function getComentariosLugar($id_lugar) {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM calificacion, calificacion_sobre
            WHERE calificacion_sobre.id_lugar = ? and calificacion.id = calificacion_sobre.id_calificacion
            ");

        // $stmt->bind_param("s",$id_lugar);
        // if ($stmt->execute()) {
        //     return $stmt->get_result()->fetch_assoc();
        // } else {
        //     return NULL;
        // }
        $stmt->bind_param("s", $id_lugar);
        
        $stmt->execute();
        $task = $stmt->get_result();
        $stmt->close();
        return $task;
    }

    // public function getPuntuacion($id_lugar) {
    //     $stmt = $this->conn->prepare("
    //         SELECT AVG(puntuacion),nombre,lugar.comuna,descripcion,selloQ,rut_empresario,lat,lon,num_c 
    //         FROM calificacion,calificacion_sobre,lugar 
    //         WHERE calificacion.id=calificacion_sobre.id_calificacion and lugar.id_lugar=calificacion_sobre.id_lugar and 
    //             calificacion_sobre.id_lugar=?");
    //     $stmt->bind_param("s", $id_lugar);
    //     $stmt->execute();
    //     $task = $stmt->get_result();
    //     $stmt->close();
    //     return $task;
    // }
    // public function createCalificacionSobre($user, $lugar){

    //     $response = array();
    //     $stmt = $this->conn->prepare("
    //         INSERT INTO calificacion_sobre(nombre_usuario, id_lugar) 
    //         VALUES(?,?");
    //     $stmt->bind_param("ss", $user, $lugar);
 
    //     $result = $stmt->execute();
            
    //     $stmt->close();
 
    //     if ($result) {
    //         return USER_CREATED_SUCCESSFULLY;
    //     } else {
    //         return USER_CREATE_FAILED;
    //     }
    // }

    


}
 
?>