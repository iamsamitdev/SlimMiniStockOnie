<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

return function (App $app)
{
    $container = $app->getContainer();

    // การสร้าง Routing
    // Root
    $app->get('/', function (Request $request, Response $response, array $args) use ($container)
    {
        echo "<h1 style='text-align:center; margin-top:45vh'>STOCK API</h3>";
        // echo time();
        // echo "<br>";
        // echo time()+(60*60);
    });

    // Document
    $app->get('/v1/doc', function($request, $response, $args) {
        $swagger = \OpenApi\Generator::scan(['/register']);
        header('Content-Type: application/json');
        echo $swagger;
    });


    // @OA\Info(title="My First API", version="0.1")
    // @OA\Get(
    //      path="/register/resource.json",
    //      @OA\Response(response="200", description="An example resource")
    // );

    // User Register
    $app->post('/register', function (Request $request, Response $response, array $args) {
    
        // รับจาก Client
        $body = $this->request->getParsedBody();
        $password = sha1($body['password']);
        // print_r($body);
        $img = "noimg.jpg";
        $sql = "INSERT INTO users(username,password,fullname,img_profile,status) 
                   VALUES(:username,:password,:fullname,:img_profile,:status)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("username", $body['username']);
        $sth->bindParam("password", $password);
        $sth->bindParam("fullname", $body['fullname']);
        $sth->bindParam("img_profile", $img);
        $sth->bindParam("status", $body['status']);

        if($sth->execute()){
            $data = $this->db->lastInsertId();
            $result = [
                'id' => $data,
                'status' => 'success'
            ];
        }else{
            $result = [
                'id' => '',
                'status' => 'fail'
            ];
        }

        return $this->response->withJson($result); 

    });

    // Login และ รับ Token
    $app->post('/login', function (Request $request, Response $response, array $args) {
 
        $body = $request->getParsedBody();

        $password = sha1($body['password']);

        $sql = "SELECT * FROM users WHERE username=:username and password=:password";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("username", $body['username']);
        $sth->bindParam("password", $password);
        $sth->execute();

        $count = $sth->rowCount();
        if($count){
            $user = $sth->fetchObject();
            $settings = $this->get('settings'); // get settings array.
            $payload = array(
                'id' => $user->id,
                'username' => $user->username,
                "iat" => time(),
                "exp" => time()+(60*60),  // Maximum expiration time is one hour
            );
            // JWT::encode($payload, $private_key, "HS256");
            $token = JWT::encode(
                // ['id' => $user->id, 'username' => $user->username], 
                $payload,
                $settings['jwt']['secret'], 
                "HS256"
            );
            return $this->response->withJson(['token' => $token]);
        }else{
            return $this->response->withJson(['error' => true, 'message' => 'These credentials do not match our records.']);
        }
    });


    // Routing Group
    $app->group('/api/v1', function () use ($app)
    {

        // $container = $app->getContainer();

        //==================================================================
        // CRUD TABLE Products
        //=================================================================
        // ดึงข้อมูลจากตาราง products ออกมาแสดงเป็น json

        // Get All Products (Method GET)
        $app->get('/products', function (Request $request, Response $response, array $args)
        {
            
            // $decoded = $request->getAttribute("decoded_token_data");
            // print_r($decoded);
            // exit();

            // Read product
            $sql  = "SELECT * FROM products";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $product = $stmt->fetchAll();

            if (count($product))
            {
                $result = [
                    'status'  => 'success',
                    'message' => 'Read Product Success',
                    'data'    => $product,
                ];
            }
            else
            {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Empty Product Data',
                    'data'    => $product,
                ];
            }

            return $this->response->withJson($result);
        });

        // Get  Product By ID (Method GET)
        $app->get('/products/{id}', function (Request $request, Response $response, array $args) 
        {
            $sql  = "SELECT * FROM products WHERE id='$args[id]'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $product = $stmt->fetchAll();
            if (count($product))
            {
                $result = [
                    'status'  => 'success',
                    'message' => 'Read Product Success',
                    'data'    => $product,
                ];
            }
            else
            {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Empty Product Data',
                    'data'    => $product,
                ];
            }

            return $this->response->withJson($result);
        });


         // Add new Product  (Method Post)
         $app->post('/products', function (Request $request, Response $response, array $args) 
         {
             // รับจาก Client
             $body = $this->request->getParsedBody();
             // print_r($body);
             $img = "noimg.jpg";
             $sql = "INSERT INTO products(product_name,product_detail,product_barcode,product_price,product_qty,product_image) 
                        VALUES(:product_name,:product_detail,:product_barcode,:product_price,:product_qty,:product_image)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("product_name", $body['product_name']);
            $sth->bindParam("product_detail", $body['product_detail']);
            $sth->bindParam("product_barcode", $body['product_barcode']);
            $sth->bindParam("product_price", $body['product_price']);
            $sth->bindParam("product_qty", $body['product_qty']);
            $sth->bindParam("product_image", $img);

            if($sth->execute()){
                $data = $this->db->lastInsertId();
                $result = [
                    'id' => $data,
                    'status' => 'success'
                ];
            }else{
                $result = [
                    'id' => '',
                    'status' => 'fail'
                ];
            }

            return $this->response->withJson($result); 

         });

        // Edit Product  (Method Put)
        $app->put('/products/{id}', function (Request $request, Response $response, array $args) {
             // รับจาก Client
             $body = $this->request->getParsedBody();

             $sql = "UPDATE  products SET 
                            product_name=:product_name,
                            product_detail=:product_detail,
                            product_barcode=:product_barcode,
                            product_price=:product_price,
                            product_qty=:product_qty
                        WHERE id='$args[id]'";
 
            $sth = $this->db->prepare($sql);
            $sth->bindParam("product_name", $body['product_name']);
            $sth->bindParam("product_detail", $body['product_detail']);
            $sth->bindParam("product_barcode", $body['product_barcode']);
            $sth->bindParam("product_price", $body['product_price']);
            $sth->bindParam("product_qty", $body['product_qty']);
            

            if($sth->execute()){
                $data = $args['id'];
                $result = [
                    'id' => $data,
                    'status' => 'success'
                ];
            }else{
                $result = [
                    'id' => '',
                    'status' => 'fail'
                ];
            }

            return $this->response->withJson($result);  
          });

        // Delete Product  (Method Delete)
        $app->delete('/products/{id}', function (Request $request, Response $response, array $args) {
            // รับจาก Client
            $body = $this->request->getParsedBody();
            $sql = "DELETE FROM products WHERE id='$args[id]'";
 
            $sth = $this->db->prepare($sql);
            
            if($sth->execute()){
                $data = $args['id'];
                $result = [
                    'id' => $data,
                    'status' => 'success'
                ];
            }else{
                $result = [
                    'id' => '',
                    'status' => 'fail'
                ];
            }

            return $this->response->withJson($result); 
        });

    });

};
